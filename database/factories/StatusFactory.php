<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'type' => 'text',
            'caption' => fake()->sentence(),
            'in_reply_to_id' => null,
            'in_reply_to_profile_id' => null,
            'reblog_of_id' => null,
            'is_nsfw' => false,
            'scope' => 'public',
            'visibility' => 'public',
            'cw_summary' => null,
            'comments_disabled' => false,
            'likes_count' => 0,
            'reblogs_count' => 0,
            'reply_count' => 0,
            'local' => true,
            'rendered' => '',
        ];
    }

    public function photo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'photo',
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'video',
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => 'private',
            'visibility' => 'private',
        ]);
    }

    public function unlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => 'unlisted',
            'visibility' => 'unlisted',
        ]);
    }

    public function nsfw(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_nsfw' => true,
        ]);
    }

    public function reply(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'reply',
            'in_reply_to_id' => Status::factory(),
        ]);
    }
}
