<?php

use App\Jobs\IpDataPipeline\ImportIpDataset;
use App\Models\IpDataset;
use App\Models\User;
use App\Services\IpDataImportService;
use App\Services\RegistrationBlockService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Cache::flush();

    $this->ipdStorage = sys_get_temp_dir().'/pf-ipd-admin-'.Str::random(16);
    mkdir($this->ipdStorage, 0755, true);
    $this->app->useStoragePath($this->ipdStorage);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->ipdStorage);
});

function ipdAdmin(): User
{
    return User::factory()->create(['is_admin' => true])->refresh();
}

function ipdAdminCsv(string $contents): string
{
    $dir = storage_path('ipd-sources');

    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $path = $dir.'/'.Str::random(16).'.csv';
    file_put_contents($path, $contents);

    return $path;
}

function ipdAdminFinish(IpDataset $dataset): IpDataset
{
    $slices = 0;

    while (! IpDataImportService::importSlice($dataset)) {
        if (++$slices > 100) {
            throw new RuntimeException("Import of dataset {$dataset->id} did not finish.");
        }
    }

    return $dataset->refresh();
}

function ipdAdminImport(string $contents): IpDataset
{
    $dataset = ipdAdminFinish(IpDataImportService::stageLocal(ipdAdminCsv($contents), dispatch: false));

    expect($dataset->status)->toBe(
        IpDataset::STATUS_ACTIVE,
        "Import of {$dataset->source_name} ended as {$dataset->status}: {$dataset->error}"
    );

    return $dataset;
}

function ipdAdminData(): void
{
    ipdAdminImport("network,continent_code,country_code,country_name\n203.0.113.0/24,NA,US,United States\n");
    ipdAdminImport("network,asn,country_code,name,org,domain\n203.0.112.0/20,14061,US,DIGITALOCEAN-ASN,\"DigitalOcean, LLC\",digitalocean.com\n");
}

it('keeps the ip data page admin only', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/i/admin/ip-data')->assertRedirect(config('app.url'));
    $this->actingAs($user)->postJson('/i/admin/ip-data/upload')->assertRedirect(config('app.url'));
    expect(IpDataset::count())->toBe(0);
});

it('shows datasets, lookups and top signup networks', function () {
    ipdAdminData();
    User::factory()->count(2)->create(['app_register_ip' => '203.0.113.42']);

    $this->actingAs(ipdAdmin())
        ->get('/i/admin/ip-data?ip=203.0.113.42')
        ->assertOk()
        ->assertSee('IP to Country')
        ->assertSee('DigitalOcean, LLC')
        ->assertSee('203.0.112.0/20')
        ->assertSee('2 local accounts')
        ->assertSee('AS14061')
        ->assertSee('IPLocate.io');
});

it('accepts a chunked upload and queues the import', function () {
    $csv = "network,continent_code,country_code,country_name\n203.0.113.0/24,NA,US,United States\n198.51.100.0/24,AS,VN,Vietnam\n";
    $half = intdiv(strlen($csv), 2);
    $admin = ipdAdmin();

    $send = fn (int $offset, string $bytes, bool $final) => $this->actingAs($admin)->post('/i/admin/ip-data/upload', [
        'upload_id' => str_repeat('a', 32),
        'offset' => $offset,
        'total' => strlen($csv),
        'name' => 'ip-to-country.csv',
        'final' => $final ? 1 : 0,
        'chunk' => UploadedFile::fake()->createWithContent('chunk', $bytes),
    ]);

    $send(0, substr($csv, 0, $half), false)
        ->assertOk()
        ->assertJson(['received' => $half]);

    $send($half + 10, substr($csv, $half), true)
        ->assertStatus(409)
        ->assertJson(['expected_offset' => $half]);

    $response = $send($half, substr($csv, $half), true)->assertOk();

    expect($response->json('dataset.type'))->toBe('country');
    expect($response->json('dataset.status'))->toBe('pending');
    Queue::assertPushed(ImportIpDataset::class);

    $dataset = IpDataset::findOrFail((int) $response->json('dataset.id'));
    expect((int) $dataset->created_by)->toBe($admin->id);

    $dataset = ipdAdminFinish($dataset);

    expect($dataset->status)->toBe('active');
    expect($dataset->rows_imported)->toBe(2);

    $this->actingAs($admin)->getJson('/i/admin/ip-data/status')->assertOk()->assertJson(['running' => false]);
});

