<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('user:checkpassword {id : Username or numeric user id}')]
#[Description('Read-only: verify a candidate password against the stored hash and diagnose why a login is rejected. Does NOT change anything.')]
class UserCheckPassword extends Command
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');

        if (ctype_digit((string) $id)) {
            $user = User::withTrashed()->find($id);
        } else {
            $user = User::withTrashed()->whereUsername($id)->first();
        }

        if (! $user) {
            $this->error('Could not find any user with that username or id.');

            return 1;
        }

        $this->info('User: '.$user->username.' (id '.$user->id.')');
        $this->info('Email on record: '.$user->email);
        $this->newLine();

        // --- Login-path checks (login authenticates by EMAIL + password) ---
        $this->line('Login authenticates by EMAIL + password (LoginController defaults to the "email" field).');
        $this->newLine();

        // 1. Duplicate email — the guard matches the FIRST row with this email.
        $sameEmail = User::withTrashed()->where('email', $user->email)->get();
        if ($sameEmail->count() > 1) {
            $this->error('DUPLICATE EMAIL: '.$sameEmail->count().' rows share "'.$user->email.'":');
            foreach ($sameEmail as $row) {
                $this->line('   - id '.$row->id.' username='.$row->username.' deleted_at='.($row->deleted_at ?? 'null'));
            }
            $this->warn('The web login may match a different row than the one you expect.');
            $this->newLine();
        } else {
            $this->line('Email is unique in the users table. ✓');
        }

        // 2. Which row would the web guard actually pick? (non-trashed, matching email)
        $guardRow = User::where('email', $user->email)->first();
        if (! $guardRow) {
            $this->error('The web guard would find NO active (non-trashed) row for this email — login will always fail.');
        } elseif ($guardRow->id !== $user->id) {
            $this->warn('The web guard would authenticate row id '.$guardRow->id.', NOT the row you looked up (id '.$user->id.').');
        } else {
            $this->line('The web guard resolves to this same row. ✓');
        }
        $this->newLine();

        // 3. Stored hash sanity + algorithm/cost.
        $hash = $user->getAttributes()['password'] ?? null;
        if (empty($hash)) {
            $this->error('Stored password hash is EMPTY — cannot authenticate.');

            return 1;
        }
        $info = password_get_info($hash);
        $algo = $info['algoName'] ?? 'unknown';
        $this->line('Stored hash algorithm: '.$algo);
        if (isset($info['options']['cost'])) {
            $this->line('Stored hash cost: '.$info['options']['cost']);
        }
        if ($algo === 'unknown') {
            $this->error('Stored hash is not a recognizable bcrypt/argon hash — it may be corrupted or double-hashed.');
        }
        $this->newLine();

        // 4. Verify a candidate password against the stored hash.
        $candidate = $this->secret('Enter the password you are trying to log in with (hidden, not stored)');
        if ($candidate === null || $candidate === '') {
            $this->comment('No password entered; skipping verification.');

            return 0;
        }

        $matches = Hash::check($candidate, $hash);
        $this->newLine();
        if ($matches) {
            $this->info('✓ PASSWORD MATCHES the stored hash for this row.');
            $this->comment('If web login still fails with this exact password + email "'.$user->email.'", the problem is NOT the password:');
            $this->line('   - check for a duplicate email row above,');
            $this->line('   - confirm you are typing the email exactly (no trailing spaces),');
            $this->line('   - confirm the app server and CLI share the same database & APP_KEY,');
            $this->line('   - check for login throttling (5 failed attempts / 60 min lockout).');
        } else {
            $this->error('✗ PASSWORD DOES NOT MATCH the stored hash for this row.');
            $this->comment('Re-run: php artisan user:setpassword '.$user->username.' and set it again, then re-check here.');
            $this->comment('If it still will not match after setting, the CLI and web app likely point at different databases.');
        }

        // 5. Would Laravel want to rehash? (cost/driver mismatch signal)
        if ($matches && Hash::needsRehash($hash)) {
            $this->newLine();
            $this->warn('Note: stored hash needsRehash() = true (cost/driver differs from current config). Login still works, but worth noting.');
        }

        return $matches ? 0 : 1;
    }
}
