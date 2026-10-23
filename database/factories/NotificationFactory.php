<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'actor_id' => Profile::factory(),
            'action' => 'comment',
            'item_id' => null,
            'item_type' => null,
            'read_at' => null,
        ];
    }

    public function mention(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'mention',
            'item_type' => Status::class,
        ]);
    }

    public function comment(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'comment',
            'item_type' => Status::class,
        ]);
    }

    public function follow(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'follow',
            'item_type' => Profile::class,
        ]);
    }

    public function like(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'like',
            'item_type' => Status::class,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
