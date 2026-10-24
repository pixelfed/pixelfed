<?php

use App\Jobs\StatusPipeline\StatusEntityLexer;
use App\Models\Hashtag;
use App\Models\Status;
use App\Models\StatusHashtag;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Hashtag slug collisions for distinct tag names
|--------------------------------------------------------------------------
|
| Str::slug($tag, '-', false) strips characters outside [\pL\pN\s] instead
| of mapping them to the separator, so distinct tags like #foo·bar (middle
| dot) and #foobar both reduce to the base slug `foobar`. Keying the
| find-or-create on the unique `name` column (with a collision-avoiding slug)
| keeps them as separate rows, and tags that reduce to an empty slug are
| skipped rather than aggregated under slug=''.
|
*/

function makeStatusWithCaptionForSlugCollision(int $profileId, string $caption): Status
{
    $status = Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'caption' => $caption,
    ]);

    (new StatusEntityLexer($status))->handle();

    return $status;
}

it('keeps distinct tags that slugify to the same base as separate rows', function () {
    $user = User::factory()->create();
    $user->refresh();

    $middleDot = json_decode('"\u00b7"');

    $statusPlain = makeStatusWithCaptionForSlugCollision($user->profile_id, 'hello #foobar');
    $statusDotted = makeStatusWithCaptionForSlugCollision($user->profile_id, 'hello #foo'.$middleDot.'bar');

    $plainName = 'foobar';
    $dottedName = 'foo'.$middleDot.'bar';

    expect(Hashtag::where('name', $plainName)->count())->toBe(1);
    expect(Hashtag::where('name', $dottedName)->count())->toBe(1);

    $plainHashtag = Hashtag::where('name', $plainName)->first();
    $dottedHashtag = Hashtag::where('name', $dottedName)->first();

    // Two distinct rows resolved to non-colliding slugs.
    expect($plainHashtag->id)->not->toBe($dottedHashtag->id);
    expect([$plainHashtag->slug, $dottedHashtag->slug])->toEqualCanonicalizing(['foobar', 'foobar-1']);

    // Each status links to its own hashtag.
    expect(StatusHashtag::where('status_id', $statusPlain->id)->where('hashtag_id', $plainHashtag->id)->exists())->toBeTrue();
    expect(StatusHashtag::where('status_id', $statusDotted->id)->where('hashtag_id', $dottedHashtag->id)->exists())->toBeTrue();
});

it('does not create an empty-slug row for a tag made only of combining marks', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Two combining acute accents; Str::slug strips them to an empty string.
    $combining = json_decode('"\u0301\u0301"');

    makeStatusWithCaptionForSlugCollision($user->profile_id, 'hello #'.$combining);

    expect(Hashtag::where('slug', '')->count())->toBe(0);
});

it('creates one row for a normal tag and reuses it for the same tag', function () {
    $user = User::factory()->create();
    $user->refresh();

    $statusA = makeStatusWithCaptionForSlugCollision($user->profile_id, 'a #sunset day');
    $statusB = makeStatusWithCaptionForSlugCollision($user->profile_id, 'another #sunset day');

    expect(Hashtag::where('name', 'sunset')->count())->toBe(1);

    $hashtag = Hashtag::where('name', 'sunset')->first();

    expect(StatusHashtag::where('status_id', $statusA->id)->where('hashtag_id', $hashtag->id)->exists())->toBeTrue();
    expect(StatusHashtag::where('status_id', $statusB->id)->where('hashtag_id', $hashtag->id)->exists())->toBeTrue();
});
