<?php

namespace App\Jobs\StatusPipeline;

use App\Models\Media;
use App\Models\ModLog;
use App\Models\Profile;
use App\Models\Status;
use App\Models\StatusEdit;
use App\Services\SanitizeService;
use App\Services\SecureMediaFetchService;
use App\Services\StatusService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Purify;

class StatusRemoteUpdatePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $activity;

    /**
     * Create a new job instance.
     */
    public function __construct($activity)
    {
        $this->activity = $activity;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $activity = $this->activity;

        // Verify activity exists and has required fields
        if (! $activity) {
            Log::info('StatusRemoteUpdatePipeline: Activity not found, skipping job');

            return;
        }
        if (! isset($activity['id'])) {
            Log::info('StatusRemoteUpdatePipeline: Invalid activity data, skipping job');

            return;
        }

        $status = Status::with('media')->whereObjectUrl($activity['id'])->first();
        if (! $status) {
            Log::info("StatusRemoteUpdatePipeline: Status not found for activity {$activity['id']}, skipping job");

            return;
        }

        try {
            $this->createPreviousEdit($status);
            $this->updateMedia($status, $activity);
            $this->updateImmediateAttributes($status, $activity);
            $this->createEdit($status, $activity);
        } catch (\Exception $e) {
            Log::warning("StatusRemoteUpdatePipeline: Failed to update status {$status->id}: ".$e->getMessage());
            throw $e;
        }
    }

    protected function createPreviousEdit($status)
    {
        try {
            if (! $status->edits()->count()) {
                StatusEdit::create([
                    'status_id' => $status->id,
                    'profile_id' => $status->profile_id,
                    'caption' => $status->caption,
                    'spoiler_text' => $status->cw_summary,
                    'is_nsfw' => $status->is_nsfw,
                    'ordered_media_attachment_ids' => $status->media()->orderBy('order')->pluck('id')->toArray(),
                    'created_at' => $status->created_at,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("StatusRemoteUpdatePipeline: Failed to create previous edit for status {$status->id}: ".$e->getMessage());
            throw $e;
        }
    }

    protected function updateMedia($status, $activity)
    {
        if (! isset($activity['attachment'])) {
            return;
        }
        $ogm = $status->media->count() ? $status->media()->orderBy('order')->get() : collect([]);
        $nm = collect($activity['attachment'])->filter(function ($nm) {
            return isset(
                $nm['type'],
                $nm['mediaType'],
                $nm['url']
            ) &&
            in_array($nm['type'], ['Document', 'Image', 'Video']) &&
            in_array($nm['mediaType'], explode(',', config_cache('pixelfed.media_types')));
        });

        // Skip when no media
        if (! $ogm->count() && ! $nm->count()) {
            return;
        }

        Media::whereProfileId($status->profile_id)
            ->whereStatusId($status->id)
            ->update([
                'status_id' => null,
            ]);

        $nm->each(function ($n, $key) use ($status) {
            // Validate the attacker-controlled attachment URL before issuing any
            // server-side request. This rejects http://, IP-literal, and
            // (with DNS checks) private-resolving hosts, closing the SSRF sink.
            $url = Helpers::validateUrl($n['url']);
            if (! $url) {
                return;
            }

            // Hardened HEAD: validate + resolve public IPs + pin the connection
            // (CURLOPT_RESOLVE) + re-validate every redirect hop + byte cap.
            // Matches the SSRF hardening applied to every other remote-media sink.
            $res = SecureMediaFetchService::head($url);
            if ($res === false) {
                return;
            }

            if (! in_array($res['mime'], explode(',', config_cache('pixelfed.media_types')))) {
                return;
            }

            $m = new Media;
            $m->status_id = $status->id;
            $m->profile_id = $status->profile_id;
            $m->remote_media = true;
            $m->media_path = $url;
            $m->mime = $res['mime'];
            $m->size = $res['length'] ?? null;
            $m->caption = isset($n['name']) && ! empty($n['name']) ? Purify::clean($n['name']) : null;
            $m->remote_url = $url;
            $m->blurhash = isset($n['blurhash']) && (strlen($n['blurhash']) < 50) ? $n['blurhash'] : null;
            $m->width = isset($n['width']) && ! empty($n['width']) ? $n['width'] : null;
            $m->height = isset($n['height']) && ! empty($n['height']) ? $n['height'] : null;
            $m->skip_optimize = true;
            $m->order = $key + 1;
            $m->save();
        });
    }

    protected function updateImmediateAttributes($status, $activity)
    {
        if (isset($activity['content'])) {
            $cleanedCaption = app(SanitizeService::class)->html($activity['content']);
            $status->caption = strip_tags($cleanedCaption);
        }

        if (isset($activity['sensitive'])) {
            if ((bool) $activity['sensitive'] == false) {
                $status->is_nsfw = false;
                $exists = ModLog::whereObjectType('App\Status::class')
                    ->whereObjectId($status->id)
                    ->whereAction('admin.status.moderate')
                    ->exists();
                if ($exists == true) {
                    $status->is_nsfw = true;
                }
                $profile = Profile::find($status->profile_id);
                if (! $profile || $profile->cw == true) {
                    $status->is_nsfw = true;
                }
            } else {
                $status->is_nsfw = true;
            }
        }

        if (isset($activity['summary'])) {
            $status->cw_summary = app(SanitizeService::class)->html($activity['summary']);
        } else {
            $status->cw_summary = null;
        }

        $status->edited_at = now();
        $status->save();
        StatusService::del($status->id);
    }

    protected function createEdit($status, $activity)
    {
        $cleaned = isset($activity['content']) ? app(SanitizeService::class)->html($activity['content']) : null;
        $spoiler_text = isset($activity['summary']) ? app(SanitizeService::class)->html($activity['summary']) : null;
        $sensitive = isset($activity['sensitive']) ? $activity['sensitive'] : null;
        $mids = $status->media()->count() ? $status->media()->orderBy('order')->pluck('id')->toArray() : null;
        StatusEdit::create([
            'status_id' => $status->id,
            'profile_id' => $status->profile_id,
            'caption' => $cleaned,
            'spoiler_text' => $spoiler_text,
            'is_nsfw' => $sensitive,
            'ordered_media_attachment_ids' => $mids,
        ]);
    }
}
