<?php

use App\Models\Status;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['instance.hide_nsfw_on_public_feeds' => false]);
});

function networkCandidate(string $scope): Status
{
    $status = new Status;
    $status->in_reply_to_id = null;
    $status->reblog_of_id = null;
    $status->scope = $scope;
    $status->visibility = $scope;
    $status->type = 'photo';
    $status->is_nsfw = false;
    $status->created_at = now()->subMinutes(5);

    return $status;
}

it('treats a public remote post as eligible', function () {
    expect(Helpers::isEligibleForNetwork(networkCandidate('public')))->toBeTrue();
});

it('rejects unlisted and private posts', function () {
    expect(Helpers::isEligibleForNetwork(networkCandidate('unlisted')))->toBeFalse()
        ->and(Helpers::isEligibleForNetwork(networkCandidate('private')))->toBeFalse();
});
