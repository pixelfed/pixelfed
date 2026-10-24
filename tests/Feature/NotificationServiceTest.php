<?php

use App\Models\Group;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(LazilyRefreshDatabase::class);

/**
 * Persist a like notification while forcing the raw item_type string, so tests
 * can reproduce legacy ('App\Status') vs current (App\Models\Status) rows.
 */
function makeLikeNotificationWithType(int $profileId, int $actorId, int $statusId, string $itemType): Notification
{
    $n = new Notification;
    $n->profile_id = $profileId;
    $n->actor_id = $actorId;
    $n->action = 'like';
    $n->item_id = $statusId;
    $n->item_type = $itemType;
    $n->save();

    // Mirror createNotification's cache registration so the page walk sees it.
    NotificationService::setNotification($n);
    NotificationService::set($n->profile_id, $n->id);

    return $n;
}

describe('NotificationService::createNotification', function () {
    it('creates a notification and persists it', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $status = Status::factory()->create(['profile_id' => $user->profile_id]);

        $notification = NotificationService::createNotification(
            $user->profile_id,
            $actor->profile_id,
            'like',
            $status->id,
            Status::class
        );

        expect($notification)->toBeInstanceOf(Notification::class);
        expect($notification->exists)->toBeTrue();
        expect($notification->profile_id)->toBe($user->profile_id);
        expect($notification->actor_id)->toBe($actor->profile_id);
        expect($notification->action)->toBe('like');
        expect($notification->item_id)->toBe($status->id);
        expect($notification->item_type)->toBe(Status::class);
    });

    it('creates a follow notification with Profile item type', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $notification = NotificationService::createNotification(
            $user->profile_id,
            $actor->profile_id,
            'follow',
            $user->profile_id,
            Profile::class
        );

        expect($notification->action)->toBe('follow');
        expect($notification->item_type)->toBe(Profile::class);
    });

    it('allows null action', function () {
        $user = User::factory()->create();
        $user->refresh();

        $notification = NotificationService::createNotification(
            $user->profile_id,
            $user->profile_id,
            null,
            1,
            Group::class
        );

        expect($notification->action)->toBeNull();
        expect($notification->exists)->toBeTrue();
    });
});

describe('NotificationService::firstOrCreateNotification', function () {
    it('creates a share notification', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $status = Status::factory()->create(['profile_id' => $user->profile_id]);

        $notification = NotificationService::firstOrCreateNotification(
            $user->profile_id,
            $actor->profile_id,
            'share',
            $status->id,
            Status::class
        );

        expect($notification)->toBeInstanceOf(Notification::class);
        expect($notification->action)->toBe('share');
        expect($notification->wasRecentlyCreated)->toBeTrue();
    });

    it('does not create a duplicate when the same actor boosts twice', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $status = Status::factory()->create(['profile_id' => $user->profile_id]);

        $first = NotificationService::firstOrCreateNotification(
            $user->profile_id,
            $actor->profile_id,
            'share',
            $status->id,
            Status::class
        );

        $second = NotificationService::firstOrCreateNotification(
            $user->profile_id,
            $actor->profile_id,
            'share',
            $status->id,
            Status::class
        );

        expect($first->id)->toBe($second->id);
        expect($second->wasRecentlyCreated)->toBeFalse();

        $count = Notification::where('profile_id', $user->profile_id)
            ->where('action', 'share')
            ->where('item_id', $status->id)
            ->count();

        expect($count)->toBe(1);
    });

    it('creates separate notifications for different actors', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor1 = User::factory()->create();
        $actor1->refresh();
        $actor2 = User::factory()->create();
        $actor2->refresh();

        $status = Status::factory()->create(['profile_id' => $user->profile_id]);

        $first = NotificationService::firstOrCreateNotification(
            $user->profile_id,
            $actor1->profile_id,
            'share',
            $status->id,
            Status::class
        );

        $second = NotificationService::firstOrCreateNotification(
            $user->profile_id,
            $actor2->profile_id,
            'share',
            $status->id,
            Status::class
        );

        expect($first->id)->not->toBe($second->id);
        expect($first->actor_id)->toBe($actor1->profile_id);
        expect($second->actor_id)->toBe($actor2->profile_id);
    });

    it('is idempotent for mentions', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $status = Status::factory()->create(['profile_id' => $actor->profile_id]);

        $first = NotificationService::firstOrCreateNotification(
            $user->profile_id,
            $actor->profile_id,
            'mention',
            $status->id,
            Status::class
        );

        $second = NotificationService::firstOrCreateNotification(
            $user->profile_id,
            $actor->profile_id,
            'mention',
            $status->id,
            Status::class
        );

        expect($first->id)->toBe($second->id);
    });
});

