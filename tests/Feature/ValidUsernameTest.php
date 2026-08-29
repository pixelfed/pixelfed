<?php

use App\Rules\ValidUsername;

/**
 * Run the ValidUsername rule and return the failure message, or null if it passed.
 */
function validateUsername(string $value): ?string
{
    $rule = new ValidUsername;
    $message = null;
    $rule->validate('username', $value, function ($msg) use (&$message) {
        $message = $msg;
    });

    return $message;
}

describe('ValidUsername', function () {
    it('accepts a simple alphanumeric username', function () {
        expect(validateUsername('dansup'))->toBeNull();
    });

    it('accepts a username with a single allowed separator', function () {
        expect(validateUsername('dan_sup'))->toBeNull();
        expect(validateUsername('dan.sup'))->toBeNull();
        expect(validateUsername('dan-sup'))->toBeNull();
    });

    it('rejects usernames ending in a disallowed file extension', function (string $value) {
        expect(validateUsername($value))->toBe('Username is invalid.');
    })->with([
        'user.php',
        'user.js',
        'user.css',
    ]);

    it('rejects more than one separator', function () {
        expect(validateUsername('a_b.c'))
            ->toBe('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');
    });

    it('rejects usernames not starting with a letter or number', function () {
        expect(validateUsername('_dansup'))
            ->toBe('Username is invalid. Must start with a letter or number.');
    });

    it('rejects usernames not ending with a letter or number', function () {
        expect(validateUsername('dansup_'))
            ->toBe('Username is invalid. Must end with a letter or number.');
    });

    it('rejects usernames with disallowed characters', function () {
        expect(validateUsername('dan$up'))
            ->toBe('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');
    });

    // it('rejects all-numeric usernames (must contain at least one letter)', function () {
    //     expect(validateUsername('12345'))
    //         ->toBe('Username is invalid. Must contain at least one alphabetical character.');
    // });

    it('rejects restricted names', function () {
        expect(validateUsername('admin'))->toBe('Username cannot be used.');
    });
});
