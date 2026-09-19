<?php

namespace App\Jobs\QuotePipeline;

use App\Models\QuoteAuthorization;
use App\Services\QuoteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RevokeQuoteAuthorizationPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    public $tries = 3;

    public $maxExceptions = 1;

    public $backoff = [10, 60];

    public function __construct(protected int $authorizationId) {}

    public function handle(): void
    {
        $auth = QuoteAuthorization::with(['profile', 'actor', 'status'])->find($this->authorizationId);

        if (! $auth || ! $auth->isRevoked()) {
            return;
        }

        QuoteService::sendDelete($auth);
    }
}
