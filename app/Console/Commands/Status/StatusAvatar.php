<?php

namespace App\Console\Commands\Status;

use App\Models\Avatar;
use App\Services\AccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StatusAvatar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'status:avatar {id : Avatar id, or a profile_id}
        {--check : Perform a live HEAD request against the avatar remote_url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show all metadata for an avatar: DB columns, storage state, owning profile, and optional live URL check';

    public function handle(): int
    {
        $avatar = $this->resolve($this->argument('id'));

        if (! $avatar) {
            $this->error('No avatar found for "'.$this->argument('id').'" (tried avatar id then profile_id).');

            return self::FAILURE;
        }

        $this->section('AVATAR ROW (table: avatars)');
        $this->dumpRow($avatar);

        if ($avatar->deleted_at) {
            $this->error('  ✗ Avatar is soft-deleted ('.$avatar->deleted_at.').');
        }

        $this->newLine();
        $this->section('STORAGE STATE');
        $isRemote = (bool) $avatar->is_remote;
        $this->table(['Field', 'Value'], [
            ['is_remote', $this->b($avatar->is_remote)],
            ['media_path', $avatar->media_path ?? 'null'],
            ['cdn_url', $avatar->cdn_url ?? 'null'],
            ['remote_url', $avatar->remote_url ?? 'null'],
            ['size', $avatar->size ?? 'null'],
            ['last_fetched_at', $avatar->last_fetched_at?->toDateTimeString() ?? 'null'],
        ]);

        if ($avatar->media_path && ! Str::startsWith($avatar->media_path, 'http')) {
            $exists = $this->localExists($avatar->media_path);
            $line = '  stored file ('.$avatar->media_path.'): '.($exists ? 'present ✓' : 'MISSING ✗');
            $exists ? $this->info($line) : $this->error($line);
        }

        $this->newLine();
        $this->section('OWNER');
        $this->dumpOwner($avatar);

        if ($this->option('check')) {
            $this->newLine();
            $this->section('LIVE URL CHECK (remote_url)');
            $this->urlCheck($avatar);
        }

        return self::SUCCESS;
    }

    protected function resolve(string $input): ?Avatar
    {
        if (! ctype_digit($input)) {
            return null;
        }

        // Prefer an avatar with this id; fall back to the profile's avatar.
        return Avatar::withTrashed()->find($input)
            ?? Avatar::withTrashed()->whereProfileId($input)->first();
    }

    protected function dumpRow(Avatar $avatar): void
    {
        $rows = [];
        foreach ($avatar->getAttributes() as $key => $value) {
            $rows[] = [$key, $this->format($value)];
        }
        $this->table(['Column', 'Value'], $rows);
    }

    protected function dumpOwner(Avatar $avatar): void
    {
        if (! $avatar->profile_id) {
            $this->comment('No profile_id on this avatar.');

            return;
        }

        $acct = AccountService::get($avatar->profile_id, true);

        if (! $acct) {
            $this->error('No account found for profile_id '.$avatar->profile_id.'.');

            return;
        }

        $this->table(['Field', 'Value'], [
            ['profile_id', $avatar->profile_id],
            ['username', $acct['username'] ?? 'null'],
            ['acct', $acct['acct'] ?? 'null'],
            ['url', $acct['url'] ?? 'null'],
            ['local', ($acct['local'] ?? null) ? 'true' : 'false'],
        ]);
    }

    protected function urlCheck(Avatar $avatar): void
    {
        $url = $avatar->remote_url;

        if (! $url || ! Str::startsWith($url, 'http')) {
            $this->comment('No remote_url to check.');

            return;
        }

        $this->line('HEAD '.$url);

        try {
            $res = Http::withOptions(['allow_redirects' => true])
                ->timeout(10)
                ->head($url);

            $line = '  HTTP '.$res->status().' ('.($res->header('content-type') ?: 'no content-type').', '.($res->header('content-length') ?: '?').' bytes)';

            if ($res->successful()) {
                $this->info($line.' ✓');
            } else {
                $this->error($line.' ✗');
                if (in_array($res->status(), [404, 410])) {
                    $this->line('  → Origin no longer serves this avatar (deleted upstream).');
                }
            }
        } catch (\Throwable $e) {
            $this->error('  request failed: '.$e->getMessage());
        }
    }

    protected function localExists(?string $mediaPath): bool
    {
        if (! $mediaPath) {
            return false;
        }

        try {
            if ((bool) config_cache('pixelfed.cloud_storage')) {
                if (Storage::disk(config('filesystems.cloud'))->exists($mediaPath)) {
                    return true;
                }
            }

            return Storage::disk('local')->exists('public/'.$mediaPath)
                || Storage::disk('local')->exists($mediaPath);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function section(string $title): void
    {
        $this->line(str_repeat('=', 64));
        $this->info($title);
        $this->line(str_repeat('=', 64));
    }

    protected function b($value): string
    {
        if ($value === null) {
            return 'null';
        }

        return $value ? 'true' : 'false';
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
