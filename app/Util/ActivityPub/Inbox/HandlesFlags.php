<?php

namespace App\Util\ActivityPub\Inbox;

use App\Models\Instance;
use App\Models\Profile;
use App\Models\RemoteReport;
use App\Models\Status;
use App\Services\SanitizeService;
use App\Util\ActivityPub\Helpers;

trait HandlesFlags
{
    public function handleFlagActivity(): void
    {
        if (! $this->payloadHasKeys(['id', 'type', 'actor', 'object'])) {
            return;
        }

        $id = $this->payload['id'];
        $actor = $this->payload['actor'];

        if (Helpers::validateLocalUrl($id) || ! $this->hostsMatch($id, $actor)) {
            return;
        }

        $content = $this->sanitizeFlagContent();
        $object = $this->payload['object'];

        if (empty($object) || (! is_array($object) && ! is_string($object))) {
            return;
        }

        if (is_array($object) && count($object) > 100) {
            return;
        }

        $objects = collect([]);
        $accountId = null;

        foreach ($object as $objectUrl) {
            if (! Helpers::validateLocalUrl($objectUrl)) {
                return;
            }

            if (str_contains($objectUrl, '/users/')) {
                $username = last(explode('/', $objectUrl));
                $profileId = Profile::whereUsername($username)->first();
                if ($profileId) {
                    $accountId = $profileId->id;
                }
            } elseif (str_contains($objectUrl, '/p/')) {
                $postId = last(explode('/', $objectUrl));
                $objects->push($postId);
            }
        }

        if (! $accountId && ! $objects->count()) {
            return;
        }

        if ($objects->count()) {
            $obc = $objects->count();
            if ($obc > 25) {
                if ($obc > 30) {
                    return;
                }
                $objects = collect($objects->take(20)->all());
                $obc = $objects->count();
            }
            $count = Status::whereProfileId($accountId)->whereIn('id', $objects)->count();
            if ($obc !== $count) {
                return;
            }
        }

        $instanceHost = parse_url($id, PHP_URL_HOST);

        $instance = Instance::updateOrCreate([
            'domain' => $instanceHost,
        ]);

        $report = new RemoteReport;
        $report->status_ids = $objects->toArray();
        $report->comment = $content;
        $report->account_id = $accountId;
        $report->uri = $id;
        $report->instance_id = $instance->id;
        $report->report_meta = [
            'actor' => $actor,
            'object' => $object,
        ];
        $report->save();
    }

    /**
     * Sanitize and optionally truncate flag/report content.
     */
    protected function sanitizeFlagContent(): ?string
    {
        if (! isset($this->payload['content'])) {
            return null;
        }

        $raw = $this->payload['content'];

        if (strlen($raw) > 5000) {
            return app(SanitizeService::class)->html(
                substr($raw, 0, 5000).' ... (truncated message due to exceeding max length)'
            );
        }

        return app(SanitizeService::class)->html($raw);
    }
}
