<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| CreateNewUser registration guardrails — property-based test (P6)
|--------------------------------------------------------------------------
|
| Exercises App\Actions\Fortify\CreateNewUser::create() directly (resolved from
| the container) rather than through the HTTP/Fortify pipeline, so the property
| isolates the action's own validation + availability guards without the
| rate limiter or session machinery.
|
| The suite defaults CACHE_STORE to redis and instance.enable_cc to true; both
| are pinned to test-friendly values in beforeEach so config_cache() reads the
| plain config() value and the register-token cache lives in the array store.
|
*/

beforeEach(function () {
    // Pin the cache to the in-memory array store (the suite default is redis,
    // which is not guaranteed to be running) and start from a clean slate.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // With instance.enable_cc disabled, config_cache($key) short-circuits to
    // config($key). That makes open_registration and the captcha toggles
    // deterministic and DB-free for this test.
    config(['instance.enable_cc' => false]);

    // Base valid environment: open registration, no max-user cap, no banned
    // signup IP, captcha OFF (so h-captcha-response is never required for the
    // valid case). Individual cases toggle exactly one of these.
    config([
        'pixelfed.open_registration' => true,
        'pixelfed.enforce_max_users' => false,
        'pixelfed.max_users' => 1000,
        'pixelfed.min_password_length' => 8,
        'pixelfed.max_name_length' => 30,
        'pixelfed.bouncer.cloud_ips.ban_signups' => false,
        'captcha.enabled' => false,
        'captcha.active.register' => false,
    ]);

    // Seed a known, valid register token in the array cache. The action reads
    // the same 'pf:register:rt' key (via CreateNewUser::getRegisterToken()).
    Cache::store('array')->forever('pf:register:rt', 'valid-register-token');
});

/**
 * The register token the action considers valid.
 */
function validRegisterToken(): string
{
    return CreateNewUser::getRegisterToken();
}

/**
 * Build a fully valid registration input array.
 *
 * Uses a unique alnum username and a real-looking, DNS-resolvable domain so the
 * email:rfc,dns,spoof rule does not flake on the happy path. $seed keeps values
 * unique across iterations.
 *
 * @return array<string, mixed>
 */
function validRegistrationInput(int $seed): array
{
    $username = 'validuser'.$seed;
    $password = 'sup3r-secret-'.$seed;

    return [
        'name' => 'Valid User '.$seed,
        'username' => $username,
        // gmail.com resolves, so the dns/spoof checks pass for valid cases.
        'email' => 'pixelfed.p6.'.$seed.'@gmail.com',
        'password' => $password,
        'password_confirmation' => $password,
        'agecheck' => 'on',
        'rt' => validRegisterToken(),
    ];
}

/**
 * Resolve the action under test from the container.
 */
function createNewUserAction(): CreateNewUser
{
    return app(CreateNewUser::class);
}

it('creates exactly one user for fully valid input across many iterations (P6)', function () {
    // Feature: fortify-auth-migration, Property 6: For any generated registration input, registration succeeds only when open_registration/max_users/rt/agecheck/ValidUsername/EmailService/captcha/pgsql-lowercasing rules are all satisfied
    // Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.6, 6.7, 6.10
    $iterations = 120;
    $action = createNewUserAction();

    for ($i = 0; $i < $iterations; $i++) {
        $before = User::count();
        $input = validRegistrationInput($i);

        $user = $action->create($input);

        // Exactly one new user persisted.
        expect(User::count())->toBe($before + 1);
        expect($user->exists)->toBeTrue();
        expect($user->username)->toBe($input['username']);
        expect($user->email)->toBe($input['email']);

        // Password stored as a bcrypt hash matching the plaintext, never plaintext.
        expect($user->password)->not->toBe($input['password']);
        expect(Hash::check($input['password'], $user->password))->toBeTrue();

        // app_register_ip is populated from the request.
        expect($user->app_register_ip)->not->toBeNull();
    }

    expect(User::count())->toBe($iterations);
})->group('fortify-auth-migration', 'pbt');

