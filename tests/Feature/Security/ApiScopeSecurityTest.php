<?php

use App\Models\Hashtag;
use App\Models\HashtagFollow;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| API Scope Security Tests
|--------------------------------------------------------------------------
|
| These tests verify that API tokens with limited scopes cannot perform
| actions outside their granted permissions.
|
*/

describe('read-only token cannot write', function () {
    it('cannot follow with read-only scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();
        Passport::actingAs($user, ['read']);

        $this->postJson("/api/v1/accounts/{$target->profile_id}/follow")
            ->assertForbidden();
    });

    it('cannot favourite with read-only scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->create(['type' => 'photo', 'scope' => 'public']);
        Passport::actingAs($user, ['read']);

        $this->postJson("/api/v1/statuses/{$status->id}/favourite")
            ->assertForbidden();
    });

    it('cannot delete a status with read-only scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
        ]);
        Passport::actingAs($user, ['read']);

        $this->deleteJson("/api/v1/statuses/{$status->id}")
            ->assertForbidden();
    });

    it('cannot mute with read-only scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();
        Passport::actingAs($user, ['read']);

        $this->postJson("/api/v1/accounts/{$target->profile_id}/mute")
            ->assertForbidden();
    });

    it('cannot block with read-only scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();
        Passport::actingAs($user, ['read']);

        $this->postJson("/api/v1/accounts/{$target->profile_id}/block")
            ->assertForbidden();
    });
});

describe('write token can read and write', function () {
    it('can read verify credentials with write scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read', 'write']);

        $this->getJson('/api/v1/accounts/verify_credentials')
            ->assertOk();
    });

    it('can follow with write scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();
        Passport::actingAs($user, ['read', 'write', 'follow']);

        $this->postJson("/api/v1/accounts/{$target->profile_id}/follow")
            ->assertOk();
    });
});

describe('non-admin tokens cannot access admin endpoints', function () {
    it('rejects admin:read scope for non-admin user', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();
        Passport::actingAs($user, ['admin:read']);

        $this->getJson('/api/admin/stats')
            ->assertNotFound();
    });

    it('rejects admin:write scope for non-admin user', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();
        Passport::actingAs($user, ['admin:write']);

        $this->postJson('/api/v1/admin/domain_blocks', [
            'domain' => 'test.example.com',
        ])->assertForbidden();
    });
});

describe('cross-user access prevention', function () {
    it('cannot delete another users status', function () {
        $user = User::factory()->create();
        $user->refresh();
        $other = User::factory()->create();
        $other->refresh();
        $status = Status::factory()->create([
            'profile_id' => $other->profile_id,
            'type' => 'photo',
        ]);
        Passport::actingAs($user, ['write']);

        $this->deleteJson("/api/v1/statuses/{$status->id}")
            ->assertNotFound();
    });

    it('cannot access private status without following', function () {
        $user = User::factory()->create();
        $user->refresh();
        $other = User::factory()->create();
        $other->refresh();
        $status = Status::factory()->private()->create([
            'profile_id' => $other->profile_id,
            'type' => 'photo',
        ]);
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/statuses/{$status->id}")
            ->assertForbidden();
    });
});

describe('tag endpoints enforce token scopes', function () {
    it('cannot follow a hashtag without the follow scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $tag = Hashtag::create(['name' => 'photography', 'slug' => 'photography']);
        Passport::actingAs($user, ['read']);

        $this->postJson("/api/v1/tags/{$tag->slug}/follow")
            ->assertForbidden();
    });

    it('cannot unfollow a hashtag without the follow scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $tag = Hashtag::create(['name' => 'travel', 'slug' => 'travel']);
        HashtagFollow::create([
            'user_id' => $user->id,
            'profile_id' => $user->profile_id,
            'hashtag_id' => $tag->id,
        ]);
        Passport::actingAs($user, ['read']);

        $this->postJson("/api/v1/tags/{$tag->slug}/unfollow")
            ->assertForbidden();
    });

    it('cannot read a hashtag without the read scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $tag = Hashtag::create(['name' => 'coffee', 'slug' => 'coffee']);
        Passport::actingAs($user, ['write']);

        $this->getJson("/api/v1/tags/{$tag->slug}")
            ->assertForbidden();
    });

    it('cannot list followed tags without the read scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['write']);

        $this->getJson('/api/v1/followed_tags')
            ->assertForbidden();
    });

    it('can follow a hashtag with the follow scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $tag = Hashtag::create(['name' => 'sunsets', 'slug' => 'sunsets']);
        Passport::actingAs($user, ['read', 'write', 'follow']);

        $this->postJson("/api/v1/tags/{$tag->slug}/follow")
            ->assertOk();
    });

    it('can unfollow a hashtag with the follow scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $tag = Hashtag::create(['name' => 'mountains', 'slug' => 'mountains']);
        HashtagFollow::create([
            'user_id' => $user->id,
            'profile_id' => $user->profile_id,
            'hashtag_id' => $tag->id,
        ]);
        Passport::actingAs($user, ['read', 'write', 'follow']);

        $this->postJson("/api/v1/tags/{$tag->slug}/unfollow")
            ->assertOk();
    });

    it('can read a hashtag with the read scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $tag = Hashtag::create(['name' => 'forests', 'slug' => 'forests']);
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/tags/{$tag->slug}")
            ->assertOk();
    });

    it('can list followed tags with the read scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/followed_tags')
            ->assertOk();
    });
});
