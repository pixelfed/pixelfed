<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\ProfileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ComposeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function search_location_can_filter_by_country()
    {
        $user = User::factory()->create();

        $profile = ProfileFactory::new()->create([
            'user_id' => $user->id,
        ]);

        $user->update([
            'profile_id' => $profile->id,
        ]);

        $user->refresh();

        DB::table('places')->insert([
            [
                'id' => 1,
                'slug' => 'san-francisco-usa',
                'name' => 'San Francisco',
                'state' => 'California',
                'country' => 'USA',
                'aliases' => null,
                'lat' => 37.7749,
                'long' => -122.4194,
                'score' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'slug' => 'san-francisco-philippines',
                'name' => 'San Francisco',
                'state' => 'Cebu',
                'country' => 'Philippines',
                'aliases' => null,
                'lat' => 10.3,
                'long' => 123.9,
                'score' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user, 'api')
            ->get('/api/v1.1/compose/search/location?q=San%20Francisco%2C%20USA');

        $response->assertJsonCount(1);

        $response->assertJsonFragment([
            'name' => 'San Francisco',
            'country' => 'USA',
        ]);

        $response->assertJsonMissing([
            'country' => 'Philippines',
        ]);
    }
}
