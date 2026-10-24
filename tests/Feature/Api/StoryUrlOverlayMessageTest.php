<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Story url overlay validation message must match the https-only rule
|--------------------------------------------------------------------------
|
| The url overlay branch accepts only https but the 422 message claimed
| "HTTP and HTTPS". The message must match the enforcement (https only).
|
*/

beforeEach(function () {
    config(['instance.stories.enabled' => true]);
    config(['instance.enable_cc' => false]);
    Storage::fake('local');
});

function storyImage(): UploadedFile
{
    // Stories require a 1080x1920 jpeg/png at least 10KB.
    $img = imagecreatetruecolor(1080, 1920);
    imagefilledrectangle($img, 0, 0, 1079, 1919, imagecolorallocate($img, 100, 120, 200));
    $path = tempnam(sys_get_temp_dir(), 'story').'.jpg';
    imagejpeg($img, $path, 100);
    imagedestroy($img);

    return new UploadedFile($path, 'story.jpg', 'image/jpeg', null, true);
}

it('rejects an http url overlay with an https-only message', function () {
    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['write']);

    $response = $this->postJson('/api/v1.2/stories/publish', [
        'image' => storyImage(),
        'overlays' => [
            [
                'type' => 'url',
                'content' => 'http://example.test/path',
                'x' => 0.5,
                'y' => 0.5,
            ],
        ],
    ]);

    $response->assertStatus(422);
    expect($response->getContent())->toContain('Only HTTPS URLs are allowed.');
    expect($response->getContent())->not->toContain('HTTP and HTTPS');
});
