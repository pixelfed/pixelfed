<?php

use App\Rules\ValidUrl;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| ValidUrl must fail non-strings, not throw
|--------------------------------------------------------------------------
|
| The rule called strtolower($value) with no type check, so a non-string
| (an array/object from a malformed or spec-compliant federated payload where
| a url field is an array) threw a TypeError and 500'd the request — a DoS
| vector. Non-strings must fail validation gracefully.
|
*/

function validatesUrl($value): bool
{
    return Validator::make(['url' => $value], ['url' => new ValidUrl])->passes();
}

it('passes a valid https url', function () {
    expect(validatesUrl('https://example.com/actor'))->toBeTrue();
});

it('is case-insensitive on the scheme', function () {
    expect(validatesUrl('HTTPS://example.com/actor'))->toBeTrue();
});

it('fails a non-https url', function () {
    expect(validatesUrl('http://example.com/actor'))->toBeFalse();
});

it('fails an array value without throwing', function () {
    expect(validatesUrl(['https://attacker.example/actor']))->toBeFalse();
});

it('fails a nested/associative array value without throwing', function () {
    expect(validatesUrl(['id' => 'https://attacker.example/actor']))->toBeFalse();
});

it('fails an integer value without throwing', function () {
    expect(validatesUrl(12345))->toBeFalse();
});

it('fails a null value without throwing', function () {
    expect(validatesUrl(null))->toBeFalse();
});
