<?php

use App\Http\Controllers\AdminController;
use App\Models\Instance;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

describe('instance notes data type', function () {
    it('stores admin-saved notes as an array and serves them through the domain_blocks API', function () {
        $instance = Instance::create([
            'domain' => 'bad.example',
            'banned' => false,
            'unlisted' => false,
        ]);

        $request = Request::create('/i/admin/instances/update', 'POST', [
            'id' => $instance->id,
            'banned' => 1,
            'unlisted' => 1,
            'notes' => 'spam ring',
        ]);

        app(AdminController::class)->postInstanceUpdateApi($request);

        $instance->refresh();
        expect(is_array($instance->notes))->toBeTrue();
        expect($instance->notes)->toBe(['spam ring']);

        $admin = User::factory()->admin()->create();
        $admin->refresh();
        Passport::actingAs($admin, ['admin:read']);

        $this->getJson('/api/v1/admin/domain_blocks')
            ->assertOk()
            ->assertJsonFragment(['private_comment' => 'spam ring']);

        $this->getJson('/api/v1/admin/domain_blocks/'.$instance->id)
            ->assertOk()
            ->assertJsonFragment(['private_comment' => 'spam ring']);
    });

    it('lets an API-created array block be re-saved through the admin path without a 422', function () {
        $instance = Instance::create([
            'domain' => 'bad2.example',
            'banned' => true,
            'notes' => ['via api'],
        ]);

        $request = Request::create('/i/admin/instances/update', 'POST', [
            'id' => $instance->id,
            'banned' => 1,
            'notes' => 'updated note',
        ]);

        app(AdminController::class)->postInstanceUpdateApi($request);

        $instance->refresh();
        expect(is_array($instance->notes))->toBeTrue();
        expect($instance->notes)->toBe(['updated note']);
    });

    it('does not 500 the index when a legacy scalar note is present', function () {
        $instance = Instance::create([
            'domain' => 'legacy.example',
            'banned' => true,
        ]);

        // mimic a pre-fix row: the array cast json_encodes a scalar string,
        // so a legacy value is stored as a JSON scalar and decodes back to a string
        DB::table('instances')
            ->where('id', $instance->id)
            ->update(['notes' => json_encode('legacy string')]);

        $admin = User::factory()->admin()->create();
        $admin->refresh();
        Passport::actingAs($admin, ['admin:read']);

        $this->getJson('/api/v1/admin/domain_blocks')
            ->assertOk()
            ->assertJsonFragment(['private_comment' => 'legacy string']);
    });
});
