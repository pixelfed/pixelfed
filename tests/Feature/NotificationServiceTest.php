<?php

use App\Notification;
use App\Profile;
use App\Services\NotificationService;
use App\Status;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('NotificationService::createNotification', function () {
    it('creates a like notification', function () {
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

    it('creates a comment notification', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $status = Status::factory()->create(['profile_id' => $user->profile_id]);

        $notification = NotificationService::createNotification(
            $user->profile_id,
            $actor->profile_id,
            'comment',
            $status->id,
            Status::class
        );

        expect($notification->action)->toBe('comment');
    });

    it('creates a follow notification', function () {
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

    it('creates a DM notification', function () {
        $user = User::factory()->create();
        $user->refresh();
        $actor = User::factory()->create();
        $actor->refresh();

        $notification = NotificationService::createNotification(
            $user->profile_id,
            $actor->profile_id,
            'dm',
            999,
            'App\DirectMessage'
        );

        expect($notification->action)->toBe('dm');
    });

    it('creates a group join approved notification', function () {
        $user = User::factory()->create();
        $user->refresh();

        $notification = NotificationService::createNotification(
            $user->profile_id,
            $user->profile_id,
            'group.join.approved',
            1,
            'App\Models\Group'
        );

        expect($notification->action)->toBe('group.join.approved');
        expect($notification->exists)->toBeTrue();
    });

    it('creates a group join rejected notification', function () {
        $user = User::factory()->create();
        $user->refresh();

        $notification = NotificationService::createNotification(
            $user->profile_id,
            $user->profile_id,
            'group.join.rejected',
            1,
            'App\Models\Group'
        );

        expect($notification->action)->toBe('group.join.rejected');
    });

    it('allows null action', function () {
        $user = User::factory()->create();
        $user->refresh();

        $notification = NotificationService::createNotification(
            $user->profile_id,
            $user->profile_id,
            null,
            1,
            'App\Models\Group'
        );

        expect($notification->action)->toBeNull();
        expect($notification->exists)->toBeTrue();
    });

    it('persists to the database', function () {
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

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'profile_id' => $user->profile_id,
            'action' => 'like',
        ]);
    });
});

describe('NotificationService::firstOrCreateNotification', function () {
    it('creates a share/boost notification', function () {
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

    it('creates separate notifications for different actors boosting same post', function () {
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
