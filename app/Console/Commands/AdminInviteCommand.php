<?php

namespace App\Console\Commands;

use App\Mail\AdminInviteEmail;
use App\Models\AdminInvite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AdminInviteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:invite';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an invite link';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('       ____  _           ______         __  ');
        $this->info('      / __ \(_)  _____  / / __/__  ____/ /  ');
        $this->info('     / /_/ / / |/_/ _ \/ / /_/ _ \/ __  /   ');
        $this->info('    / ____/ />  </  __/ / __/  __/ /_/ /    ');
        $this->info('   /_/   /_/_/|_|\___/_/_/  \___/\__,_/     ');
        $this->line(' ');
        $this->info('    Pixelfed Admin Inviter');
        $this->line(' ');
        $this->info('    Manage user registration invite links');
        $this->line(' ');

        return match ($this->choice(
            'Select an action',
            [
                'Create invite',
                'View invites',
                'Expire invite',
                'Cancel',
            ],
            3
        )) {
            'Create invite' => $this->create(),
            'View invites' => $this->view(),
            'Expire invite' => $this->expire(),
            default => Command::SUCCESS,
        };
    }

    protected function create(): int
    {
        $this->info('Create Invite');
        $this->line('=============');
        $this->info('Set an optional invite name (only visible to admins)');
        $name = $this->ask('Invite Name (optional)', 'Untitled Invite');

        $this->info('Set an optional invite description (only visible to admins)');
        $description = $this->ask('Invite Description (optional)');

        $this->info('Set an optional message to invitees (visible to all)');
        $message = $this->ask('Invite Message (optional)', 'You\'ve been invited to join');

        $this->info('Set maximum # of invite uses, use 0 for unlimited');
        $max_uses = $this->ask('Max uses', 1);

        $expires = match ($this->choice(
            'Set an invite expiry date?',
            [
                'No - invite never expires',
                'Yes - expire after 24 hours',
                'Custom - let me pick an expiry date',
            ],
            0
        )) {
            'No - invite never expires' => null,
            'Yes - expire after 24 hours' => now()->addHours(24),
            'Custom - let me pick an expiry date' => now()->addDays(
                (int) $this->ask('Custom expiry date in days', 14)
            ),
        };

        $skipEmailVerification = $this->confirm('Skip email verification for invitees?');

        $invite = AdminInvite::create([
            'name' => $name,
            'description' => $description,
            'message' => $message,
            'max_uses' => $max_uses,
            'skip_email_verification' => $skipEmailVerification,
            'expires_at' => $expires,
        ]);

        $this->info('#################');
        $this->info('Invite Generated!');
        $this->line(' ');
        $this->warn($invite->url());
        $this->line(' ');

        if ($this->confirm('Send invitation email to user?')) {
            $email = $this->promptForEmail();

            Mail::to($email)->queue(new AdminInviteEmail($invite));

            $this->line(' ');
            $this->info("Invite email sent to {$email}");
        }

        return Command::SUCCESS;
    }

    protected function view(): int
    {
        $this->info('View Invites');
        $this->line('============');

        if (AdminInvite::count() === 0) {
            $this->line(' ');
            $this->error('No invites found!');

            return Command::SUCCESS;
        }

        $this->table(
            ['Invite Code', 'Uses Left', 'Expires'],
            AdminInvite::all(['invite_code', 'max_uses', 'uses', 'expires_at'])->map(function ($invite) {
                return [
                    'invite_code' => $invite->invite_code,
                    'uses_left' => $invite->max_uses ? ($invite->max_uses - $invite->uses) : '∞',
                    'expires_at' => $invite->expires_at ? $invite->expires_at->diffForHumans() : 'never',
                ];
            })->toArray()
        );

        return Command::SUCCESS;
    }

    protected function expire(): int
    {
        $token = $this->anticipate('Enter invite code to expire', function ($val) {
            return AdminInvite::query()
                ->where('invite_code', 'like', "%$val%")
                ->pluck('invite_code')
                ->toArray();
        });

        $invite = AdminInvite::whereInviteCode($token)->first();

        if (! $invite) {
            $this->error('Invalid invite code');

            return Command::FAILURE;
        }

        $invite->max_uses = 1;
        $invite->expires_at = now()->subHours(2);
        $invite->save();

        $this->info('Expired the following invite: '.$invite->url());

        return Command::SUCCESS;
    }

    protected function promptForEmail(): string
    {
        do {
            $email = $this->ask('What email should the invite be sent to?');

            $validator = Validator::make(
                ['email' => $email],
                ['email' => ['required', 'email:rfc,dns']]
            );

            if ($validator->fails()) {
                $this->error($validator->errors()->first('email'));
                $this->line(' ');

                continue;
            }

            return $email;
        } while (true);
    }
}
