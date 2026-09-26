<?php

use App\Jobs\AutospamPipeline\AutospamPretrainPipeline;
use App\Jobs\AutospamPipeline\AutospamUpdateCachedDataPipeline;
use App\Models\Status;
use App\Models\User;
use App\Services\AutospamService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| AutospamPretrainPipeline must not export an empty model
|--------------------------------------------------------------------------
|
| The job saved the classifier unconditionally. When every sampled spam
| status was deleted / had a null caption, it exported a model with no
| documents, and AutospamService::check() then crashed with a TypeError
| (Classifier::most() returning null). The job now skips saving when nothing
| was learned, leaving any prior valid model in place.
|
*/

function seedSpamInterstitials(array $itemIds): void
{
    $rows = [];
    foreach ($itemIds as $id) {
        $rows[] = [
            'item_id' => $id,
            'item_type' => Status::class,
            'is_spam' => true,
            'type' => 'post.autospam',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    DB::table('account_interstitials')->insert($rows);
}

beforeEach(function () {
    Storage::fake('local');
    config(['autospam.nlp.spam_sample_limit' => 200]);
    Queue::fake();
});

it('does not write a spam model when nothing was learned', function () {
    // 120 spam interstitials (> the 100 minimum), all pointing at ids that
    // have no corresponding Status row.
    seedSpamInterstitials(range(900000, 900119));

    (new AutospamPretrainPipeline)->handle();

    expect(Storage::exists(AutospamService::MODEL_SPAM_PATH))->toBeFalse();
    Queue::assertNotPushed(AutospamUpdateCachedDataPipeline::class);
});

it('does not overwrite an existing valid model with an empty one', function () {
    seedSpamInterstitials(range(900000, 900119));

    // A prior, valid model exists on disk.
    $existing = json_encode(['documents' => ['spam' => 5], 'words' => ['spam' => ['buy' => 3]]]);
    Storage::put(AutospamService::MODEL_SPAM_PATH, $existing);

    (new AutospamPretrainPipeline)->handle();

    // The prior model must be untouched.
    expect(Storage::get(AutospamService::MODEL_SPAM_PATH))->toBe($existing);
});

it('writes a non-empty model when spam captions are learned', function () {
    $user = User::factory()->create();
    $user->refresh();

    $ids = [];
    foreach (range(1, 120) as $i) {
        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'caption' => 'buy cheap followers now click here spam link '.$i,
        ]);
        $ids[] = $status->id;
    }
    seedSpamInterstitials($ids);

    (new AutospamPretrainPipeline)->handle();

    expect(Storage::exists(AutospamService::MODEL_SPAM_PATH))->toBeTrue();

    $model = json_decode(Storage::get(AutospamService::MODEL_SPAM_PATH), true);
    expect($model['documents']['spam'] ?? 0)->toBeGreaterThan(0);
    expect($model['words']['spam'] ?? [])->not->toBeEmpty();

    Queue::assertPushed(AutospamUpdateCachedDataPipeline::class);
});
