<?php

namespace App\Jobs\FeaturedCollectionPipeline;

use App\Models\FeatureAuthorization;
use App\Services\FeaturedCollectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RevokeFeatureAuthorizationPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    public $tries = 3;

    public $maxExceptions = 1;

    public $backoff = [10, 60];

    public function __construct(protected int $authorizationId) {}

    public function handle(): void
    {
        $auth = FeatureAuthorization::with(['profile', 'actor'])->find($this->authorizationId);

        if (! $auth || ! $auth->isRevoked()) {
            return;
        }

        FeaturedCollectionService::sendDelete($auth);
    }
}
