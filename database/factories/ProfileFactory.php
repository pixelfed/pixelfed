<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'username' => str_replace('.', '', fake()->unique()->userName()),
            'name' => fake()->name(),
            'domain' => null,
            'remote_url' => null,
            'is_private' => false,
            'status_count' => 0,
            'following_count' => 0,
            'followers_count' => 0,
            'last_fetched_at' => null,
            'last_status_at' => null,
        ];
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_private' => true,
        ]);
    }

    public function remote(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => fake()->domainName(),
            'remote_url' => fake()->url(),
            'user_id' => null,
        ]);
    }
}
