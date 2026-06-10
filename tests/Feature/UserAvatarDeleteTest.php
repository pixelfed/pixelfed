<?php

namespace Tests\Feature;

use App\Avatar;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAvatarDeleteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_resets_custom_avatar_to_default_with_force(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->refresh();

        $mediaPath = 'public/avatars/custom-avatar.jpg';

        Storage::disk('local')->put($mediaPath, 'avatar-bytes');

        $avatar = Avatar::where('profile_id', $user->profile_id)->firstOrFail();
        $avatar->update([
            'media_path' => $mediaPath,
            'cdn_url' => 'https://cdn.example.test/avatar.jpg',
            'change_count' => 1,
        ]);

        Cache::put('avatar:'.$user->profile_id, 'cached', 60);
        Cache::put("avatar:{$user->profile_id}", 'cached', 60);
        Cache::put('user:account:id:'.$user->id, 'cached', 60);

        $exitCode = Artisan::call('user:avatar-delete', [
            'username' => $user->username,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $avatar->refresh();

        $this->assertSame('public/avatars/default.jpg', $avatar->media_path);
        $this->assertNull($avatar->cdn_url);
        $this->assertSame(2, $avatar->change_count);
        Storage::disk('local')->assertMissing($mediaPath);
        $this->assertFalse(Cache::has('avatar:'.$user->profile_id));
        $this->assertFalse(Cache::has("avatar:{$user->profile_id}"));
        $this->assertFalse(Cache::has('user:account:id:'.$user->id));
    }

    #[Test]
    public function it_aborts_when_avatar_is_already_default(): void
    {
        $user = User::factory()->create();
        $user->refresh();

        $exitCode = Artisan::call('user:avatar-delete', [
            'username' => $user->username,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(
            'Default avatar already used',
            Artisan::output()
        );
    }

    #[Test]
    public function it_fails_for_unknown_username(): void
    {
        $exitCode = Artisan::call('user:avatar-delete', [
            'username' => 'missing-user-'.uniqid(),
            '--force' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString(
            'Could not find any user with that username',
            Artisan::output()
        );
    }
}
