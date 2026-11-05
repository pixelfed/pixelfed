<?php

use App\Models\RegistrationBlock;
use App\Models\User;
use App\Services\RegistrationBlockExchangeService;
use App\Services\RegistrationBlockService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::forget(RegistrationBlockService::CACHE_KEY);
});

function rbxSeed(): void
{
    RegistrationBlockService::store('ip', 'exact', '198.51.100.0/24', 'botnet');
    RegistrationBlockService::store('email', 'exact', 'spammer@example.net', 'seen in reports');
    RegistrationBlockService::store('email_domain', 'exact', 'spam.example');
    RegistrationBlockService::store('username', 'wildcard', '*casino*');
    RegistrationBlockService::store('username', 'regex', '^[a-z]+[0-9]{5,}$')->update(['is_active' => false]);
}

function rbxAdmin(): User
{
    $user = User::factory()->create(['is_admin' => true]);
    $user->refresh();

    return $user;
}

it('exports active blocks without notes by default', function () {
    rbxSeed();

    $payload = RegistrationBlockExchangeService::export();

    expect($payload['format'])->toBe(RegistrationBlockExchangeService::FORMAT);
    expect($payload['version'])->toBe(1);
    expect($payload['count'])->toBe(4);
    expect(collect($payload['blocks'])->pluck('pattern')->all())->not->toContain('^[a-z]+[0-9]{5,}$');
    expect($payload['blocks'][0])->not->toHaveKey('note');
});

it('exports notes, inactive blocks and a subset of types when asked', function () {
    rbxSeed();

    $payload = RegistrationBlockExchangeService::export(['username'], true, true);

    expect($payload['count'])->toBe(2);
    expect(collect($payload['blocks'])->pluck('type')->unique()->all())->toBe(['username']);
    expect($payload['blocks'][0])->toHaveKey('note');
    expect(collect($payload['blocks'])->pluck('status')->all())->toContain('disabled');
});

it('round trips through json and csv into a fresh server', function (string $format) {
    rbxSeed();

    $payload = RegistrationBlockExchangeService::export([], true);
    $body = $format === 'csv'
        ? RegistrationBlockExchangeService::toCsv($payload)
        : RegistrationBlockExchangeService::toJson($payload);

    RegistrationBlock::query()->delete();

    $result = RegistrationBlockExchangeService::import(RegistrationBlockExchangeService::parse($body));

    expect($result['added'])->toBe(4);
    expect($result['invalid'])->toBe([]);
    expect(RegistrationBlock::where('type', 'ip')->value('pattern'))->toBe('198.51.100.0/24');
    expect(RegistrationBlock::where('type', 'email')->value('note'))->toBe('seen in reports');
    expect(RegistrationBlockService::emailBlocked('s.pammer+x@example.net', false))->toBeFalse();
    expect(RegistrationBlockService::emailBlocked('spammer+x@example.net', false))->toBeTrue();
    expect(RegistrationBlockService::usernameBlocked('bigcasino', false))->toBeTrue();
})->with(['json', 'csv']);

it('leaves existing rules alone, including ones the admin disabled', function () {
    $disabled = RegistrationBlockService::store('username', 'wildcard', '*casino*', 'local note');
    $disabled->update(['is_active' => false]);

    $result = RegistrationBlockExchangeService::import([
        ['type' => 'username', 'match_type' => 'wildcard', 'pattern' => '*CASINO*', 'note' => 'remote', 'expires_at' => null],
    ]);

    expect($result['added'])->toBe(0);
    expect($result['existing'])->toBe(1);
    expect($disabled->fresh()->is_active)->toBeFalse();
    expect($disabled->fresh()->note)->toBe('local note');
});

it('skips rules that would block common legitimate signups', function () {
    $result = RegistrationBlockExchangeService::import([
        ['type' => 'email_domain', 'match_type' => 'exact', 'pattern' => 'gmail.com', 'note' => null, 'expires_at' => null],
        ['type' => 'email', 'match_type' => 'wildcard', 'pattern' => '*@*', 'note' => null, 'expires_at' => null],
        ['type' => 'username', 'match_type' => 'regex', 'pattern' => '[a-z]', 'note' => null, 'expires_at' => null],
        ['type' => 'username', 'match_type' => 'wildcard', 'pattern' => '*casino*', 'note' => null, 'expires_at' => null],
    ]);

    expect($result['broad'])->toHaveCount(3);
    expect($result['added'])->toBe(1);
    expect(RegistrationBlock::count())->toBe(1);
});