describe('NotificationService::getMaxPage renderable filtering (pixelfed#7195)', function () {
    it('excludes a like notification whose status has been deleted', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $status = Status::factory()->create(['profile_id' => $user->profile_id, 'scope' => 'public']);

        $n = NotificationService::createNotification(
            $user->profile_id,
            $actor->profile_id,
            'like',
            $status->id,
            Status::class
        );

        // Delete the underlying status so it can no longer be hydrated.
        $status->delete();
        NotificationService::del($user->profile_id, $n->id);

        $page = NotificationService::getMaxPage($user->profile_id, $n->id + 1, 20);
        $ids = collect($page['data'])->pluck('id')->map(fn ($v) => (string) $v)->all();

        expect($ids)->not->toContain((string) $n->id);
    });

    it('includes a follow notification (no status required)', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $n = NotificationService::createNotification(
            $user->profile_id,
            $actor->profile_id,
            'follow',
            $actor->profile_id,
            Profile::class
        );

        $page = NotificationService::getMaxPage($user->profile_id, $n->id + 1, 20);
        $ids = collect($page['data'])->pluck('id')->map(fn ($v) => (string) $v)->all();

        expect($ids)->toContain((string) $n->id);
    });

    it('includes a like notification whose status still exists', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $status = Status::factory()->create(['profile_id' => $user->profile_id, 'scope' => 'public']);

        $n = NotificationService::createNotification(
            $user->profile_id,
            $actor->profile_id,
            'like',
            $status->id,
            Status::class
        );

        $page = NotificationService::getMaxPage($user->profile_id, $n->id + 1, 20);
        $ids = collect($page['data'])->pluck('id')->map(fn ($v) => (string) $v)->all();

        expect($ids)->toContain((string) $n->id);
    });
});

describe('NotificationService legacy item_type alias (pixelfed#7195)', function () {
    it('hydrates and includes a legacy App\\Status like notification', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $status = Status::factory()->create(['profile_id' => $user->profile_id, 'scope' => 'public']);

        // Legacy row: item_type stored as the pre-migration 'App\Status'.
        $n = makeLikeNotificationWithType($user->profile_id, $actor->profile_id, $status->id, 'App\Status');

        $page = NotificationService::getMaxPage($user->profile_id, $n->id + 1, 20);
        $item = collect($page['data'])->firstWhere('id', (string) $n->id)
            ?? collect($page['data'])->firstWhere('id', $n->id);

        expect($item)->not->toBeNull();
        expect($item['status'] ?? null)->not->toBeNull();
        expect((string) $item['status']['id'])->toBe((string) $status->id);
    });

    it('excludes a legacy App\\Status like notification whose status was deleted', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $status = Status::factory()->create(['profile_id' => $user->profile_id, 'scope' => 'public']);
        $n = makeLikeNotificationWithType($user->profile_id, $actor->profile_id, $status->id, 'App\Status');

        $status->delete();
        NotificationService::del($user->profile_id, $n->id);

        $page = NotificationService::getMaxPage($user->profile_id, $n->id + 1, 20);
        $ids = collect($page['data'])->pluck('id')->map(fn ($v) => (string) $v)->all();

        expect($ids)->not->toContain((string) $n->id);
    });
});

describe('NotificationService::renderableFilter unexpected type warning', function () {
    it('logs a warning for a notification type outside the known set', function () {
        Log::spy();

        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        // An unknown action -> replaceTypeVerb passes it through unchanged, so
        // the page walk sees a type not in the known list.
        $n = NotificationService::createNotification(
            $user->profile_id,
            $actor->profile_id,
            'totally-unknown-type',
            $actor->profile_id,
            Profile::class
        );

        NotificationService::getMaxPage($user->profile_id, $n->id + 1, 20);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message) => str_contains($message, 'unexpected notification type'))
            ->atLeast()->once();
    });

    it('does not warn for a known follow notification', function () {
        Log::spy();

        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $n = NotificationService::createNotification(
            $user->profile_id,
            $actor->profile_id,
            'follow',
            $actor->profile_id,
            Profile::class
        );

        NotificationService::getMaxPage($user->profile_id, $n->id + 1, 20);

        Log::shouldNotHaveReceived('warning');
    });
});

describe('NotificationService pagination termination (pixelfed#7195)', function () {
    it('returns empty data when every candidate is an unrenderable status notification', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        // Two like notifications whose statuses are all deleted -> nothing is
        // renderable, so the page must come back empty and let the client stop
        // paginating instead of looping forever.
        $notifs = [];
        foreach (range(1, 2) as $i) {
            $status = Status::factory()->create(['profile_id' => $user->profile_id, 'scope' => 'public']);
            $n = makeLikeNotificationWithType($user->profile_id, $actor->profile_id, $status->id, Status::class);
            $status->delete();
            NotificationService::del($user->profile_id, $n->id);
            $notifs[] = $n;
        }

        $topId = max(array_map(fn ($n) => $n->id, $notifs)) + 1;
        $page = NotificationService::getMaxPage($user->profile_id, $topId, 20);

        expect($page['data'])->toBeEmpty();
    });
});