it('rejects uploads that are not csv or zip files', function () {
    $this->actingAs(ipdAdmin())->post('/i/admin/ip-data/upload', [
        'upload_id' => str_repeat('b', 32),
        'offset' => 0,
        'total' => 5,
        'name' => 'evil.php',
        'final' => 1,
        'chunk' => UploadedFile::fake()->createWithContent('chunk', '<?php'),
    ])->assertStatus(422);

    expect(IpDataset::count())->toBe(0);
});

it('lets admins cancel a running import', function () {
    $dataset = IpDataImportService::stageLocal(
        ipdAdminCsv("network,continent_code,country_code,country_name\n203.0.113.0/24,NA,US,United States\n"),
        dispatch: false
    );

    expect($dataset->status)->toBe('pending');

    $this->actingAs(ipdAdmin())
        ->post('/i/admin/ip-data/'.$dataset->id.'/cancel')
        ->assertRedirect('/i/admin/ip-data');

    expect($dataset->fresh()->status)->toBe('cancelled');
});

it('filters users by asn, country and ip', function () {
    ipdAdminData();
    $match = User::factory()->create(['username' => 'seoboost', 'app_register_ip' => '203.0.113.42']);
    User::factory()->create(['username' => 'minalens', 'app_register_ip' => '198.51.100.7']);

    expect((int) $match->fresh()->signup_asn)->toBe(14061);

    $admin = ipdAdmin();

    foreach (['asn:AS14061', 'asn:14061', 'country:us', 'ip:203.0.113.42'] as $q) {
        $this->actingAs($admin)
            ->get('/i/admin/users/list?a=search&q='.urlencode($q))
            ->assertOk()
            ->assertSee('seoboost')
            ->assertDontSee('minalens');
    }
});

it('shows the signup network and same ip accounts on the user page', function () {
    ipdAdminData();
    $user = User::factory()->create(['username' => 'seoboost', 'app_register_ip' => '203.0.113.42']);
    User::factory()->create(['username' => 'bestcasino', 'app_register_ip' => '203.0.113.42']);

    $this->actingAs(ipdAdmin())
        ->get('/i/admin/users/show/'.$user->id)
        ->assertOk()
        ->assertSee('Signup network')
        ->assertSee('DigitalOcean, LLC')
        ->assertSee('Block AS14061')
        ->assertSee('Block 203.0.112.0/20')
        ->assertSee('Same signup IP')
        ->assertSee('bestcasino');
});

it('adds signup networks to the dashboard accounts api', function () {
    ipdAdminData();
    User::factory()->create(['app_register_ip' => '203.0.113.42']);

    $data = $this->actingAs(ipdAdmin())->getJson('/i/admin/api/accounts')->assertOk()->json('data');
    $withNetwork = collect($data)->firstWhere('signup_network', '!=', null);

    expect($withNetwork)->not->toBeNull();
    expect($withNetwork['signup_network']['asn']['number'])->toBe(14061);
    expect($withNetwork['signup_network']['country']['code'])->toBe('US');
});

it('blocks signups by asn and country through the registration guard', function () {
    config([
        'pixelfed.open_registration' => true,
        'pixelfed.max_users' => 1000,
        'instance.enable_cc' => false,
    ]);

    ipdAdminData();

    expect(RegistrationBlockService::ipBlocked('203.0.113.42', false))->toBeFalse();

    RegistrationBlockService::store('asn', 'exact', 'AS14061');
    expect(RegistrationBlockService::ipBlocked('203.0.112.9', false))->toBeTrue();
    expect(RegistrationBlockService::ipBlocked('198.51.100.9', false))->toBeFalse();

    RegistrationBlockService::store('country', 'wildcard', 'us');
    expect(RegistrationBlockService::check(ip: '203.0.113.42', record: false)['type'])->toBe('asn');

    $this->get('/register')->assertOk();
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])->get('/register')->assertForbidden();
});
