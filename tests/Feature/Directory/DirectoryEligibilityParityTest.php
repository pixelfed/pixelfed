<?php

use App\Http\Controllers\PixelfedDirectoryController;
use App\Models\ConfigCache;
use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| PixelfedDirectoryController::buildListing() eligibility parity
|--------------------------------------------------------------------------
|
| The submission payload's is_eligible ignored the same gates the admin
| panel enforces (open/curated registration, oauth, activitypub, and the
| feature_config validator), so the directory could receive is_eligible=true
| for an instance the admin panel considered ineligible. is_eligible must
| honor every gate and be a strict boolean.
|
*/

function setDirConfig(string $key, $value): void
{
    config([$key => $value]);
    ConfigCache::updateOrCreate(['k' => $key], ['v' => $value === false ? '' : (string) $value]);
}

function seedEligibleDirectory(): void
{
    $admin = User::factory()->create();
    $admin->refresh();
    $profile = $admin->profile;
    $profile->is_private = false;
    $profile->save();
    $pid = $profile->id;

    // Three public photo posts, each with a media attachment so
    // StatusService::get() yields media_attachments[0]['url'].
    $ids = [];
    foreach (range(1, 3) as $i) {
        $status = Status::factory()->create([
            'profile_id' => $pid,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
        ]);
        Media::create([
            'status_id' => $status->id,
            'profile_id' => $pid,
            'user_id' => $admin->id,
            'media_path' => "public/test/{$i}.jpg",
            'mime' => 'image/jpeg',
            'order' => 1,
        ]);
        $ids[] = $status->id;
    }

    config(['pixelfed.directory' => [
        'admin' => $pid,
        'summary' => 'A friendly instance for photographers.',
        'favourite_posts' => $ids,
        'contact_email' => 'admin@example.test',
        'privacy_pledge' => true,
        'location' => 'US',
    ]]);
    ConfigCache::updateOrCreate(['k' => 'app.short_description'], ['v' => 'A friendly instance for photographers.']);

    // Feature config values that pass the AdminDirectoryController validator.
    setDirConfig('pixelfed.media_types', 'image/jpeg,image/png,video/mp4');
    setDirConfig('pixelfed.image_quality', 80);
    setDirConfig('pixelfed.optimize_image', true);
    setDirConfig('pixelfed.max_photo_size', 30000);
    setDirConfig('pixelfed.max_caption_length', 1000);
    setDirConfig('pixelfed.max_altext_length', 2000);
    setDirConfig('pixelfed.enforce_account_limit', false);
    setDirConfig('pixelfed.max_account_size', 2000000);
    setDirConfig('pixelfed.max_album_length', 10);
    setDirConfig('pixelfed.account_deletion', true);

    // Registration + oauth + activitypub gates.
    setDirConfig('pixelfed.open_registration', true);
    setDirConfig('instance.curated_registration.enabled', false);
    setDirConfig('federation.activitypub.enabled', true);
    setDirConfig('pixelfed.oauth_enabled', true);
    config(['passport.public_key' => 'test-public-key']);
    config(['passport.private_key' => 'test-private-key']);
}

it('reports eligible when every requirement is satisfied', function () {
    seedEligibleDirectory();

    $res = (new PixelfedDirectoryController)->buildListing();

    expect($res['is_eligible'])->toBeTrue();
});

it('always returns is_eligible as a strict boolean', function () {
    seedEligibleDirectory();

    $res = (new PixelfedDirectoryController)->buildListing();

    expect($res['is_eligible'])->toBeBool();
});

it('is ineligible when activitypub is disabled even if content requirements pass', function () {
    seedEligibleDirectory();
    setDirConfig('federation.activitypub.enabled', false);

    $res = (new PixelfedDirectoryController)->buildListing();

    expect($res['is_eligible'])->toBeFalse();
});

it('is ineligible when neither open nor curated registration is enabled', function () {
    seedEligibleDirectory();
    setDirConfig('pixelfed.open_registration', false);
    setDirConfig('instance.curated_registration.enabled', false);

    $res = (new PixelfedDirectoryController)->buildListing();

    expect($res['is_eligible'])->toBeFalse();
});

it('is ineligible when the feature_config validator fails', function () {
    seedEligibleDirectory();
    // Drop png support -> media_types validation fails.
    setDirConfig('pixelfed.media_types', 'image/jpeg,video/mp4');

    $res = (new PixelfedDirectoryController)->buildListing();

    expect($res['is_eligible'])->toBeFalse();
});
