<?php

namespace App\Providers;

use App\Observers\UserObserver;
use App\User;
use Auth;
use Horizon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        User::observe(UserObserver::class);

        Horizon::auth(function ($request) {
            return Auth::check() && $request->user()->is_admin;
        });

        Blade::directive('prettyNumber', function ($expression) {
            $num = $expression;
            $abbrevs = [12 => 'T', 9 => 'B', 6 => 'M', 3 => 'K', 0 => ''];
            foreach ($abbrevs as $exponent => $abbrev) {
                if ($expression >= pow(10, $exponent)) {
                    $display_num = $expression / pow(10, $exponent);
                    $num = number_format($display_num, 0).$abbrev;

                    return "<?php echo '$num'; ?>";
                }
            }

            return "<?php echo $num; ?>";
        });

        Blade::directive('prettySize', function ($expression) {
            $size = intval($expression);
            $precision = 0;
            $short = true;
            $units = $short ?
                ['B', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'] :
                ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
            }
            $res = round($size, $precision).$units[$i];

            return "<?php echo '$res'; ?>";
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