it('never persists a user when exactly one guardrail is violated across many iterations (P6)', function () {
    // Feature: fortify-auth-migration, Property 6: For any generated registration input, registration succeeds only when open_registration/max_users/rt/agecheck/ValidUsername/EmailService/captcha/pgsql-lowercasing rules are all satisfied
    // Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.6, 6.7, 6.10
    //
    // Each iteration takes an otherwise-valid input and violates exactly ONE
    // guardrail, then asserts create() throws and no user row is written. The
    // violation kind rotates deterministically so all guardrails are covered
    // many times over the >=100 iterations.
    $iterations = 160;
    $action = createNewUserAction();

    // A domain that is on the EmailService banned list (see EmailService::bannedDomains()).
    $bannedEmail = 'someone@bugmenot.com';
    expect(EmailService::isBanned($bannedEmail))->toBeTrue();

    $violations = [
        'missing_agecheck',
        'false_agecheck',
        'wrong_rt',
        'invalid_username_ends_with_php',
        'invalid_username_double_separator',
        'banned_email',
        'duplicate_email',
        'duplicate_username',
        'short_password',
        'unconfirmed_password',
        'captcha_required_missing',
        'closed_registration',
        'max_users_reached',
    ];

    for ($i = 0; $i < $iterations; $i++) {
        $kind = $violations[$i % count($violations)];
        $input = validRegistrationInput(1000 + $i);

        // Reset per-iteration config that some cases mutate.
        config([
            'pixelfed.open_registration' => true,
            'pixelfed.enforce_max_users' => false,
            'captcha.enabled' => false,
            'captcha.active.register' => false,
        ]);

        $before = User::count();

        switch ($kind) {
            case 'missing_agecheck':
                unset($input['agecheck']);
                break;

            case 'false_agecheck':
                $input['agecheck'] = '0';
                break;

            case 'wrong_rt':
                $input['rt'] = 'not-the-valid-token-'.$i;
                break;

            case 'invalid_username_ends_with_php':
                // ValidUsername rejects names ending in .php/.js/.css.
                $input['username'] = 'evil'.$i.'.php';
                break;

            case 'invalid_username_double_separator':
                // ValidUsername allows at most one dash/period/underscore.
                $input['username'] = 'a.b.c'.$i;
                break;

            case 'banned_email':
                $input['email'] = $bannedEmail;
                break;

            case 'duplicate_email':
                $existing = User::factory()->create();
                $input['email'] = $existing->email;
                break;

            case 'duplicate_username':
                $existing = User::factory()->create();
                $input['username'] = $existing->username;
                break;

            case 'short_password':
                // Below pixelfed.min_password_length (8).
                $short = 'a1b2';
                $input['password'] = $short;
                $input['password_confirmation'] = $short;
                break;

            case 'unconfirmed_password':
                $input['password_confirmation'] = $input['password'].'-mismatch';
                break;

            case 'captcha_required_missing':
                // Captcha enabled for register, but no h-captcha-response provided.
                config([
                    'captcha.enabled' => true,
                    'captcha.active.register' => true,
                ]);
                break;

            case 'closed_registration':
                config(['pixelfed.open_registration' => false]);
                break;

            case 'max_users_reached':
                // Fill the instance to its cap so the availability guard aborts.
                config([
                    'pixelfed.enforce_max_users' => true,
                    'pixelfed.max_users' => 1,
                ]);
                User::factory()->create(['status' => null]);
                break;
        }

        $countBeforeCreate = User::count();

        // create() must reject the input. Validation failures raise a
        // ValidationException; the availability guards raise either an
        // HttpException (abort_if 400 when registration is closed) or an
        // HttpResponseException (redirect to the max-users help page).
        $thrown = null;
        try {
            $action->create($input);
        } catch (Throwable $e) {
            $thrown = $e;
        }

        expect($thrown)->not->toBeNull("violation [{$kind}] should reject registration");
        expect(
            $thrown instanceof ValidationException
            || $thrown instanceof HttpResponseException
            || $thrown instanceof HttpException
        )->toBeTrue("violation [{$kind}] threw unexpected ".($thrown ? get_class($thrown) : 'null'));

        // No user was created by the rejected registration. (Duplicate/max-user
        // cases legitimately added a fixture user before create(), so compare
        // against the count taken immediately before the create() call.)
        expect(User::count())->toBe($countBeforeCreate);
        expect(User::count())->toBeGreaterThanOrEqual($before);
    }
})->group('fortify-auth-migration', 'pbt');
