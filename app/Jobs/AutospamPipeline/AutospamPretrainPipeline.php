<?php

namespace App\Jobs\AutospamPipeline;

use App\AccountInterstitial;
use App\Services\AutospamService;
use App\Status;
use App\Util\Lexer\Classifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class AutospamPretrainPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $classifier;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->classifier = new Classifier;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $classifier = $this->classifier;

        $aiCount = AccountInterstitial::whereItemType('App\Status')
            ->whereIsSpam(true)
            ->count();

        if ($aiCount < 100) {
            return;
        }

        AccountInterstitial::whereItemType('App\Status')
            ->whereIsSpam(true)
            ->inRandomOrder()
            ->take(config('autospam.nlp.spam_sample_limit'))
            ->pluck('item_id')
            ->each(function ($ai) use ($classifier) {
                $status = Status::whereNotNull('caption')->find($ai);
                if (! $status) {
                    return;
                }
                $classifier->learn($status->caption, 'spam');
            });

        Storage::put(AutospamService::MODEL_SPAM_PATH, $classifier->export());

        AutospamUpdateCachedDataPipeline::dispatch()->delay(5);
    }
}
