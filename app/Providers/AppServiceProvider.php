<?php

namespace App\Providers;

use App\Observers\{
	AvatarObserver,
	FollowerObserver,
	HashtagFollowObserver,
	LikeObserver,
	NotificationObserver,
	ModLogObserver,
	ProfileObserver,
    StatusHashtagObserver,
    StatusObserver,
	UserObserver,
	UserFilterObserver,
};
use App\{
	Avatar,
	Follower,
	HashtagFollow,
	Like,
	Notification,
	ModLog,
	Profile,
	StatusHashtag,
    Status,
	User,
	UserFilter
};
use Auth, Horizon, URL;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		if(config('instance.force_https_urls', true)) {
			URL::forceScheme('https');
		}

		Schema::defaultStringLength(191);
		Paginator::useBootstrap();
		Avatar::observe(AvatarObserver::class);
		Follower::observe(FollowerObserver::class);
		HashtagFollow::observe(HashtagFollowObserver::class);
		Like::observe(LikeObserver::class);
		Notification::observe(NotificationObserver::class);
		ModLog::observe(ModLogObserver::class);
		Profile::observe(ProfileObserver::class);
		StatusHashtag::observe(StatusHashtagObserver::class);
		User::observe(UserObserver::class);
        Status::observe(StatusObserver::class);
		UserFilter::observe(UserFilterObserver::class);
		Horizon::auth(function ($request) {
			return Auth::check() && $request->user()->is_admin;
		});
		Validator::includeUnvalidatedArrayKeys();

		// Model::preventLazyLoading(true);
	}

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}
}
