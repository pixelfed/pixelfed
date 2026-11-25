<?php

use App\Jobs\IpDataPipeline\BackfillSignupNetworks;
use App\Jobs\IpDataPipeline\ImportIpDataset;
use App\Jobs\IpDataPipeline\PurgeIpDataset;
use App\Models\IpDataset;
use App\Models\User;
use App\Services\IpDataImportService;
use App\Services\IpDataService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Cache::flush();
});

function ipdImportFile(string $contents, string $suffix = '.csv'): string
{
    $path = tempnam(sys_get_temp_dir(), 'ipd').$suffix;
    file_put_contents($path, $contents);

    return $path;
}

function ipdImportRun(string $path, bool $force = false): IpDataset
{
    $dataset = IpDataImportService::stageLocal($path, $force, dispatch: false);

    while (! IpDataImportService::importSlice($dataset)) {
    }

    return $dataset->refresh();
}

function ipdCountryCsv(int $rows): string
{
    $csv = "network,continent_code,country_code,country_name\n";

    for ($i = 0; $i < $rows; $i++) {
        $csv .= '10.'.intdiv($i, 256).'.'.($i % 256).".0/24,NA,US,United States\n";
    }

    return $csv;
}

it('detects the dataset type from the header row', function () {
    expect(IpDataImportService::detect(['network', 'continent_code', 'country_code', 'country_name'])[0])->toBe('country');
    expect(IpDataImportService::detect(["\xEF\xBB\xBFnetwork", 'asn', 'country_code', 'name', 'org', 'domain'])[0])->toBe('asn');
    expect(fn () => IpDataImportService::detect(['network', 'city']))->toThrow(InvalidArgumentException::class);
    expect(fn () => IpDataImportService::detect(['asn', 'name']))->toThrow(InvalidArgumentException::class, 'Missing the network column');
});

it('imports a country csv and activates it', function () {
    $dataset = ipdImportRun(ipdImportFile("network,continent_code,country_code,country_name\n203.0.113.0/24,NA,US,United States\n2001:db8::/32,EU,DE,Germany\nbad-row,EU,DE,Germany\n198.51.100.0/24,EU,?,Nowhere\n"));

    expect($dataset->status)->toBe('active');
    expect($dataset->type)->toBe('country');
    expect($dataset->rows_imported)->toBe(2);
    expect($dataset->rows_skipped)->toBe(2);
    expect($dataset->networks_v4)->toBe(1);
    expect($dataset->networks_v6)->toBe(1);
    expect($dataset->entities)->toBe(2);
    expect($dataset->path)->toBeNull();
    expect(DB::table('ip_countries')->where('code', 'DE')->value('name'))->toBe('Germany');
    expect(IpDataService::active()['country'])->toBe($dataset->id);
    Queue::assertPushed(BackfillSignupNetworks::class);
});

it('imports a zipped asn csv in several slices', function () {
    $csv = "network,asn,country_code,name,org,domain\n";
    for ($i = 0; $i < 50; $i++) {
        $csv .= "192.0.{$i}.0/24,".(64500 + $i).",US,NET-{$i},\"Org {$i}, Inc.\",org{$i}.example\n";
    }

    $source = ipdImportFile($csv);
    $zip = sys_get_temp_dir().'/ip-to-asn-'.uniqid().'.csv.zip';
    $archive = new ZipArchive;
    $archive->open($zip, ZipArchive::CREATE);
    $archive->addFile($source, 'ip-to-asn.csv');
    $archive->close();

    $dataset = IpDataImportService::stageLocal($zip, dispatch: false);
    $slices = 0;
    while (! IpDataImportService::importSlice($dataset, 7)) {
        $slices++;
    }
    $dataset->refresh();

    expect($slices)->toBeGreaterThan(5);
    expect($dataset->status)->toBe('active');
    expect($dataset->rows_imported)->toBe(50);
    expect($dataset->entities)->toBe(50);
    expect(DB::table('ip_asns')->where('asn', 64510)->value('org'))->toBe('Org 10, Inc.');
    expect(IpDataService::lookup('192.0.10.200')['asn']['number'])->toBe(64510);
});

