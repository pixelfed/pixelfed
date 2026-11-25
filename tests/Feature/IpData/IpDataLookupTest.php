<?php

use App\Models\IpDataset;
use App\Services\IpDataImportService;
use App\Services\IpDataService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Cache::flush();
});

function ipdLookupCsv(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'ipd').'.csv';
    file_put_contents($path, $contents);

    return $path;
}

function ipdLookupImport(string $contents): IpDataset
{
    $dataset = IpDataImportService::stageLocal(ipdLookupCsv($contents), dispatch: false);

    while (! IpDataImportService::importSlice($dataset)) {
    }

    return $dataset->refresh();
}

it('builds the same key for ipv4 and ipv4 mapped ipv6', function () {
    expect(IpDataService::key('203.0.113.7'))->toBe('00000000000000000000ffffcb007107');
    expect(IpDataService::key('::ffff:203.0.113.7'))->toBe(IpDataService::key('203.0.113.7'));
    expect(IpDataService::key('2001:db8::1'))->toBe('20010db8000000000000000000000001');
    expect(IpDataService::key('not-an-ip'))->toBeNull();
    expect(IpDataService::key(''))->toBeNull();
    expect(IpDataService::key(null))->toBeNull();
});

it('parses networks and masks host bits', function () {
    $net = IpDataService::parseNetwork('203.0.113.77/24');

    expect($net['start'])->toBe(IpDataService::key('203.0.113.0'));
    expect($net['prefix'])->toBe(120);
    expect($net['v4'])->toBeTrue();
    expect(IpDataService::cidr($net['start'], $net['prefix']))->toBe('203.0.113.0/24');
    expect(IpDataService::endKey($net['start'], $net['prefix']))->toBe(IpDataService::key('203.0.113.255'));

    $v6 = IpDataService::parseNetwork('2001:db8:abcd::/48');
    expect($v6['prefix'])->toBe(48);
    expect($v6['v4'])->toBeFalse();
    expect(IpDataService::cidr($v6['start'], $v6['prefix']))->toBe('2001:db8:abcd::/48');

    expect(IpDataService::parseNetwork('198.51.100.9')['prefix'])->toBe(128);
    expect(IpDataService::parseNetwork('203.0.113.0/33'))->toBeNull();
    expect(IpDataService::parseNetwork('203.0.113.0/'))->toBeNull();
    expect(IpDataService::parseNetwork('nope/24'))->toBeNull();
});

it('returns nothing before any data is imported', function () {
    expect(IpDataService::available())->toBeFalse();
    expect(IpDataService::lookup('203.0.113.7'))->toBeNull();
    expect(IpDataService::lookupMany(['203.0.113.7']))->toBe(['203.0.113.7' => null]);
});

it('looks up country and asn details for ipv4 and ipv6', function () {
    ipdLookupImport("network,continent_code,country_code,country_name\n203.0.113.0/24,NA,US,United States\n2001:db8::/32,EU,DE,Germany\n");
    ipdLookupImport("network,asn,country_code,name,org,domain\n203.0.112.0/20,14061,US,DIGITALOCEAN-ASN,\"DigitalOcean, LLC\",digitalocean.com\n2001:db8::/32,3320,DE,DTAG,Deutsche Telekom AG,telekom.com\n");

    $v4 = IpDataService::lookup('203.0.113.42');
    expect($v4['ip'])->toBe('203.0.113.42');
    expect($v4['country'])->toMatchArray(['code' => 'US', 'name' => 'United States', 'continent' => 'NA', 'network' => '203.0.113.0/24']);
    expect($v4['asn'])->toMatchArray(['number' => 14061, 'org' => 'DigitalOcean, LLC', 'domain' => 'digitalocean.com', 'network' => '203.0.112.0/20']);

    $v6 = IpDataService::lookup('2001:db8:1::5');
    expect($v6['country']['code'])->toBe('DE');
    expect($v6['asn']['number'])->toBe(3320);

    $edge = IpDataService::lookup('203.0.112.1');
    expect($edge['country'])->toBeNull();
    expect($edge['asn']['number'])->toBe(14061);

    expect(IpDataService::lookup('198.51.100.1'))->toBeNull();
    expect(IpDataService::signupNetwork('203.0.113.42'))->toBe(['asn' => 14061, 'country' => 'US']);
});

it('picks the most specific of nested networks', function () {
    ipdLookupImport("network,continent_code,country_code,country_name\n10.0.0.0/8,NA,US,United States\n10.1.0.0/16,NA,CA,Canada\n10.1.5.0/24,EU,FR,France\n");

    expect(IpDataService::lookup('10.1.5.9')['country']['code'])->toBe('FR');
    expect(IpDataService::lookup('10.1.9.9')['country']['code'])->toBe('CA');
    expect(IpDataService::lookup('10.200.0.1')['country']['code'])->toBe('US');
});

it('looks up many ips at once and caches results per dataset', function () {
    $dataset = ipdLookupImport("network,asn,country_code,name,org,domain\n203.0.112.0/20,14061,US,DIGITALOCEAN-ASN,\"DigitalOcean, LLC\",digitalocean.com\n");

    $results = IpDataService::lookupMany(['203.0.113.1', '198.51.100.1', 'bogus', '203.0.113.1']);

    expect(array_keys($results))->toBe(['203.0.113.1', '198.51.100.1', 'bogus']);
    expect($results['203.0.113.1']['asn']['number'])->toBe(14061);
    expect($results['198.51.100.1'])->toBeNull();
    expect($results['bogus'])->toBeNull();

    $key = IpDataService::LOOKUP_CACHE_PREFIX.'0:'.$dataset->id.':'.IpDataService::key('203.0.113.1');
    expect(Cache::get($key)['asn']['number'])->toBe(14061);
});
