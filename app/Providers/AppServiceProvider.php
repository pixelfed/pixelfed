<?php

namespace App\Providers;

use App\Observers\{
    AvatarObserver,
    NotificationObserver,
    ModLogObserver,
    StatusHashtagObserver,
    UserObserver,
    UserFilterObserver,
};
use App\{
    Avatar,
    Notification,
    ModLog,
    StatusHashtag,
    User,
    UserFilter
};
use Auth, Horizon, URL;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
        Notification::observe(NotificationObserver::class);
        ModLog::observe(ModLogObserver::class);
        StatusHashtag::observe(StatusHashtagObserver::class);
        User::observe(UserObserver::class);
        UserFilter::observe(UserFilterObserver::class);

        Horizon::auth(function ($request) {
            return Auth::check() && $request->user()->is_admin;
        });

        Blade::directive('prettyNumber', function ($expression) {
            $num = \App\Util\Lexer\PrettyNumber::convert($expression);
            return "<?php echo $num; ?>";
        });

        Blade::directive('prettySize', function ($expression) {
            $size = \App\Util\Lexer\PrettyNumber::size($expression);
            return "<?php echo '$size'; ?>";
        });

        Blade::directive('maxFileSize', function () {
            $value = config('pixelfed.max_photo_size');

            return \App\Util\Lexer\PrettyNumber::size($value, true);
        });
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
