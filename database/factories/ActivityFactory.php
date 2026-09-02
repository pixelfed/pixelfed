<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'data' => json_encode(['type' => 'Create']),
            'to_id' => Profile::factory(),
            'from_id' => Profile::factory(),
            'object_type' => 'status',
            'processed_at' => null,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processed_at' => now(),
        ]);
    }
}
