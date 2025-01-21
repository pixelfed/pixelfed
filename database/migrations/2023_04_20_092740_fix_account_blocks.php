<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Follower;
use App\Notification;
use App\Profile;
use App\UserFilter;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\NotificationService;
use App\Services\RelationshipService;
use App\Services\UserFilterService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        UserFilter::whereFilterType('block')
        ->whereFilterableType('App\Profile')
        ->chunk(10, function($filters) {
        	foreach($filters as $filter) {
        		$actor = Profile::whereNull('status')->find($filter->user_id);
        		if(!$actor) {
        			continue;
        		}
        		$target = Profile::whereNull('status')->find($filter->filterable_id);
        		if(!$target) {
        			continue;
        		}

				$followed = Follower::whereProfileId($target->id)->whereFollowingId($actor->id)->first();
				if($followed) {
					$followed->delete();
					$target->following_count = Follower::whereProfileId($target->id)->count();
					$target->save();
					$actor->followers_count = Follower::whereFollowingId($actor->id)->count();
					$actor->save();
					FollowerService::remove($target->id, $actor->id);
					AccountService::del($actor->id);
					AccountService::del($target->id);
				}

				$following = Follower::whereProfileId($actor->id)->whereFollowingId($target->id)->first();
				if($following) {
					$following->delete();
					$actor->followers_count = Follower::whereFollowingId($target->id)->count();
					$actor->save();
					$target->following_count = Follower::whereProfileId($actor->id)->count();
					$target->save();
					FollowerService::remove($actor->id, $target->id);
					AccountService::del($actor->id);
					AccountService::del($target->id);
				}

				Notification::whereProfileId($actor->id)
					->whereActorId($target->id)
					->get()
					->map(function($n) use($actor) {
						NotificationService::del($actor->id, $n['id']);
						$n->forceDelete();
				});

				UserFilterService::block($actor->id, $target->id);
				RelationshipService::refresh($actor->id, $target->id);
        	}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
