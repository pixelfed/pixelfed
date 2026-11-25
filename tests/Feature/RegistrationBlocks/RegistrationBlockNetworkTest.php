<?php

use App\Models\RegistrationBlock;
use App\Services\RegistrationBlockExchangeService;
use App\Services\RegistrationBlockService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::forget(RegistrationBlockService::CACHE_KEY);
});

function rbnRule(string $type, string $pattern, int $id = 1): array
{
    return ['id' => $id, 'type' => $type, 'match_type' => 'exact', 'pattern' => $pattern, 'expires_at' => null];
}

it('normalizes asn values', function (string $input, ?int $expected) {
    expect(RegistrationBlockService::normalizeAsn($input))->toBe($expected);
})->with([
    ['AS14061', 14061],
    ['as14061', 14061],
    [' 14061 ', 14061],
    ['AS 16276', 16276],
    ['0', null],
    ['AS', null],
    ['14061a', null],
    ['99999999999', null],
]);

it('validates and normalizes asn and country patterns', function () {
    expect(RegistrationBlockService::validatePattern('asn', 'exact', 'AS14061'))->toBeNull();
    expect(RegistrationBlockService::validatePattern('asn', 'exact', 'digitalocean'))->not->toBeNull();
    expect(RegistrationBlockService::validatePattern('country', 'exact', 'vn'))->toBeNull();
    expect(RegistrationBlockService::validatePattern('country', 'exact', 'VNM'))->not->toBeNull();

    expect(RegistrationBlockService::normalizePattern('asn', 'exact', 'AS14061'))->toBe('14061');
    expect(RegistrationBlockService::normalizePattern('country', 'exact', 'vn'))->toBe('VN');
});

it('stores asn and country blocks as exact matches', function () {
    $asn = RegistrationBlockService::store('asn', 'wildcard', 'as16276');
    $country = RegistrationBlockService::store('country', 'regex', 'fr');

    expect($asn->match_type)->toBe('exact');
    expect($asn->pattern)->toBe('16276');
    expect($country->match_type)->toBe('exact');
    expect($country->pattern)->toBe('FR');
    expect(RegistrationBlock::count())->toBe(2);
});

it('matches a known network without looking it up', function () {
    $rules = [rbnRule('asn', '14061', 1), rbnRule('country', 'VN', 2)];

    expect(RegistrationBlockService::find($rules, network: ['asn' => 14061, 'country' => 'US'])['id'])->toBe(1);
    expect(RegistrationBlockService::find($rules, network: ['asn' => 3320, 'country' => 'VN'])['id'])->toBe(2);
    expect(RegistrationBlockService::find($rules, network: ['asn' => 3320, 'country' => 'DE']))->toBeNull();
    expect(RegistrationBlockService::find($rules, ip: '203.0.113.7'))->toBeNull();
});

it('round trips asn and country blocks through export and import', function () {
    RegistrationBlockService::store('asn', 'exact', 'AS14061');
    RegistrationBlockService::store('country', 'exact', 'VN');

    $payload = RegistrationBlockExchangeService::export();
    RegistrationBlock::query()->delete();

    $result = RegistrationBlockExchangeService::import(RegistrationBlockExchangeService::parse(RegistrationBlockExchangeService::toJson($payload), 'json'));

    expect($result['added'])->toBe(2);
    expect(RegistrationBlock::where('type', 'asn')->value('pattern'))->toBe('14061');
    expect(RegistrationBlock::where('type', 'country')->value('pattern'))->toBe('VN');
});
