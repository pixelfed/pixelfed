<?php

namespace App\Jobs\RemoteFollowPipeline;

use App\Jobs\ImageOptimizePipeline\ImageThumbnail;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Media;
use App\Status;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Storage;
use Zttp\Zttp;
use App\Util\ActivityPub\Helpers;

class RemoteFollowImportRecent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $actor;
    protected $profile;
    protected $outbox;
    protected $mediaCount;
    protected $cursor;
    protected $nextUrl;
    protected $supported;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($actorObject, $profile)
    {
        $this->actor = $actorObject;
        $this->profile = $profile;
        $this->cursor = 1;
        $this->mediaCount = 0;
        $this->supported = [
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/gif',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $outbox = $this->fetchOutbox();
    }

    public function fetchOutbox($url = false)
    {
        $url = ($url == false) ? $this->actor['outbox'] : $url;
        if (Helpers::validateUrl($url) == false) {
            return;
        }
        $response = Zttp::withHeaders([
            'User-Agent' => 'PixelfedBot v0.1 - https://pixelfed.org',
        ])->get($url);

        $this->outbox = $response->json();
        $this->parseOutbox($this->outbox);
    }

    public function parseOutbox($outbox)
    {
        $types = ['OrderedCollection', 'OrderedCollectionPage'];

        if (isset($outbox['totalItems']) && $outbox['totalItems'] < 1) {
            // Skip remote fetch, not enough posts
            Log::info('not enough items');

            return;
        }

        if (isset($outbox['type']) && in_array($outbox['type'], $types)) {
            Log::info('handle ordered collection');
            $this->handleOrderedCollection();
        }
    }

    public function handleOrderedCollection()
    {
        $outbox = $this->outbox;

        if (!isset($outbox['next']) && !isset($outbox['first']['next']) && $this->cursor !== 1) {
            $this->cursor = 40;
            $outbox['next'] = false;
        }

        if ($outbox['type'] == 'OrderedCollectionPage') {
            $this->nextUrl = $outbox['next'];
        }

        if (isset($outbox['first']) && !is_array($outbox['first'])) {
            // Mastodon detected
            Log::info('Mastodon detected...');
            $this->nextUrl = $outbox['first'];

            return $this->fetchOutbox($this->nextUrl);
        } else {
            // Pleroma detected.
            $this->nextUrl = isset($outbox['next']) ? $outbox['next'] : (isset($outbox['first']['next']) ? $outbox['first']['next'] : $outbox['next']);
            Log::info('Checking ordered items...');
            $orderedItems = isset($outbox['orderedItems']) ? $outbox['orderedItems'] : $outbox['first']['orderedItems'];
        }

        foreach ($orderedItems as $item) {
            Log::info('Parsing items...');
            $parsed = $this->parseObject($item);
            if ($parsed !== 0) {
                Log::info('Found media!');
                $this->importActivity($item);
            }
        }

        if ($this->cursor < 40 && $this->mediaCount < 9) {
            $this->cursor++;
            $this->mediaCount++;
            $this->fetchOutbox($this->nextUrl);
        }
    }

    public function parseObject($parsed)
    {
        if ($parsed['type'] !== 'Create') {
            return 0;
        }

        $activity = $parsed['object'];

        if (isset($activity['attachment']) && !empty($activity['attachment'])) {
            return $this->detectSupportedMedia($activity['attachment']);
        }
    }

    public function detectSupportedMedia($attachments)
    {
        $supported = $this->supported;
        $count = 0;

        foreach ($attachments as $media) {
            $mime = $media['mediaType'];
            $count = in_array($mime, $supported) ? ($count + 1) : $count;
        }

        return $count;
    }

    public function importActivity($activity)
    {
        $profile = $this->profile;
        $supported = $this->supported;
        $attachments = $activity['object']['attachment'];
        $caption = str_limit($activity['object']['content'], 125);

        if (Status::whereUrl($activity['id'])->count() !== 0) {
            return true;
        }

        $status = new Status();
        $status->profile_id = $profile->id;
        $status->url = $activity['id'];
        $status->local = false;
        $status->caption = strip_tags($caption);
        $status->created_at = Carbon::parse($activity['published']);

        $count = 0;

        foreach ($attachments as $media) {
            Log::info($media['mediaType'].' - '.$media['url']);
            $url = $media['url'];
            $mime = $media['mediaType'];
            if (!in_array($mime, $supported)) {
                Log::info('Invalid media, skipping. '.$mime);
                continue;
            }
            if (Helpers::validateUrl($url) == false) {
                Log::info('Skipping invalid attachment URL: ' . $url);
                continue;
            }
            
            $count++;

            if ($count === 1) {
                $status->save();
            }
            $this->importMedia($url, $mime, $status);
        }
        Log::info(count($attachments).' media found...');

        if ($count !== 0) {
            NewStatusPipeline::dispatch($status, $status->media->first());
        }
    }

    public function importMedia($url, $mime, $status)
    {
        $user = $this->profile;
        $monthHash = hash('sha1', date('Y').date('m'));
        $userHash = hash('sha1', $user->id.(string) $user->created_at);
        $storagePath = "public/m/{$monthHash}/{$userHash}";

        try {
            $info = pathinfo($url);
            $url = str_replace(' ', '%20', $url);
            $img = file_get_contents($url);
            $file = '/tmp/'.str_random(64);
            file_put_contents($file, $img);
            $path = Storage::putFile($storagePath, new File($file), 'public');

            $media = new Media();
            $media->status_id = $status->id;
            $media->profile_id = $status->profile_id;
            $media->user_id = null;
            $media->media_path = $path;
            $media->size = 0;
            $media->mime = $mime;
            $media->save();

            ImageThumbnail::dispatch($media);
            
            @unlink($file);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
