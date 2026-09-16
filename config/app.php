<?php

use App\Util\Lexer\PrettyNumber;
use App\Util\Localization\Localization;
use Illuminate\Support\Facades\Facade;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Pixelfed'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'https://localhost'),

    /*
     * Do not edit your timezone or things will break!
     */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => Localization::normalizeLocale(env('APP_LOCALE', 'en-US')),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => Localization::normalizeLocale(env('APP_FALLBACK_LOCALE', 'en-US')),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    'short_description' => env('PF_SHORT_DESCRIPTION', 'Pixelfed is an image sharing platform, an ethical alternative to centralized platforms.'),
    'description' => env('PF_DESCRIPTION', 'Pixelfed is an image sharing platform, an ethical alternative to centralized platforms.'),
    'rules' => env('PF_RULES', null),
    'logo' => '/img/pixelfed-icon-color.svg',
    'banner_image' => '/storage/headers/default.jpg',
    'dev_log' => env('PIXELFED_DEV_LOG', false),

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        'PrettyNumber' => PrettyNumber::class,
    ])->toArray(),

];

/*
| Legacy two-letter language codes mapped to the current
| locale-coded folder names under lang/. Replace 'de' with 'de-DE'.
|
| Added Sep 2026 - DELETE AFTER COMMUNICATION WITH ADMINS
*/
$pixelfedLegacyLocaleMap = [
    'af' => 'af-ZA',
    'ar' => 'ar-SA',
    'bn' => 'bn-BD',
    'bs' => 'bs-BA',
    'ca' => 'ca-ES',
    'cs' => 'cs-CZ',
    'cy' => 'cy-GB',
    'da' => 'da-DK',
    'de' => 'de-DE',
    'el' => 'el-GR',
    'en' => 'en-US',
    'eo' => 'eo-UY',
    'es' => 'es-ES',
    'eu' => 'eu-ES',
    'fa' => 'fa-IR',
    'fi' => 'fi-FI',
    'fr' => 'fr-FR',
    'gd' => 'gd-GB',
    'gl' => 'gl-ES',
    'he' => 'he-IL',
    'hi' => 'hi-IN',
    'hr' => 'hr-HR',
    'hu' => 'hu-HU',
    'id' => 'id-ID',
    'it' => 'it-IT',
    'ja' => 'ja-JP',
    'ko' => 'ko-KR',
    'me' => 'me-ME',
    'mk' => 'mk-MK',
    'ms' => 'ms-MY',
    'nl' => 'nl-NL',
    'no' => 'no-NO',
    'oc' => 'oc-FR',
    'pl' => 'pl-PL',
    'pt' => 'pt-PT',
    'ro' => 'ro-RO',
    'ru' => 'ru-RU',
    'sk' => 'sk-SK',
    'sr' => 'sr-CS',
    'sv' => 'sv-SE',
    'th' => 'th-TH',
    'tr' => 'tr-TR',
    'uk' => 'uk-UA',
    'vi' => 'vi-VN',
    'zh-cn' => 'zh-CN',
    'zh-tw' => 'zh-TW',
];

if (! function_exists('pixelfed_normalize_locale')) {
    function pixelfed_normalize_locale(array $map, ?string $locale): string
    {
        $locale = is_string($locale) ? trim($locale) : '';

        if ($locale === '') {
            return 'en-US';
        }

        $lower = strtolower($locale);

        return $map[$lower] ?? $locale;
    }
}
