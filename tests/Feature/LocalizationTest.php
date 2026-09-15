<?php

use App\Models\User;
use App\Util\Localization\Localization;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Localization / Language Selection Tests
|--------------------------------------------------------------------------
|
| Covers:
|  - New users resolve locale dynamically (fallback to APP_LOCALE), the
|    stored language column stays null until explicitly chosen.
|  - Guests resolve to the configured app locale.
|  - Logged-in users with a stored language use it for the session.
|  - Changing language (SPA endpoint + /i/lang) persists and updates session.
|  - The locales manifest (Localization::languages()/locales()) shape.
|  - Empty (untranslated) strings fall back to the fallback locale.
|
*/

describe('new user language default (dynamic fallback)', function () {
    it('leaves a new user language null rather than persisting a default', function () {
        Config::set('app.locale', 'de-DE');

        $user = User::factory()->create();

        // The language is intentionally not persisted; the effective locale
        // is resolved dynamically at request/login time via config fallback.
        expect($user->language)->toBeNull();
    });

    it('falls back to the configured app locale when the user has no language', function () {
        Config::set('app.locale', 'de-DE');

        $user = User::factory()->create();

        expect($user->language)->toBeNull();

        // Logging in triggers AuthLogin::userLanguage, which resolves the
        // session locale via $user->language ?? config('app.locale').
        event(new Login('web', $user, false));

        expect(session('locale'))->toBe('de-DE');
    });

    it('uses the stored user language over the app default on login', function () {
        Config::set('app.locale', 'de-DE');

        $user = User::factory()->create(['language' => 'fr-FR']);

        event(new Login('web', $user, false));

        expect(session('locale'))->toBe('fr-FR');
    });
});

describe('guest locale resolution', function () {
    it('uses the configured app locale for guests with no session locale', function () {
        Config::set('app.locale', 'de-DE');

        // Simulate a request lifecycle without a session locale.
        expect(app()->getLocale())->toBe('de-DE');
    });

    it('honors a session locale set on the request via the Localization middleware', function () {
        Config::set('app.locale', 'en-US');

        $locale = firstNonDefaultLocale();

        // The Localization middleware calls app()->setLocale() from the
        // session. Drive a request with the session locale pre-set.
        $this->withSession(['locale' => $locale])
            ->get('/')
            ->assertSuccessful();

        expect(app()->getLocale())->toBe($locale);
    });
});

describe('locales manifest', function () {
    it('returns a non-empty list of language codes including en-US', function () {
        $langs = Localization::languages();

        expect($langs)->toBeArray()
            ->and($langs)->toContain('en-US')
            ->and(count($langs))->toBeGreaterThan(1);
    });

    it('excludes the vendor directory and the manifest file from the list', function () {
        $langs = Localization::languages();

        expect($langs)->not->toContain('vendor')
            ->and($langs)->not->toContain('locales.json')
            ->and($langs)->not->toContain('locales');
    });

    it('exposes locale metadata with code, name and nativeName', function () {
        $locales = Localization::locales();

        expect($locales)->toBeArray()->not->toBeEmpty();

        foreach ($locales as $locale) {
            expect($locale)->toHaveKeys(['code', 'name', 'nativeName']);
        }
    });

    it('sorts locale metadata alphabetically by display name', function () {
        $names = array_map(fn ($l) => $l['name'], Localization::locales());
        $sorted = $names;
        usort($sorted, 'strcasecmp');

        expect($names)->toBe($sorted);
    });

    it('keeps the languages() codes in sync with the locales() manifest', function () {
        $codes = array_map(fn ($l) => $l['code'], Localization::locales());

        expect(Localization::languages())->toEqualCanonicalizing($codes);
    });
});

