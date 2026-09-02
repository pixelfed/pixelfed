<?php

namespace App\Console\Commands\Status;

use App\Models\AccountLog;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('status:user {id : Username or numeric user id} {--logs=10 : Number of recent account log entries to show}')]
#[Description('Show detailed debug/status info for a user account (login & password reset diagnostics)')]
class StatusUser extends Command
{
    /**
     * Columns whose values must never be printed in full.
     *
     * @var array<string, string>
     */
    protected $sensitive = [
        'password' => 'REDACTED (hash)',
        '2fa_secret' => 'REDACTED',
        '2fa_backup_codes' => 'REDACTED',
        'remember_token' => 'REDACTED',
    ];

    /**
     * Auth-affecting columns we want to call out explicitly.
     *
     * @var array<int, string>
     */
    protected $authColumns = [
        'status',
        'email_verified_at',
        'deleted_at',
        '2fa_enabled',
        'is_admin',
        'app_register_ip',
    ];

    public function handle()
    {
        $id = $this->argument('id');

        // Include soft-deleted users so we can detect deletion-related lockouts.
        $query = User::withTrashed();
        if (ctype_digit((string) $id)) {
            $user = $query->find($id);
        } else {
            $user = $query->whereUsername($id)->first();
        }

        if (! $user) {
            $this->error('No user row found for "'.$id.'".');
            $this->diagnoseMissingUser($id);

            return 1;
        }

        $this->line(str_repeat('=', 60));
        $this->info('USER ROW (table: users)');
        $this->line(str_repeat('=', 60));
        $this->dumpModelColumns($user);

        $this->newLine();
        $this->line(str_repeat('=', 60));
        $this->info('AUTH DIAGNOSTICS');
        $this->line(str_repeat('=', 60));
        $this->authDiagnostics($user);

        $this->newLine();
        $this->line(str_repeat('=', 60));
        $this->info('LINKED PROFILE');
        $this->line(str_repeat('=', 60));
        $this->dumpProfile($user);

        $this->newLine();
        $this->line(str_repeat('=', 60));
        $this->info('RELATED RECORDS');
        $this->line(str_repeat('=', 60));
        $this->dumpRelated($user);

        $this->newLine();
        $this->line(str_repeat('=', 60));
        $this->info('RECENT ACCOUNT LOG (last '.(int) $this->option('logs').')');
        $this->line(str_repeat('=', 60));
        $this->dumpLogs($user);

        return 0;
    }

    /**
     * Dump every column of the users row, redacting sensitive values.
     */
    protected function dumpModelColumns(User $user): void
    {
        $attrs = $user->getAttributes();
        $rows = [];
        foreach ($attrs as $key => $value) {
            if (array_key_exists($key, $this->sensitive)) {
                $display = $value === null || $value === ''
                    ? '(empty!)'
                    : $this->sensitive[$key];
            } else {
                $display = $this->format($value);
            }
            $rows[] = [$key, $display];
        }
        $this->table(['Column', 'Value'], $rows);
    }

    /**
     * Highlight the specific conditions that block login or password reset.
     */
    protected function authDiagnostics(User $user): void
    {
        $problems = [];
        $ok = [];

        // 1. Soft delete
        if ($user->deleted_at) {
            $problems[] = 'SOFT DELETED: deleted_at = '.$user->deleted_at.' (row is trashed; excluded from normal auth queries & password reset)';
        } else {
            $ok[] = 'Not soft-deleted (deleted_at is null)';
        }

        // 2. status column (LoginController blocks "deleted"; RegisterController treats deleted/delete specially)
        $status = $user->status;
        if ($status === null) {
            $ok[] = 'status = null (active)';
        } elseif (in_array($status, ['deleted', 'delete'])) {
            $problems[] = 'status = "'.$status.'" (treated as deleted account)';
        } else {
            $problems[] = 'status = "'.$status.'" (non-null status may restrict the account)';
        }

        // 3. Email verification (password reset requires a matching, valid email)
        if (empty($user->email)) {
            $problems[] = 'email is EMPTY (password reset cannot find/notify this account)';
        } else {
            $ok[] = 'email present: '.$user->email;
        }

        if ($user->email_verified_at) {
            $ok[] = 'email verified at '.$user->email_verified_at;
        } else {
            $problems[] = 'email NOT verified (email_verified_at is null) — login may be gated to /i/verify-email and some flows reject unverified accounts';
        }

        // 4. 2FA
        if ($this->attr($user, '2fa_enabled')) {
            $ok[] = '2FA enabled (login requires TOTP/backup code — a lost 2FA device looks like a rejected login)';
        } else {
            $ok[] = '2FA disabled';
        }

        // 5. password presence
        if (empty($user->password)) {
            $problems[] = 'password hash is EMPTY (cannot authenticate with a password)';
        }

        // 6. profile link
        if (empty($user->profile_id)) {
            $problems[] = 'profile_id is EMPTY (user has no linked profile — avatarUrl/profile routes will misbehave)';
        }

        if ($problems) {
            $this->error('POTENTIAL LOGIN / RESET BLOCKERS:');
            foreach ($problems as $p) {
                $this->line('  ✗ '.$p);
            }
        } else {
            $this->info('No obvious auth blockers detected on the user row.');
        }

        if ($ok) {
            $this->newLine();
            $this->comment('OK checks:');
            foreach ($ok as $o) {
                $this->line('  ✓ '.$o);
            }
        }

        // Duplicate-email detection (unique constraint / reset ambiguity)
        if (! empty($user->email)) {
            $dupes = User::withTrashed()->where('email', $user->email)->count();
            if ($dupes > 1) {
                $this->newLine();
                $this->error('  ✗ DUPLICATE EMAIL: '.$dupes.' user rows share email "'.$user->email.'" — password reset may target the wrong row.');
            }
        }
    }

