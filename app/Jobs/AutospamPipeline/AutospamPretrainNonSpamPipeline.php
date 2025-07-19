<?php

namespace App\Jobs\AutospamPipeline;

use App\Services\AutospamService;
use App\Status;
use App\Util\Lexer\Classifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class AutospamPretrainNonSpamPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $classifier;

    public $accounts;

    /**
     * Create a new job instance.
     */
    public function __construct($accounts)
    {
        $this->accounts = $accounts;
        $this->classifier = new Classifier;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $classifier = $this->classifier;
        $accounts = $this->accounts;

        foreach ($accounts as $acct) {
            Status::whereNotNull('caption')
                ->whereScope('public')
                ->whereProfileId($acct->id)
                ->inRandomOrder()
                ->take(400)
                ->pluck('caption')
                ->each(function ($c) use ($classifier) {
                    $classifier->learn($c, 'ham');
                });
        }

        Storage::put(AutospamService::MODEL_HAM_PATH, $classifier->export());

        AutospamUpdateCachedDataPipeline::dispatch()->delay(5);
    }
}
