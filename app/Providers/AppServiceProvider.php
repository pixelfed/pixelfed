<?php

namespace App\Providers;

use App\Observers\{
	AvatarObserver,
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

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		URL::forceScheme('https');
		Schema::defaultStringLength(191);
		Paginator::useBootstrap();
		Avatar::observe(AvatarObserver::class);
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
