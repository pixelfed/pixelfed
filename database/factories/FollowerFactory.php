<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

class FollowerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'following_id' => Profile::factory(),
            'local_profile' => true,
        ];
    }

    public function remote(): static
    {
        return $this->state(fn (array $attributes) => [
            'local_profile' => false,
        ]);
    }
}