it('reports invalid rows, drops expired ones and filters types', function () {
    $result = RegistrationBlockExchangeService::import([
        ['type' => 'ip', 'match_type' => 'exact', 'pattern' => '10.0.0.0/4', 'note' => null, 'expires_at' => null],
        ['type' => 'nope', 'match_type' => 'exact', 'pattern' => 'x', 'note' => null, 'expires_at' => null],
        ['type' => 'username', 'match_type' => 'regex', 'pattern' => '(', 'note' => null, 'expires_at' => null],
        ['type' => 'username', 'match_type' => 'exact', 'pattern' => 'oldspam', 'note' => null, 'expires_at' => now()->subDay()->toIso8601String()],
        ['type' => 'email_domain', 'match_type' => 'exact', 'pattern' => 'spam.example', 'note' => null, 'expires_at' => null],
        ['type' => 'username', 'match_type' => 'exact', 'pattern' => 'freecoins', 'note' => null, 'expires_at' => now()->addDay()->toIso8601String()],
    ], ['types' => ['ip', 'username']]);

    expect($result['invalid'])->toHaveCount(3);
    expect($result['expired'])->toBe(1);
    expect($result['filtered'])->toBe(1);
    expect($result['added'])->toBe(1);
    expect(RegistrationBlock::first()->expires_at)->not->toBeNull();
});

it('applies an expiry and note override and supports dry runs', function () {
    $rows = [['type' => 'username', 'match_type' => 'exact', 'pattern' => 'freecoins', 'note' => 'remote', 'expires_at' => null]];

    $dry = RegistrationBlockExchangeService::import($rows, ['dry_run' => true]);
    expect($dry['added'])->toBe(1);
    expect(RegistrationBlock::count())->toBe(0);

    RegistrationBlockExchangeService::import($rows, ['expires' => now()->addDays(7), 'note' => 'From pixel.example']);
    $block = RegistrationBlock::first();

    expect($block->note)->toBe('From pixel.example');
    expect($block->expires_at->isFuture())->toBeTrue();
});

it('rejects files that are not registration block exports', function (string $contents) {
    RegistrationBlockExchangeService::parse($contents);
})->with([
    'empty' => '',
    'bad json' => '{"blocks": [',
    'other format' => '{"format":"mastodon.domain_blocks","blocks":[]}',
    'newer version' => '{"format":"pixelfed.registration_blocks","version":99,"blocks":[{"type":"ip","pattern":"203.0.113.7"}]}',
    'no blocks' => '{"format":"pixelfed.registration_blocks","version":1,"blocks":[]}',
])->throws(InvalidArgumentException::class);

it('escapes spreadsheet formulas in csv exports and restores them on import', function () {
    RegistrationBlockService::store('username', 'regex', '=?[a-z]+casino$');

    $csv = RegistrationBlockExchangeService::toCsv(RegistrationBlockExchangeService::export());

    expect($csv)->toContain("'=?[a-z]+casino$");
    expect(RegistrationBlockExchangeService::parse($csv)[0]['pattern'])->toBe('=?[a-z]+casino$');
});

it('accepts a csv without a header row', function () {
    $rows = RegistrationBlockExchangeService::parse("ip,exact,203.0.113.7\nusername,wildcard,*casino*,spam\n", 'csv');

    expect($rows)->toHaveCount(2);
    expect($rows[1]['note'])->toBe('spam');
});

it('lets admins download an export and import a file from the admin page', function () {
    rbxSeed();
    $admin = rbxAdmin();

    $res = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/i/admin/registration-blocks/export?format=json&types[]=ip&types[]=email');

    $res->assertOk();
    expect($res->headers->get('content-disposition'))->toContain('attachment');
    $body = $res->getContent();
    expect(json_decode($body, true)['count'])->toBe(2);

    RegistrationBlock::query()->delete();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/i/admin/registration-blocks/import', [
            'file' => UploadedFile::fake()->createWithContent('blocks.json', $body),
            'types' => ['ip', 'email', 'email_domain', 'username'],
            'expires' => 'file',
        ])
        ->assertRedirect()
        ->assertSessionHas('import_result', fn ($r) => $r['added'] === 2 && $r['dry_run'] === false);

    expect(RegistrationBlock::count())->toBe(2);
});

it('does not let non admins export or import', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/i/admin/registration-blocks/export')
        ->assertRedirect(config('app.url'));

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/i/admin/registration-blocks/import', ['contents' => '[]', 'types' => ['ip'], 'expires' => 'file'])
        ->assertRedirect(config('app.url'));
});

it('shows the signup ip with a block link on the admin user page', function () {
    $admin = rbxAdmin();
    $target = User::factory()->create(['app_register_ip' => '203.0.113.50']);
    $target->refresh();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/i/admin/users/show/'.$target->id)
        ->assertOk()
        ->assertSee('203.0.113.50')
        ->assertSee('pattern=203.0.113.50', false);

    RegistrationBlockService::store('ip', 'exact', '203.0.113.0/24');

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/i/admin/users/show/'.$target->id)
        ->assertOk()
        ->assertSee('blocked')
        ->assertDontSee('pattern=203.0.113.50', false);
});

it('prefills the add form from the block link', function () {
    $this->actingAs(rbxAdmin())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/i/admin/registration-blocks?type=ip&pattern=203.0.113.50&note=Signup+IP+of+%40spammer')
        ->assertOk()
        ->assertSee('203.0.113.50</textarea>', false)
        ->assertSee('value="Signup IP of @spammer"', false);
});
