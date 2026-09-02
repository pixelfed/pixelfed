<?php

namespace App\Console\Commands\Status;

use App\Models\Instance;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('status:profile {id : Profile id, username, user@domain, @user@domain, webfinger, or remote_url}')]
#[Description('Show detailed debug/metadata for a LOCAL or REMOTE profile (federation-aware)')]
class StatusProfile extends Command
{
    /**
     * Columns to redact / trim.
     *
     * @var array<int, string>
     */
    protected $redact = ['private_key', 'public_key'];

    protected $longText = ['bio', 'note', 'private_key', 'public_key'];

    public function handle()
    {
        $id = $this->argument('id');

        $profile = $this->resolveProfile($id);

        if (! $profile) {
            $this->error('No profile found for "'.$id.'".');
            $this->suggestSimilar($id);

            return 1;
        }

        $isLocal = empty($profile->domain);

        $this->line(str_repeat('=', 60));
        $this->info(($isLocal ? 'LOCAL' : 'REMOTE').' PROFILE ROW (table: profiles)');
        $this->line(str_repeat('=', 60));
        $this->dumpColumns($profile);

        $this->newLine();
        $this->line(str_repeat('=', 60));
        $this->info('DERIVED METADATA');
        $this->line(str_repeat('=', 60));
        $this->dumpMetadata($profile, $isLocal);

        $this->newLine();
        $this->line(str_repeat('=', 60));
        $this->info($isLocal ? 'LINKED LOCAL USER' : 'REMOTE INSTANCE');
        $this->line(str_repeat('=', 60));
        if ($isLocal) {
            $this->dumpLocalUser($profile);
        } else {
            $this->dumpInstance($profile);
        }

        $this->newLine();
        $this->line(str_repeat('=', 60));
        $this->info('HEALTH CHECKS');
        $this->line(str_repeat('=', 60));
        $this->healthChecks($profile, $isLocal);

        return 0;
    }

    /**
     * Resolve a profile from many identifier forms.
     */
    protected function resolveProfile(string $id): ?Profile
    {
        // Numeric id
        if (ctype_digit($id)) {
            $p = Profile::withTrashed()->find($id);
            if ($p) {
                return $p;
            }
        }

        // remote_url (full URL)
        if (Str::startsWith($id, ['http://', 'https://'])) {
            $p = Profile::withTrashed()->where('remote_url', $id)->first();
            if ($p) {
                return $p;
            }
        }

        $needle = ltrim($id, '@');

        // user@domain form -> split into username + domain
        if (str_contains($needle, '@')) {
            [$uname, $domain] = explode('@', $needle, 2);

            $p = Profile::withTrashed()
                ->where('domain', $domain)
                ->where(function ($q) use ($uname, $needle) {
                    $q->where('username', $uname)
                        ->orWhere('username', $needle)
                        ->orWhere('username', '@'.$needle)
                        ->orWhere('webfinger', $needle)
                        ->orWhere('webfinger', '@'.$needle);
                })
                ->first();
            if ($p) {
                return $p;
            }
        }

        // webfinger exact
        $p = Profile::withTrashed()
            ->where('webfinger', $needle)
            ->orWhere('webfinger', '@'.$needle)
            ->first();
        if ($p) {
            return $p;
        }

        // Plain username. Prefer a LOCAL match (domain null), else any.
        $local = Profile::withTrashed()->whereNull('domain')->where('username', $needle)->first();
        if ($local) {
            return $local;
        }

        return Profile::withTrashed()->where('username', $needle)->first();
    }

    protected function dumpColumns(Profile $profile): void
    {
        $rows = [];
        foreach ($profile->getAttributes() as $key => $value) {
            if (in_array($key, $this->redact, true)) {
                $rows[] = [$key, empty($value) ? '(empty!)' : 'present ('.strlen((string) $value).' chars)'];

                continue;
            }
            $display = $this->format($value);
            if (in_array($key, $this->longText, true) && is_string($value) && strlen($value) > 60) {
                $display = mb_strimwidth($value, 0, 60, '…');
            }
            $rows[] = [$key, $display];
        }
        $this->table(['Profile Column', 'Value'], $rows);
    }

    protected function dumpMetadata(Profile $profile, bool $isLocal): void
    {
        $meta = [];
        $meta[] = ['type', $isLocal ? 'LOCAL' : 'REMOTE'];
        $meta[] = ['domain', $isLocal ? '(local)' : $profile->domain];
        $meta[] = ['profile url', $this->safeCall(fn () => $profile->url())];
        $meta[] = ['permalink', $this->safeCall(fn () => $profile->permalink())];
        $meta[] = ['emailUrl (handle)', $this->safeCall(fn () => $profile->emailUrl())];

        if (! $isLocal) {
            $meta[] = ['remote_url', $this->format($this->attr($profile, 'remote_url'))];
            $meta[] = ['webfinger', $this->format($this->attr($profile, 'webfinger'))];
            $meta[] = ['key_id', $this->format($this->attr($profile, 'key_id'))];
            $meta[] = ['inbox_url', $this->format($this->attr($profile, 'inbox_url'))];
            $meta[] = ['outbox_url', $this->format($this->attr($profile, 'outbox_url'))];
            $meta[] = ['shared_inbox', $this->format($this->attr($profile, 'sharedInbox'))];
            $meta[] = ['last_fetched_at', $this->format($profile->last_fetched_at)];
        }

        $meta[] = ['followers (live)', (string) $this->safeCount(fn () => $profile->followers()->count())];
        $meta[] = ['following (live)', (string) $this->safeCount(fn () => $profile->following()->count())];
        $meta[] = ['followers_count (cached)', $this->format($this->attr($profile, 'followers_count'))];
        $meta[] = ['following_count (cached)', $this->format($this->attr($profile, 'following_count'))];
        $meta[] = ['statuses (live)', (string) $this->safeCount(fn () => $profile->statuses()->count())];
        $meta[] = ['status_count (cached)', $this->format($profile->status_count)];
        $meta[] = ['last_status_at', $this->format($profile->last_status_at)];
        $meta[] = ['avatar row', $this->avatarInfo($profile)];

        $this->table(['Metadata', 'Value'], $meta);
    }

