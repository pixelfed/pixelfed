<?php

use App\Models\AccountInterstitial;
use App\Models\DirectMessage;
use App\Models\Follower;
use App\Models\Like;
use App\Models\Media;
use App\Models\MediaTag;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Report;
use App\Models\Status;
use App\Models\Story;
use App\Models\User;
use App\Models\UserFilter;
use Illuminate\Database\Eloquent\Relations\Relation;

describe('model namespace migration', function () {
    it('resolves legacy App\\ morph types to App\\Models\\ classes', function () {
        $morphMap = Relation::morphMap();

        expect($morphMap)->toHaveKey('App\Status');
        expect($morphMap['App\Status'])->toBe(Status::class);

        expect($morphMap)->toHaveKey('App\Profile');
        expect($morphMap['App\Profile'])->toBe(Profile::class);

        expect($morphMap)->toHaveKey('App\User');
        expect($morphMap['App\User'])->toBe(User::class);

        expect($morphMap)->toHaveKey('App\DirectMessage');
        expect($morphMap['App\DirectMessage'])->toBe(DirectMessage::class);

        expect($morphMap)->toHaveKey('App\Media');
        expect($morphMap['App\Media'])->toBe(Media::class);

        expect($morphMap)->toHaveKey('App\MediaTag');
        expect($morphMap['App\MediaTag'])->toBe(MediaTag::class);

        expect($morphMap)->toHaveKey('App\Like');
        expect($morphMap['App\Like'])->toBe(Like::class);

        expect($morphMap)->toHaveKey('App\Notification');
        expect($morphMap['App\Notification'])->toBe(Notification::class);

        expect($morphMap)->toHaveKey('App\Story');
        expect($morphMap['App\Story'])->toBe(Story::class);

        expect($morphMap)->toHaveKey('App\Report');
        expect($morphMap['App\Report'])->toBe(Report::class);

        expect($morphMap)->toHaveKey('App\Follower');
        expect($morphMap['App\Follower'])->toBe(Follower::class);

        expect($morphMap)->toHaveKey('App\UserFilter');
        expect($morphMap['App\UserFilter'])->toBe(UserFilter::class);

        expect($morphMap)->toHaveKey('App\AccountInterstitial');
        expect($morphMap['App\AccountInterstitial'])->toBe(AccountInterstitial::class);
    });

    it('resolves morph type to the correct model instance', function () {
        $class = Relation::getMorphedModel('App\Status');
        expect($class)->toBe(Status::class);

        $class = Relation::getMorphedModel('App\Profile');
        expect($class)->toBe(Profile::class);

        $class = Relation::getMorphedModel('App\User');
        expect($class)->toBe(User::class);
    });

    it('models reside in App\\Models namespace', function () {
        expect(Status::class)->toBe(\App\Models\Status::class);
        expect(Profile::class)->toBe(\App\Models\Profile::class);
        expect(User::class)->toBe(\App\Models\User::class);
        expect(Media::class)->toBe(\App\Models\Media::class);
        expect(Like::class)->toBe(\App\Models\Like::class);
        expect(Notification::class)->toBe(\App\Models\Notification::class);
        expect(Story::class)->toBe(\App\Models\Story::class);
        expect(Follower::class)->toBe(\App\Models\Follower::class);
    });
});
