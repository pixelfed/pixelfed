<?php

namespace App\Util\ActivityPub\Inbox;

use App\Federation\Handlers\DirectMessageHandler;
use App\Jobs\ProfilePipeline\HandleUpdateActivity;
use App\Jobs\StatusPipeline\StatusRemoteUpdatePipeline;
use App\Models\Status;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\Validator\UpdatePersonValidator;

trait HandlesUpdates
{
    public function handleUpdateActivity(): void
    {
        $activity = $this->payload['object'];

        if (! isset($activity['type'], $activity['id'])) {
            return;
        }

        if (! Helpers::validateUrl($activity['id'])) {
            return;
        }

        if ($activity['type'] === 'Note') {
            $status = Status::whereObjectUrl($activity['id'])->first();
            $actor = Helpers::profileFetch(Helpers::pluckval($this->payload['actor']));

            if ($status && $actor && (int) $status->profile_id === (int) $actor->id) {
                StatusRemoteUpdatePipeline::dispatch($activity);
            } elseif (! $status && $actor && $actor->domain !== null) {
                app(DirectMessageHandler::class)->handleUpdate($activity, $actor);
            }
        } elseif ($activity['type'] === 'Person') {
            if (UpdatePersonValidator::validate($this->payload)) {
                HandleUpdateActivity::dispatch($this->payload)->onQueue('low');
            }
        }
    }
}