describe('logged-in language via change-language endpoint', function () {
    beforeEach(function () {
        Config::set('exp.spa', true);
    });

    it('persists a valid locale to the user and session', function () {
        $locale = firstNonDefaultLocale();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/pixelfed/web/change-language.json', [
                'v' => '0.1',
                'l' => $locale,
            ])
            ->assertOk()
            ->assertJson(['language' => $locale]);

        expect($user->fresh()->language)->toBe($locale)
            ->and(session('locale'))->toBe($locale);
    });

    it('rejects an unknown locale', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/pixelfed/web/change-language.json', [
                'v' => '0.1',
                'l' => 'zz-ZZ',
            ])
            ->assertStatus(400);

        // Unknown locale must not be stored.
        expect($user->fresh()->language)->not->toBe('zz-ZZ');
    });

    it('rejects a missing locale parameter', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/pixelfed/web/change-language.json', [
                'v' => '0.1',
            ])
            ->assertStatus(422);
    });

    it('rejects an invalid api version', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/pixelfed/web/change-language.json', [
                'v' => '9.9',
                'l' => firstNonDefaultLocale(),
            ])
            ->assertStatus(422);
    });

    it('rejects a locale code longer than the max length', function () {
        $user = User::factory()->create();

        // 13 chars, exceeds the max:12 rule (alpha_dash still valid chars).
        $this->actingAs($user)
            ->postJson('/api/pixelfed/web/change-language.json', [
                'v' => '0.1',
                'l' => 'aa-bbbbbbbbbb',
            ])
            ->assertStatus(422);
    });

    it('accepts long custom locale codes such as en-x-pirate when available', function () {
        $locales = Localization::languages();

        if (! in_array('en-x-pirate', $locales, true)) {
            test()->markTestSkipped('en-x-pirate locale not present in this environment');
        }

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/pixelfed/web/change-language.json', [
                'v' => '0.1',
                'l' => 'en-x-pirate',
            ])
            ->assertOk()
            ->assertJson(['language' => 'en-x-pirate']);

        expect($user->fresh()->language)->toBe('en-x-pirate');
    });

    it('requires authentication', function () {
        $this->postJson('/api/pixelfed/web/change-language.json', [
            'v' => '0.1',
            'l' => firstNonDefaultLocale(),
        ])->assertNotFound();
    });
});

describe('changing language via /i/lang/{locale}', function () {
    it('persists a valid locale for a logged-in user and updates the session', function () {
        $locale = firstNonDefaultLocale();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/i/lang/'.$locale)
            ->assertRedirect(route('site.language'));

        expect($user->fresh()->language)->toBe($locale)
            ->and(session('locale'))->toBe($locale);
    });

    it('ignores an invalid locale and does not change the stored language', function () {
        $user = User::factory()->create(['language' => 'fr-FR']);

        $this->actingAs($user)
            ->get('/i/lang/zz-ZZ')
            ->assertRedirect(route('site.language'));

        expect($user->fresh()->language)->toBe('fr-FR')
            ->and(session()->has('locale'))->toBeFalse();
    });
});

describe('language settings via /settings/home', function () {
    it('updates the stored language when a valid locale is submitted', function () {
        $locale = firstNonDefaultLocale();

        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->post('/settings/home', [
                'name' => $user->name,
                'language' => $locale,
            ])->assertRedirect('/settings/home');

        expect($user->fresh()->language)->toBe($locale)
            ->and(session('locale'))->toBe($locale);
    });

    it('rejects a locale code that exceeds the max length', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->post('/settings/home', [
                'name' => $user->name,
                'language' => 'aa-bbbbbbbbbb', // 13 chars > max:12
            ])->assertSessionHasErrors('language');
    });
});

describe('locale-aware caching of rendered site pages', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('caches /site/about under a locale-scoped key', function () {
        $this->withSession(['locale' => 'en-US'])
            ->get('/site/about')
            ->assertOk();

        expect(Cache::has('site.about_v2:en-US'))->toBeTrue()
            // The old, locale-unaware key must not be used.
            ->and(Cache::has('site.about_v2'))->toBeFalse();
    });

    it('does not let a non-default locale poison the cached render of another', function () {
        $other = firstNonDefaultLocale();

        // A visitor on a non-default locale warms the cache first (cold cache).
        $this->withSession(['locale' => $other])
            ->get('/site/about')
            ->assertOk();

        // Then an en-US visitor: must get its own cache entry, not the
        // other locale's render.
        $this->withSession(['locale' => 'en-US'])
            ->get('/site/about')
            ->assertOk();

        // Each locale gets its own cache entry, so the first (non-default)
        // render cannot overwrite / be served as the en-US render. We assert
        // key isolation rather than content difference, since a partially
        // translated locale may legitimately render identically to English.
        expect(Cache::has('site.about_v2:'.$other))->toBeTrue()
            ->and(Cache::has('site.about_v2:en-US'))->toBeTrue()
            // The old shared key must never be written.
            ->and(Cache::has('site.about_v2'))->toBeFalse();
    })->skip(fn () => firstNonDefaultLocale() === 'en-US', 'needs a second locale');
});