    protected function dumpLocalUser(Profile $profile): void
    {
        if (! $profile->user_id) {
            $this->error('This local profile has NO user_id — orphaned profile (no login account).');

            return;
        }
        $user = User::withTrashed()->find($profile->user_id);
        if (! $user) {
            $this->error('profile.user_id='.$profile->user_id.' points to a MISSING user row (orphaned profile).');

            return;
        }
        $rows = [
            ['user id', $this->format($user->id)],
            ['username', $this->format($user->username)],
            ['email', $this->format($user->email)],
            ['is_admin', $this->format($user->getAttributes()['is_admin'] ?? null)],
            ['status', $this->format($user->status)],
            ['email_verified_at', $this->format($user->email_verified_at)],
            ['2fa_enabled', $this->format($user->getAttributes()['2fa_enabled'] ?? null)],
            ['deleted_at', $this->format($user->deleted_at)],
            ['last_active_at', $this->format($user->last_active_at)],
        ];
        $this->table(['User Field', 'Value'], $rows);
        $this->comment('Tip: run `status:user '.$user->username.'` for full auth diagnostics.');
    }

    protected function dumpInstance(Profile $profile): void
    {
        $instance = Instance::whereDomain($profile->domain)->first();
        if (! $instance) {
            $this->comment('No Instance row recorded for domain "'.$profile->domain.'".');

            return;
        }
        $rows = [];
        foreach ($instance->getAttributes() as $key => $value) {
            $rows[] = [$key, $this->format($value)];
        }
        $this->table(['Instance Column', 'Value'], $rows);
    }

    protected function healthChecks(Profile $profile, bool $isLocal): void
    {
        $problems = [];
        $ok = [];

        if ($profile->deleted_at) {
            $problems[] = 'Profile is soft-deleted (deleted_at='.$profile->deleted_at.').';
        } else {
            $ok[] = 'Not soft-deleted.';
        }

        if ($isLocal) {
            if (! $profile->user_id) {
                $problems[] = 'Local profile has no user_id (orphaned — no login account).';
            }
            if (empty($this->attr($profile, 'private_key'))) {
                $problems[] = 'Missing private_key — ActivityPub signing/federation will fail.';
            }
            if (empty($this->attr($profile, 'public_key'))) {
                $problems[] = 'Missing public_key — remote servers cannot verify this actor.';
            }
        } else {
            if ($profile->user_id) {
                $problems[] = 'Remote profile unexpectedly has a user_id ('.$profile->user_id.').';
            }
            if (empty($this->attr($profile, 'remote_url'))) {
                $problems[] = 'Remote profile missing remote_url (ActivityPub id).';
            }
            if (empty($this->attr($profile, 'inbox_url'))) {
                $problems[] = 'Remote profile missing inbox_url — cannot deliver activities.';
            }
            if (empty($this->attr($profile, 'public_key'))) {
                $problems[] = 'Remote profile missing public_key — cannot verify its signatures.';
            }
        }

        $cached = (int) $this->attr($profile, 'followers_count');
        $live = (int) $this->safeCount(fn () => $profile->followers()->count());
        if ($cached !== $live) {
            $problems[] = 'followers_count ('.$cached.') out of sync with live count ('.$live.').';
        }

        if ($problems) {
            $this->error('ISSUES:');
            foreach ($problems as $p) {
                $this->line('  ✗ '.$p);
            }
        } else {
            $this->info('No profile issues detected.');
        }
        foreach ($ok as $o) {
            $this->line('  ✓ '.$o);
        }
    }

    protected function suggestSimilar(string $id): void
    {
        $needle = ltrim($id, '@');
        $needle = str_contains($needle, '@') ? explode('@', $needle)[0] : $needle;
        $similar = Profile::withTrashed()
            ->where('username', 'like', '%'.$needle.'%')
            ->limit(10)
            ->get(['id', 'username', 'domain']);
        if ($similar->isEmpty()) {
            return;
        }
        $this->newLine();
        $this->comment('Similar profiles:');
        $rows = [];
        foreach ($similar as $p) {
            $rows[] = [$p->id, $p->username, $p->domain ?? '(local)'];
        }
        $this->table(['id', 'username', 'domain'], $rows);
    }

    protected function avatarInfo(Profile $profile): string
    {
        try {
            $avatar = $profile->avatar;

            return $avatar ? 'present (media_path='.($avatar->media_path ?? 'null').')' : 'MISSING';
        } catch (\Throwable $e) {
            return 'error';
        }
    }

    protected function attr($model, string $key)
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

    protected function safeCount(callable $fn): string
    {
        try {
            return (string) $fn();
        } catch (\Throwable $e) {
            return 'error';
        }
    }

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