    protected function dumpProfile(User $user): void
    {
        $profile = null;
        if ($user->profile_id) {
            $profile = Profile::withTrashed()->find($user->profile_id);
        }
        if (! $profile) {
            $profile = Profile::withTrashed()->where('user_id', $user->id)->first();
        }

        if (! $profile) {
            $this->error('No profile row found for this user (profile_id='.($user->profile_id ?? 'null').', user_id='.$user->id.').');

            return;
        }

        // Dump every column on the profiles row, redacting crypto keys and
        // trimming long text so the table stays readable.
        $keysToRedact = ['private_key', 'public_key'];
        $longText = ['bio', 'note', 'private_key', 'public_key'];
        $rows = [];
        foreach ($profile->getAttributes() as $key => $value) {
            if (in_array($key, $keysToRedact, true)) {
                $rows[] = [$key, empty($value) ? '(empty!)' : 'present ('.strlen((string) $value).' chars)'];

                continue;
            }
            $display = $this->format($value);
            if (in_array($key, $longText, true) && is_string($value) && strlen($value) > 60) {
                $display = mb_strimwidth($value, 0, 60, '…');
            }
            $rows[] = [$key, $display];
        }
        $this->table(['Profile Column', 'Value'], $rows);

        // Derived / computed metadata not stored directly on the row.
        $this->newLine();
        $this->comment('Derived metadata:');
        $meta = [];
        $isLocal = empty($profile->domain);
        $meta[] = ['type', $isLocal ? 'LOCAL' : 'REMOTE ('.$profile->domain.')'];
        $meta[] = ['profile url', $this->safeCall(fn () => $profile->url())];
        $meta[] = ['permalink', $this->safeCall(fn () => $profile->permalink())];
        if (! $isLocal) {
            $meta[] = ['remote_url', $this->format($profile->remote_url ?? null)];
            $meta[] = ['inbox_url', $this->format($this->attrOf($profile, 'inbox_url'))];
            $meta[] = ['outbox_url', $this->format($this->attrOf($profile, 'outbox_url'))];
            $meta[] = ['shared_inbox', $this->format($this->attrOf($profile, 'sharedInbox'))];
            $meta[] = ['last_fetched_at', $this->format($profile->last_fetched_at)];
        }
        $meta[] = ['followers (live count)', (string) $this->safeCount(fn () => $profile->followers()->count())];
        $meta[] = ['following (live count)', (string) $this->safeCount(fn () => $profile->following()->count())];
        $meta[] = ['followers_count (cached)', $this->format($this->attrOf($profile, 'followers_count'))];
        $meta[] = ['following_count (cached)', $this->format($this->attrOf($profile, 'following_count'))];
        $meta[] = ['statuses (live count)', (string) $this->safeCount(fn () => $profile->statuses()->count())];
        $meta[] = ['status_count (cached)', $this->format($profile->status_count)];
        $meta[] = ['last_status_at', $this->format($profile->last_status_at)];
        $meta[] = ['avatar row', $this->profileAvatar($profile)];
        $this->table(['Metadata', 'Value'], $meta);

        // Consistency / health checks.
        $problems = [];
        if ($profile->deleted_at) {
            $problems[] = 'Profile is soft-deleted (deleted_at='.$profile->deleted_at.').';
        }
        if ($profile->user_id && $profile->user_id != $user->id) {
            $problems[] = 'MISMATCH: profile.user_id ('.$profile->user_id.') != user.id ('.$user->id.').';
        }
        if ($user->profile_id && (string) $user->profile_id !== (string) $profile->id) {
            $problems[] = 'MISMATCH: user.profile_id ('.$user->profile_id.') != profile.id ('.$profile->id.').';
        }
        if ($isLocal && empty($this->attrOf($profile, 'private_key'))) {
            $problems[] = 'Local profile is MISSING its private_key — ActivityPub signing/federation will fail.';
        }
        if ($isLocal && empty($this->attrOf($profile, 'public_key'))) {
            $problems[] = 'Local profile is MISSING its public_key — remote servers cannot verify this actor.';
        }
        $cachedFollowers = (int) $this->attrOf($profile, 'followers_count');
        $liveFollowers = (int) $this->safeCount(fn () => $profile->followers()->count());
        if ($cachedFollowers !== $liveFollowers) {
            $problems[] = 'followers_count ('.$cachedFollowers.') is out of sync with live count ('.$liveFollowers.').';
        }

        if ($problems) {
            $this->newLine();
            $this->error('PROFILE ISSUES:');
            foreach ($problems as $p) {
                $this->line('  ✗ '.$p);
            }
        }
    }

