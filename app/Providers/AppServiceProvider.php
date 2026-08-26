<?php

namespace App\Providers;

use App\Avatar;
use App\Follower;
use App\HashtagFollow;
use App\Like;
use App\ModLog;
use App\Notification;
use App\Observers\AvatarObserver;
use App\Observers\FollowerObserver;
use App\Observers\HashtagFollowObserver;
use App\Observers\LikeObserver;
use App\Observers\ModLogObserver;
use App\Observers\NotificationObserver;
use App\Observers\ProfileObserver;
use App\Observers\StatusHashtagObserver;
use App\Observers\StatusObserver;
use App\Observers\UserFilterObserver;
use App\Observers\UserObserver;
use App\Profile;
use App\Services\AccountService;
use App\Services\UserOidcService;
use App\Status;
use App\StatusHashtag;
use App\User;
use App\UserFilter;
use Auth;
use Horizon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

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

        URL::forceRootUrl(config('app.url'));

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

        Gate::define('viewPulse', function (User $user) {
            return $user->is_admin === 1;
        });

        if (config('pulse.enabled', false)) {
            Pulse::user(function ($user) {
                $acct = AccountService::get($user->profile_id, true);

                return $acct ? [
                    'name' => $acct['username'],
                    'extra' => $user->email,
                    'avatar' => $acct['avatar'],
                ] : [
                    'name' => $user->username,
                    'extra' => 'DELETED',
                    'avatar' => '/storage/avatars/default.jpg',
                ];
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserOidcService::class, function () {
            return UserOidcService::build();
        });
    }
}
