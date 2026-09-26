<?php

namespace App\Jobs\AutospamPipeline;

use App\Models\AccountInterstitial;
use App\Models\Status;
use App\Services\AutospamService;
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

        $aiCount = AccountInterstitial::whereItemType(Status::class)
            ->whereIsSpam(true)
            ->count();

        if ($aiCount < 100) {
            return;
        }

        AccountInterstitial::whereItemType(Status::class)
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

        $export = $classifier->export();

        // If every sampled status was deleted or had a null caption the model
        // learned nothing, and an empty model makes Classifier::most() return
        // null (crashing AutospamService::check() on every new post). Skip
        // saving so the previous, valid model stays in place.
        $decoded = json_decode($export, true);
        if (empty($decoded['documents']['spam'])) {
            return;
        }

        Storage::put(AutospamService::MODEL_SPAM_PATH, $export);

        AutospamUpdateCachedDataPipeline::dispatch()->delay(5);
    }
}