    protected function profileAvatar(Profile $profile): string
    {
        try {
            $avatar = $profile->avatar;
            if (! $avatar) {
                return 'MISSING';
            }

            return 'present (media_path='.($avatar->media_path ?? 'null').')';
        } catch (\Throwable $e) {
            return 'error';
        }
    }

    protected function attrOf($model, string $key)
    {
        return $model->getAttributes()[$key] ?? null;
    }

    protected function safeCall(callable $fn): string
    {
        try {
            return (string) ($fn() ?? 'null');
        } catch (\Throwable $e) {
            return 'error';
        }
    }

    protected function dumpRelated(User $user): void
    {
        $rows = [];

        $rows[] = ['statuses (posts)', (string) $this->safeCount(fn () => $user->statuses()->count())];
        $rows[] = ['user_settings row', $user->settings()->exists() ? 'yes' : 'MISSING'];
        $rows[] = ['devices', (string) $this->safeCount(fn () => $user->devices()->count())];
        $rows[] = ['oauth access tokens', (string) $this->oauthTokenCount($user)];

        $this->table(['Relation', 'Count / Presence'], $rows);
    }

    protected function dumpLogs(User $user): void
    {
        $limit = (int) $this->option('logs');
        if (! Schema::hasTable('account_logs')) {
            $this->comment('account_logs table not present.');

            return;
        }

        $logs = AccountLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        if ($logs->isEmpty()) {
            $this->comment('No account log entries. (No recorded logins — consistent with never successfully logging in.)');

            return;
        }

        $rows = [];
        foreach ($logs as $log) {
            $rows[] = [
                (string) $log->created_at,
                (string) $log->action,
                (string) $log->message,
                (string) $log->ip_address,
                mb_strimwidth((string) $log->user_agent, 0, 40, '…'),
            ];
        }
        $this->table(['When', 'Action', 'Message', 'IP', 'User Agent'], $rows);
    }

    /**
     * When no user row exists, look for an orphan profile or similar usernames.
     */
    protected function diagnoseMissingUser(string $id): void
    {
        $this->newLine();
        $this->comment('Diagnosing missing user...');

        // Orphan profile? (profile exists but its user row is gone)
        $profile = Profile::withTrashed()
            ->when(ctype_digit($id), fn ($q) => $q->where('id', $id))
            ->orWhere('username', $id)
            ->first();

        if ($profile) {
            $this->warn('Found a PROFILE named "'.$profile->username.'" (id='.$profile->id.', user_id='.($profile->user_id ?? 'null').').');
            if ($profile->user_id) {
                $exists = User::withTrashed()->whereKey($profile->user_id)->exists();
                if (! $exists) {
                    $this->error('  ✗ ORPHAN: profile.user_id='.$profile->user_id.' points to a user row that does NOT exist. This explains "account doesn\'t exist" during login/reset.');
                }
            } else {
                $this->error('  ✗ Profile has no user_id (remote profile, or the local user row was deleted).');
            }
            if ($profile->deleted_at) {
                $this->error('  ✗ Profile is soft-deleted (deleted_at='.$profile->deleted_at.').');
            }
        } else {
            $this->line('No profile matches "'.$id.'" either.');
        }

        // Similar usernames (typo / case sensitivity)
        $similar = User::withTrashed()
            ->where('username', 'like', '%'.$id.'%')
            ->limit(10)
            ->get(['id', 'username', 'email', 'status', 'deleted_at']);
        if ($similar->isNotEmpty()) {
            $this->newLine();
            $this->comment('Similar usernames (case-insensitive match):');
            $rows = [];
            foreach ($similar as $u) {
                $rows[] = [$u->id, $u->username, $u->email, $u->status ?? 'null', $u->deleted_at ?? 'null'];
            }
            $this->table(['id', 'username', 'email', 'status', 'deleted_at'], $rows);
        }
    }

    protected function oauthTokenCount(User $user): int
    {
        try {
            if (! Schema::hasTable('oauth_access_tokens')) {
                return 0;
            }

            return (int) DB::table('oauth_access_tokens')->where('user_id', $user->id)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function safeCount(callable $fn): string
    {
        try {
            return (string) $fn();
        } catch (\Throwable $e) {
            return 'error';
        }
    }

    protected function attr(User $user, string $key)
    {
        return $user->getAttributes()[$key] ?? null;
    }

    /**
     * Human-friendly value formatting.
     */
    protected function format($value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === '') {
            return '(empty string)';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
