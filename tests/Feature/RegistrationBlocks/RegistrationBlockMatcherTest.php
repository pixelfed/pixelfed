<?php

use App\Services\RegistrationBlockService;

function rbRule(string $type, string $pattern, string $match = 'exact', ?int $expiresAt = null, int $id = 1): array
{
    return [
        'id' => $id,
        'type' => $type,
        'match_type' => $match,
        'pattern' => $pattern,
        'expires_at' => $expiresAt,
    ];
}

it('matches single ips and cidr ranges for ipv4 and ipv6', function () {
    $rules = [
        rbRule('ip', '203.0.113.7', id: 1),
        rbRule('ip', '198.51.100.0/24', id: 2),
        rbRule('ip', '2001:db8::/32', id: 3),
    ];

    expect(RegistrationBlockService::find($rules, ip: '203.0.113.7')['id'])->toBe(1);
    expect(RegistrationBlockService::find($rules, ip: '198.51.100.200')['id'])->toBe(2);
    expect(RegistrationBlockService::find($rules, ip: '2001:db8:1::5')['id'])->toBe(3);
    expect(RegistrationBlockService::find($rules, ip: '203.0.113.8'))->toBeNull();
    expect(RegistrationBlockService::find($rules, ip: 'not-an-ip'))->toBeNull();
});

it('matches exact emails through plus tags and gmail dots', function () {
    $rules = [rbRule('email', 'spammer@gmail.com')];

    expect(RegistrationBlockService::find($rules, email: 'SPAMMER@gmail.com'))->not->toBeNull();
    expect(RegistrationBlockService::find($rules, email: 's.p.a.m.m.e.r+42@gmail.com'))->not->toBeNull();
    expect(RegistrationBlockService::find($rules, email: 'spammer@googlemail.com'))->not->toBeNull();
    expect(RegistrationBlockService::find($rules, email: 'spammer2@gmail.com'))->toBeNull();
});

it('does not strip dots for non gmail domains', function () {
    $rules = [rbRule('email', 'jo.hn@example.org')];

    expect(RegistrationBlockService::find($rules, email: 'john@example.org'))->toBeNull();
    expect(RegistrationBlockService::find($rules, email: 'jo.hn+x@example.org'))->not->toBeNull();
});

it('matches wildcard and regex emails', function () {
    $rules = [
        rbRule('email', '*casino*@*', 'wildcard', id: 1),
        rbRule('email', '^[a-z]{6}[0-9]{4}@gmail\.com$', 'regex', id: 2),
    ];

    expect(RegistrationBlockService::find($rules, email: 'bestcasino99@example.net')['id'])->toBe(1);
    expect(RegistrationBlockService::find($rules, email: 'qwerty1234@gmail.com')['id'])->toBe(2);
    expect(RegistrationBlockService::find($rules, email: 'daniel@gmail.com'))->toBeNull();
});

it('matches email domains including subdomains', function () {
    $rules = [
        rbRule('email_domain', 'spam.example', id: 1),
        rbRule('email_domain', '*.ru', 'wildcard', id: 2),
    ];

    expect(RegistrationBlockService::find($rules, email: 'a@spam.example')['id'])->toBe(1);
    expect(RegistrationBlockService::find($rules, email: 'a@mail.spam.example')['id'])->toBe(1);
    expect(RegistrationBlockService::find($rules, email: 'a@notspam.example'))->toBeNull();
    expect(RegistrationBlockService::find($rules, email: 'a@mail.ru')['id'])->toBe(2);
});

it('matches usernames with separators removed', function () {
    $rules = [
        rbRule('username', 'freecoins', id: 1),
        rbRule('username', '*crypto*', 'wildcard', id: 2),
        rbRule('username', '^[a-z]+[0-9]{5,}$', 'regex', id: 3),
    ];

    expect(RegistrationBlockService::find($rules, username: 'free_coins')['id'])->toBe(1);
    expect(RegistrationBlockService::find($rules, username: 'BestCrypto')['id'])->toBe(2);
    expect(RegistrationBlockService::find($rules, username: 'mark48213')['id'])->toBe(3);
    expect(RegistrationBlockService::find($rules, username: 'dansup'))->toBeNull();
});

it('skips expired rules', function () {
    $rules = [rbRule('ip', '203.0.113.7', expiresAt: time() - 10)];

    expect(RegistrationBlockService::find($rules, ip: '203.0.113.7'))->toBeNull();
});

it('treats wildcard special characters literally', function () {
    $rules = [rbRule('username', 'a.b', 'wildcard')];

    expect(RegistrationBlockService::find($rules, username: 'axb'))->toBeNull();
});

it('validates patterns before storing them', function (string $type, string $match, string $pattern, bool $ok) {
    expect(RegistrationBlockService::validatePattern($type, $match, $pattern) === null)->toBe($ok);
})->with([
    ['ip', 'exact', '203.0.113.7', true],
    ['ip', 'exact', '203.0.113.0/24', true],
    ['ip', 'exact', '2001:db8::/32', true],
    ['ip', 'exact', '203.0.113.0/2', false],
    ['ip', 'exact', '203.0.113.*', false],
    ['email', 'exact', 'not-an-email', false],
    ['email', 'wildcard', '*', false],
    ['email', 'regex', '(unclosed', false],
    ['email', 'regex', '.*', false],
    ['email_domain', 'exact', 'localhost', false],
    ['email_domain', 'exact', 'spam.example', true],
    ['username', 'regex', '^bot[0-9]+$', true],
    ['nope', 'exact', 'x', false],
]);

it('parses expiry durations', function () {
    expect(RegistrationBlockService::parseDuration('never'))->toBeNull();
    expect(RegistrationBlockService::parseDuration(''))->toBeNull();
    expect(RegistrationBlockService::parseDuration('7d')->isFuture())->toBeTrue();
    expect(fn () => RegistrationBlockService::parseDuration('soon'))->toThrow(InvalidArgumentException::class);
});
