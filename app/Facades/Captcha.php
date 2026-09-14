<?php

namespace App\Facades;

use App\Contracts\CaptchaDriver;
use App\Services\Captcha\CaptchaManager;
use Illuminate\Support\Facades\Facade;

/**
 * Provider-agnostic captcha facade.
 *
 * @method static CaptchaDriver active()
 * @method static bool enabled()
 * @method static bool activeOn(string $surface)
 * @method static array available()
 * @method static array rules()
 * @method static CaptchaDriver driver(string|null $driver = null)
 *
 * @see CaptchaManager
 */
class Captcha extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'captcha.manager';
    }
}
