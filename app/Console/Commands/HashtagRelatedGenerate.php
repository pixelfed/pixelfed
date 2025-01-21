<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Hashtag;
use App\StatusHashtag;
use App\Models\HashtagRelated;
use App\Services\HashtagRelatedService;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\confirm;

class HashtagRelatedGenerate extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hashtag-related-generate {tag}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'tag' => 'Which hashtag should we generate related tags for?',
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tag = $this->argument('tag');
        $hashtag = Hashtag::whereName($tag)->orWhere('slug', $tag)->first();
        if(!$hashtag) {
            $this->error('Hashtag not found, aborting...');
            exit;
        }

        $exists = HashtagRelated::whereHashtagId($hashtag->id)->exists();

        if($exists) {
            $confirmed = confirm('Found existing related tags, do you want to regenerate them?');
            if(!$confirmed) {
                $this->error('Aborting...');
                exit;
            }
        }

        $this->info('Looking up #' . $tag . '...');

        $tags = StatusHashtag::whereHashtagId($hashtag->id)->count();
        if(!$tags || $tags < 100) {
            $this->error('Not enough posts found to generate related hashtags!');
            exit;
        }

        $this->info('Found ' . $tags . ' posts that use that hashtag');
        $related = collect(HashtagRelatedService::fetchRelatedTags($tag));

        $selected = multiselect(
            label: 'Which tags do you want to generate?',
            options: $related->pluck('name'),
            required: true,
        );

        $filtered = $related->filter(fn($i) => in_array($i['name'], $selected))->all();
        $agg_score = $related->filter(fn($i) => in_array($i['name'], $selected))->sum('related_count');

        HashtagRelated::updateOrCreate([
            'hashtag_id' => $hashtag->id,
        ], [
            'related_tags' => array_values($filtered),
            'agg_score' => $agg_score,
            'last_calculated_at' => now()
        ]);

        $this->info('Finished!');
    }
}