describe('legacy locale normalization', function () {
    /*
    | Localization::normalizeLocale maps a legacy short code (e.g. "es") to the
    | current locale folder (e.g. "es-ES"). config/app.php runs APP_LOCALE and
    | APP_FALLBACK_LOCALE through it so instances upgrading with an old
    | APP_LOCALE do not silently fall back to English.
    */
    it('maps a legacy two-letter code to its current locale code', function () {
        expect(Localization::normalizeLocale('es'))->toBe('es-ES')
            ->and(Localization::normalizeLocale('de'))->toBe('de-DE')
            ->and(Localization::normalizeLocale('fr'))->toBe('fr-FR');
    });

    it('maps a legacy region code to its current locale code', function () {
        expect(Localization::normalizeLocale('zh-cn'))->toBe('zh-CN')
            ->and(Localization::normalizeLocale('zh-tw'))->toBe('zh-TW');
    });

    it('pins the ambiguous legacy codes deliberately', function () {
        // "en" must resolve to en-US, not the en-x-pirate novelty locale.
        expect(Localization::normalizeLocale('en'))->toBe('en-US')
            // "sr" must resolve to the prior single Serbian translation.
            ->and(Localization::normalizeLocale('sr'))->toBe('sr-CS');
    });

    it('is case-insensitive on legacy short codes', function () {
        expect(Localization::normalizeLocale('ES'))->toBe('es-ES')
            ->and(Localization::normalizeLocale('Zh-Cn'))->toBe('zh-CN');
    });

    it('leaves an already-current locale code unchanged', function () {
        expect(Localization::normalizeLocale('es-ES'))->toBe('es-ES')
            ->and(Localization::normalizeLocale('en-US'))->toBe('en-US');
    });

    it('leaves unknown or custom locale codes unchanged', function () {
        expect(Localization::normalizeLocale('xx-YY'))->toBe('xx-YY')
            ->and(Localization::normalizeLocale('en-x-pirate'))->toBe('en-x-pirate');
    });

    it('falls back to en-US for empty or whitespace input', function () {
        expect(Localization::normalizeLocale(''))->toBe('en-US')
            ->and(Localization::normalizeLocale('   '))->toBe('en-US')
            ->and(Localization::normalizeLocale(null))->toBe('en-US');
    });

    it('resolves the configured app locale from a legacy APP_LOCALE', function () {
        // Simulate an upgraded instance whose APP_LOCALE is still "es".
        Config::set('app.locale', Localization::normalizeLocale('es'));

        expect(config('app.locale'))->toBe('es-ES');
    });
});

describe('empty string translation fallback', function () {
    it('falls back to the fallback locale for empty (untranslated) strings', function () {
        Config::set('app.fallback_locale', 'en-US');

        // Build a self-contained throwaway locale with one empty and one
        // translated key so the assertion does not depend on live Crowdin
        // translation state.
        $code = 'zz-Test';
        $dir = lang_path($code);
        File::ensureDirectoryExists($dir);
        File::put($dir.'/testgroup.php', "<?php\n\nreturn [\n    'empty' => '',\n    'filled' => 'Localized',\n];\n");

        // Also ensure the fallback locale has real values for both keys.
        $fallbackDir = lang_path('en-US');
        File::ensureDirectoryExists($fallbackDir);
        $fallbackFile = $fallbackDir.'/testgroup.php';
        $hadFallback = File::exists($fallbackFile);
        File::put($fallbackFile, "<?php\n\nreturn [\n    'empty' => 'English Empty',\n    'filled' => 'English Filled',\n];\n");

        try {
            app()->setLocale($code);

            // Empty in target locale -> stripped -> falls back to en-US.
            expect(__('testgroup.empty'))->toBe('English Empty')
                // Present in target locale -> uses the target translation.
                ->and(__('testgroup.filled'))->toBe('Localized');
        } finally {
            app()->setLocale('en-US');
            File::deleteDirectory($dir);
            if (! $hadFallback) {
                File::delete($fallbackFile);
            }
        }
    });
});

/**
 * Pick a valid, available locale that isn't the current app default so
 * assertions actually prove a change happened.
 */
function firstNonDefaultLocale(): string
{
    $default = config('app.locale');

    foreach (Localization::languages() as $code) {
        if ($code !== $default) {
            return $code;
        }
    }

    return $default;
}
