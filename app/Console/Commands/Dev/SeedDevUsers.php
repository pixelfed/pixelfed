<?php

namespace App\Console\Commands\Dev;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('seed:devusers')]
#[Description('Seed dev users (admin + regular) with random passwords')]
class SeedDevUsers extends Command
{
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = [
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'is_admin' => true,
            ],
            [
                'name' => 'Test User',
                'username' => 'user',
                'email' => 'user@example.com',
                'is_admin' => false,
            ],
        ];

        foreach ($users as $data) {
            if (User::whereUsername($data['username'])->exists()) {
                $this->warn("User '{$data['username']}' already exists, skipping.");

                continue;
            }

            $password = Str::random(16);

            $user = User::factory()->create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => bcrypt($password),
                'is_admin' => $data['is_admin'],
                'email_verified_at' => now(),
            ]);

            $role = $data['is_admin'] ? 'admin' : 'user';
            $this->info("Created {$role}: {$data['username']} / {$data['email']} — password: {$password}");
        }

        $this->newLine();
        $this->info('Done! Save these passwords — they are generated randomly each run.');
    }
}
