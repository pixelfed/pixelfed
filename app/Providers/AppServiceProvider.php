<?php

namespace App\Providers;

use App\Avatar;
use App\Follower;
use App\Like;
use App\ModLog;
use App\Notification;
use App\Observers\AvatarObserver;
use App\Observers\FollowerObserver;
use App\Observers\LikeObserver;
use App\Observers\ModLogObserver;
use App\Observers\NotificationObserver;
use App\Observers\ProfileObserver;
use App\Observers\StatusHashtagObserver;
use App\Observers\StatusObserver;
use App\Observers\UserFilterObserver;
use App\Observers\UserObserver;
use App\Profile;
use App\Status;
use App\StatusHashtag;
use App\User;
use App\UserFilter;
use Auth;
use Horizon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('instance.force_https_urls', true)) {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);
        Paginator::useBootstrap();
        Avatar::observe(AvatarObserver::class);
        Follower::observe(FollowerObserver::class);
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