it('fails files with an unknown header without touching the active data', function () {
    $active = ipdImportRun(ipdImportFile(ipdCountryCsv(3)));
    $failed = ipdImportRun(ipdImportFile("network,city\n203.0.113.0/24,Lisbon\n"));

    expect($failed->status)->toBe('failed');
    expect($failed->error)->toContain('Unrecognized header row');
    expect($active->fresh()->status)->toBe('active');
    expect(IpDataService::active()['country'])->toBe($active->id);
});

it('replaces the active dataset and purges the old one', function () {
    $first = ipdImportRun(ipdImportFile(ipdCountryCsv(10)));
    $second = ipdImportRun(ipdImportFile(ipdCountryCsv(12)));

    expect($first->fresh()->status)->toBe('replaced');
    expect($second->status)->toBe('active');
    expect(IpDataService::active()['country'])->toBe($second->id);
    Queue::assertPushed(PurgeIpDataset::class, fn ($job) => $job->datasetId === $first->id);

    (new PurgeIpDataset($first->id))->handle();

    expect(DB::table('ip_country_networks')->where('dataset_id', $first->id)->count())->toBe(0);
    expect(DB::table('ip_country_networks')->where('dataset_id', $second->id)->count())->toBe(12);
    expect($first->fresh()->purged_at)->not->toBeNull();
});

it('refuses a much smaller dataset unless forced', function () {
    ipdImportRun(ipdImportFile(ipdCountryCsv(20)));

    $small = ipdImportRun(ipdImportFile(ipdCountryCsv(5)));
    expect($small->status)->toBe('failed');
    expect($small->error)->toContain('less than half');

    $forced = ipdImportRun(ipdImportFile(ipdCountryCsv(5)), true);
    expect($forced->status)->toBe('active');
});

it('cancels older running imports of the same type when a new file arrives', function () {
    $older = IpDataImportService::stageLocal(ipdImportFile(ipdCountryCsv(3)), dispatch: false);
    $newer = IpDataImportService::stageLocal(ipdImportFile(ipdCountryCsv(3)));

    expect($older->fresh()->status)->toBe('cancelled');
    expect($newer->fresh()->status)->toBe('pending');
    Queue::assertPushed(ImportIpDataset::class, fn ($job) => $job->datasetId === $newer->id);
});

it('backfills signup country and asn for existing accounts', function () {
    $user = User::factory()->create(['app_register_ip' => '203.0.113.9']);
    $other = User::factory()->create(['app_register_ip' => '198.51.100.9']);
    expect($user->fresh()->signup_country)->toBeNull();

    ipdImportRun(ipdImportFile("network,continent_code,country_code,country_name\n203.0.113.0/24,AS,VN,Vietnam\n"));
    ipdImportRun(ipdImportFile("network,asn,country_code,name,org,domain\n203.0.113.0/24,7552,VN,VIETEL-AS-AP,Viettel Group,viettel.com.vn\n"));

    (new BackfillSignupNetworks)->handle();

    expect($user->fresh()->signup_country)->toBe('VN');
    expect((int) $user->fresh()->signup_asn)->toBe(7552);
    expect($other->fresh()->signup_country)->toBeNull();
});

it('stamps signup country and asn when an account is created', function () {
    ipdImportRun(ipdImportFile("network,continent_code,country_code,country_name\n203.0.113.0/24,EU,PT,Portugal\n"));

    $user = User::factory()->create(['app_register_ip' => '203.0.113.20']);

    expect($user->fresh()->signup_country)->toBe('PT');
    expect($user->fresh()->signup_asn)->toBeNull();
});

it('works from the command line', function () {
    $path = ipdImportFile(ipdCountryCsv(4));

    $this->artisan('app:ip-data', ['action' => 'import', 'args' => [$path]])
        ->expectsOutputToContain('Imported 4 networks')
        ->assertSuccessful();

    $this->artisan('app:ip-data', ['action' => 'lookup', 'args' => ['10.0.2.1']])
        ->expectsOutputToContain('United States')
        ->assertSuccessful();

    $this->artisan('app:ip-data', ['action' => 'status'])->assertSuccessful();
});
