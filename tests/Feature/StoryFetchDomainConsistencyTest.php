<?php

use App\Jobs\StoryPipeline\StoryFetch;

/*
|--------------------------------------------------------------------------
| StoryFetch::validateDomainConsistency prefix normalization
|--------------------------------------------------------------------------
|
| The check used ltrim($host, 'www.'), which strips a leading run of any of
| the characters w/., not the literal "www." prefix. So wwworld.victim.com
| collapsed to orld.victim.com and falsely matched a different domain,
| bypassing the same-domain federation guard. Only a real "www." prefix must
| be normalized.
|
*/

function callValidateDomainConsistency(string $url1, string $url2): bool
{
    $job = new StoryFetch([]);
    $ref = new ReflectionMethod($job, 'validateDomainConsistency');
    $ref->setAccessible(true);

    return $ref->invoke($job, $url1, $url2);
}

it('does not let a wwworld host match a different domain', function () {
    expect(callValidateDomainConsistency(
        'https://wwworld.victim.com/story/123',
        'https://orld.victim.com/users/alice',
    ))->toBeFalse();
});

it('does not treat leading w/. runs as a www prefix', function () {
    expect(callValidateDomainConsistency('https://w.example.com/a', 'https://example.com/b'))->toBeFalse();
    expect(callValidateDomainConsistency('https://ww.example.com/a', 'https://example.com/b'))->toBeFalse();
    expect(callValidateDomainConsistency('https://wwww.example.com/a', 'https://example.com/b'))->toBeFalse();
});

it('matches identical hosts', function () {
    expect(callValidateDomainConsistency(
        'https://example.com/story/1',
        'https://example.com/users/bob',
    ))->toBeTrue();
});

it('is case-insensitive', function () {
    expect(callValidateDomainConsistency(
        'https://Example.COM/story/1',
        'https://example.com/users/bob',
    ))->toBeTrue();
});

it('normalizes a genuine www. prefix on one side', function () {
    expect(callValidateDomainConsistency(
        'https://www.example.com/story/1',
        'https://example.com/users/bob',
    ))->toBeTrue();
});

it('returns false when a host cannot be parsed', function () {
    expect(callValidateDomainConsistency('not a url', 'https://example.com/x'))->toBeFalse();
});
