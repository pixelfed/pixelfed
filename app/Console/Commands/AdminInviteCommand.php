<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AdminInvite;
use Illuminate\Support\Str;

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
        $this->info(' ');
        $this->info('    Pixelfed Admin Inviter');
        $this->line(' ');
        $this->info('    Manage user registration invite links');
        $this->line(' ');

        $action = $this->choice(
            'Select an action',
            [
                'Create invite',
                'View invites',
                'Expire invite',
                'Cancel'
            ],
            3
        );

        switch($action) {
            case 'Create invite':
                return $this->create();
            break;

            case 'View invites':
                return $this->view();
            break;

            case 'Expire invite':
                return $this->expire();
            break;

            case 'Cancel':
                return;
            break;
        }
    }

    protected function create()
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

        $shouldExpire = $this->choice(
            'Set an invite expiry date?',
            [
                'No - invite never expires',
                'Yes - expire after 24 hours',
                'Custom - let me pick an expiry date'
            ],
            0
        );
        switch($shouldExpire) {
            case 'No - invite never expires':
                $expires = null;
            break;

            case 'Yes - expire after 24 hours':
                $expires = now()->addHours(24);
            break;

            case 'Custom - let me pick an expiry date':
                $this->info('Set custom expiry date in days');
                $customExpiry = $this->ask('Custom Expiry', 14);
                $expires = now()->addDays($customExpiry);
            break;
        }

        $this->info('Skip email verification for invitees?');
        $skipEmailVerification = $this->choice('Skip email verification', ['No', 'Yes'], 0);

        $invite = new AdminInvite;
        $invite->name = $name;
        $invite->description = $description;
        $invite->message = $message;
        $invite->max_uses = $max_uses;
        $invite->skip_email_verification = $skipEmailVerification === 'Yes';
        $invite->expires_at = $expires;
        $invite->invite_code = Str::uuid() . Str::random(random_int(1,6));
        $invite->save();

        $this->info('####################');
        $this->info('# Invite Generated!');
        $this->line(' ');
        $this->info($invite->url());
        $this->line(' ');
        return Command::SUCCESS;
    }

    protected function view()
    {
        $this->info('View Invites');
        $this->line('=============');
        if(AdminInvite::count() == 0) {
            $this->line(' ');
            $this->error('No invites found!');
            return;
        }
        $this->table(
            ['Invite Code', 'Uses Left', 'Expires'],
            AdminInvite::all(['invite_code', 'max_uses', 'uses', 'expires_at'])->map(function($invite) {
                return [
                    'invite_code' => $invite->invite_code,
                    'uses_left' => $invite->max_uses ? ($invite->max_uses - $invite->uses) : '∞',
                    'expires_at' => $invite->expires_at ? $invite->expires_at->diffForHumans() : 'never'
                ];
            })->toArray()
        );
    }

    protected function expire()
    {
        $token = $this->anticipate('Enter invite code to expire', function($val) {
            if(!$val || empty($val)) {
                return [];
            }
            return AdminInvite::where('invite_code', 'like', '%' . $val . '%')->pluck('invite_code')->toArray();
        });

        if(!$token || empty($token)) {
            $this->error('Invalid invite code');
            return;
        }
        $invite = AdminInvite::whereInviteCode($token)->first();
        if(!$invite) {
            $this->error('Invalid invite code');
            return;
        }
        $invite->max_uses = 1;
        $invite->expires_at = now()->subHours(2);
        $invite->save();
        $this->info('Expired the following invite: ' . $invite->url());
    }
}
