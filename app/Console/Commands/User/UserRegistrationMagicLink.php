<?php

namespace App\Console\Commands\User;

use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:app-magic-link {--username=} {--email=}')]
#[Description('Get the app magic link for users who register in-app but have not recieved the confirmation email')]
class UserRegistrationMagicLink extends Command
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $username = $this->option('username');
        $email = $this->option('email');
        if (! $username && ! $email) {
            $this->error('Please provide the username or email as arguments');
            $this->line(' ');
            $this->info('Example: ');
            $this->info('php artisan user:app-magic-link --username=dansup');
            $this->info('php artisan user:app-magic-link --email=dansup@pixelfed.com');

            return;
        }
        $user = User::when($username, function ($q, $username) {
            return $q->whereUsername($username);
        })
            ->when($email, function ($q, $email) {
                return $q->whereEmail($email);
            })
            ->first();

        if (! $user) {
            $this->error('We cannot find any matching accounts');

            return;
        }

        if ($user->email_verified_at) {
            $this->error('User already verified email address');

            return;
        }

        if (! $user->register_source || $user->register_source !== 'app' || ! $user->app_register_token) {
            $this->error('User did not register via app');

            return;
        }

        $verify = EmailVerification::whereUserId($user->id)->first();

        if (! $verify) {
            $this->error('Cannot find user verification codes');

            return;
        }

        $appUrl = 'pixelfed://confirm-account/'.$user->app_register_token.'?rt='.$verify->random_token;
        $this->line(' ');
        $this->info('Magic link found! Copy the following link and send to user');
        $this->line(' ');
        $this->line(' ');
        $this->info($appUrl);
        $this->line(' ');
        $this->line(' ');

        return Command::SUCCESS;
    }
}
