<?php

namespace App\Util\ActivityPub\Inbox;

use App\Jobs\MovePipeline\CleanupLegacyAccountMovePipeline;
use App\Jobs\MovePipeline\MoveMigrateFollowersPipeline;
use App\Jobs\MovePipeline\ProcessMovePipeline;
use App\Jobs\MovePipeline\UnfollowLegacyAccountMovePipeline;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

trait HandlesMoves
{
    public function handleMoveActivity(): void
    {
        $actor = $this->payload['actor'];
        $activity = $this->payload['object'];
        $target = $this->payload['target'];

        if (
            ! Helpers::validateUrl($actor) ||
            ! Helpers::validateUrl($activity) ||
            ! Helpers::validateUrl($target)
        ) {
            return;
        }

        Bus::chain([
            new ProcessMovePipeline($target, $activity),
            new MoveMigrateFollowersPipeline($target, $activity),
            new UnfollowLegacyAccountMovePipeline($target, $activity),
            new CleanupLegacyAccountMovePipeline($target, $activity),
        ])
            ->catch(function (Throwable $e) {
                Log::error($e);
            })
            ->onQueue('move')
            ->delay(now()->addMinutes(random_int(1, 3)))
            ->dispatch();
    }
}
