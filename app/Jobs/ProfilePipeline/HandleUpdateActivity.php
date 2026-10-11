<?php

namespace App\Jobs\ProfilePipeline;

use App\Jobs\AvatarPipeline\RemoteAvatarFetchFromUrl;
use App\Models\Profile;
use App\Services\SanitizeService;
use App\Util\Lexer\Autolink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HandleUpdateActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payload = $this->payload;

        if (! is_array($payload) || ! isset($payload['actor']) || ! is_string($payload['actor'])) {
            Log::info('HandleUpdateActivity: Invalid payload or missing actor, skipping job');

            return;
        }

        if (! isset($payload['object']) || ! is_array($payload['object'])) {
            Log::info('HandleUpdateActivity: Missing or invalid object, skipping job');

            return;
        }

        $object = $payload['object'];

        if (! isset($object['id']) || $object['id'] !== $payload['actor']) {
            Log::info('HandleUpdateActivity: Object id does not match actor, skipping job');

            return;
        }

        $profile = Profile::whereRemoteUrl($payload['actor'])->first();

        if (! $profile || $profile->domain === null || $profile->private_key) {
            return;
        }

        $sharedInbox = $object['endpoints']['sharedInbox'] ?? null;
        $profile->sharedInbox = is_string($sharedInbox) ? $sharedInbox : null;

        $publicKey = $object['publicKey']['publicKeyPem'] ?? null;
        if (is_string($publicKey) && $publicKey !== '') {
            $profile->public_key = $publicKey;
        }

        $profile->bio = $this->cleanBio($object['summary'] ?? null);
        $profile->name = $this->cleanName($object['name'] ?? null);

        if ($profile->isDirty()) {
            $profile->save();
        }

        $iconUrl = $object['icon']['url'] ?? null;
        if (is_string($iconUrl) && $iconUrl !== '') {
            RemoteAvatarFetchFromUrl::dispatch($profile, $iconUrl)->onQueue('low');
        } else {
            if ($profile->avatar) {
                $profile->avatar->update(['remote_url' => null]);
            }
            Cache::forget('avatar:'.$profile->id);
        }
    }

    protected function cleanBio($summary): ?string
    {
        if (! is_string($summary)) {
            return null;
        }

        $text = trim(strip_tags($summary));

        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) > 500) {
            $text = mb_substr($text, 0, (int) config('pixelfed.max_bio_length'));
            $summary = Autolink::create()->autolink($text);
        }

        $clean = app(SanitizeService::class)->html($summary);

        return is_string($clean) && trim($clean) !== '' ? $clean : null;
    }

    protected function cleanName($name): ?string
    {
        if (! is_string($name)) {
            return null;
        }

        $i = 0;
        do {
            $prev = $name;
            $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } while ($name !== $prev && ++$i < 5);

        $name = strip_tags($name);

        $name = str_replace(['<', '>'], '', $name);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        $name = trim(mb_substr($name, 0, (int) config('pixelfed.max_name_length')));

        return $name !== '' ? $name : null;
    }
}
