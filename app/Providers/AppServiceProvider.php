<?php

namespace App\Providers;

use App\Avatar;
use App\Follower;
use App\HashtagFollow;
use App\Like;
use App\Models\OAuthToken;
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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
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

        Passport::$clientUuids = false;
        Passport::authorizationView('auth.oauth.authorize');

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

        RateLimiter::for('app-signup', function (Request $request) {
            return Limit::perDay(100)->by($request->ip());
        });

        RateLimiter::for('app-code-verify', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));

            $emailKey = $email !== ''
                ? hash('sha256', $email)
                : 'missing';

            return [
                Limit::perHour(20)->by('app-code-verify:ip:'.$request->ip()),
                Limit::perHour(10)->by('app-code-verify:email:'.$emailKey),
            ];
        });

        RateLimiter::for('app-code-resend', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });

        RateLimiter::for('account-lookup', function (Request $request) {
            return Limit::perDay(50)->by($request->ip());
        });

        RateLimiter::for('oauth-pat', function (Request $request) {
            $user = $request->user('web');

            $actor = $user
                ? 'u:'.$user->getAuthIdentifier()
                : 'ip:'.$request->ip();

            $tooMany = function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Too many requests',
                    'retry_after' => isset($headers['Retry-After'])
                        ? (int) $headers['Retry-After']
                        : null,
                    'debug' => 'oauth-pat limiter hit',
                    'headers' => $headers,
                ], 429)->withHeaders($headers)->header('X-Debug-Limiter', 'oauth-pat');
            };

            return [
                Limit::perMinute(3)
                    ->by("minute:{$actor}"),

                Limit::perHour(15)
                    ->by("hour:{$actor}"),

                Limit::perDay(20)
                    ->by("day:{$actor}"),
            ];
        });

        Passport::useTokenModel(OAuthToken::class);
        Passport::tokensExpireIn(now()->addDays(config('instance.oauth.token_expiration', 356)));
        Passport::refreshTokensExpireIn(now()->addDays(config('instance.oauth.refresh_expiration', 400)));
        Passport::enableImplicitGrant();
        if (config('instance.oauth.pat.enabled')) {
            Passport::personalAccessClientId(config('instance.oauth.pat.id'));
        }

        Passport::tokensCan([
            'read' => 'Full read access to your account',
            'write' => 'Full write access to your account',
            'follow' => 'Ability to follow other profiles',
            'admin:read' => 'Read all data on the server',
            'admin:read:domain_blocks' => 'Read sensitive information of all domain blocks',
            'admin:write' => 'Modify all data on the server',
            'admin:write:domain_blocks' => 'Perform moderation actions on domain blocks',
            'push' => 'Receive your push notifications',
        ]);

        Passport::setDefaultScope([
            'read',
            'write',
            'follow',
        ]);

        URL::forceRootUrl(config('app.url'));

        // Model::preventLazyLoading(true);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Passport::ignoreRoutes();

        $this->app->bind(UserOidcService::class, function () {
            return UserOidcService::build();
        });
    }
}
