<?php

namespace App\Jobs\MovePipeline;

use App\Services\ActivityPubFetchService;
use App\Util\ActivityPub\Helpers;
use DateTime;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Arr;

class ProcessMovePipeline implements ShouldQueue
{
    use Queueable;

    public $target;

    public $activity;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 15;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 5;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct($target, $activity)
    {
        $this->target = $target;
        $this->activity = $activity;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping('process-move:'.$this->target),
            (new ThrottlesExceptionsWithRedis(5, 2 * 60))->backoff(1),
        ];
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(10);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (config('app.env') !== 'production' || (bool) config_cache('federation.activitypub.enabled') == false) {
            throw new Exception('Activitypub not enabled');
        }

        $validTarget = $this->checkTarget();
        if (! $validTarget) {
            throw new Exception('Invalid target');
        }

        $validActor = $this->checkActor();
        if (! $validActor) {
            throw new Exception('Invalid actor');
        }

    }

    protected function checkTarget()
    {
        $fetchTargetUrl = $this->target.'?cb='.time();
        $res = ActivityPubFetchService::fetchRequest($fetchTargetUrl, true);

        if (! $res || ! isset($res['alsoKnownAs'])) {
            return false;
        }

        $targetRes = Helpers::profileFetch($this->target);
        if (! $targetRes) {
            return false;
        }

        if (is_string($res['alsoKnownAs'])) {
            return $this->lowerTrim($res['alsoKnownAs']) === $this->lowerTrim($this->activity);
        }

        if (is_array($res['alsoKnownAs'])) {
            $map = Arr::map($res['alsoKnownAs'], function ($value, $key) {
                return trim(strtolower($value));
            });

            $res = in_array($this->activity, $map);

            return $res;
        }

        return false;
    }

    protected function checkActor()
    {
        $fetchActivityUrl = $this->activity.'?cb='.time();
        $res = ActivityPubFetchService::fetchRequest($fetchActivityUrl, true);

        if (! $res || ! isset($res['movedTo']) || empty($res['movedTo'])) {
            return false;
        }

        $actorRes = Helpers::profileFetch($this->activity);
        if (! $actorRes) {
            return false;
        }

        if (is_string($res['movedTo'])) {
            $match = $this->lowerTrim($res['movedTo']) === $this->lowerTrim($this->target);
            if (! $match) {
                return false;
            }

            return $match;
        }

        return false;
    }

    protected function lowerTrim($str)
    {
        return trim(strtolower($str));
    }
}
