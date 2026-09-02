<?php

namespace App\Console\Commands\Status;

use App\Models\Media;
use App\Models\Status;
use App\Services\AccountService;
use App\Services\MediaService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('status:statuses {id : Status id, or a post URL like https://host/p/username/ID}')]
#[Description('Show detailed debug/metadata for a post (Status) and its media, including stored vs expected media URLs')]
class StatusStatuses extends Command
{
    /**
     * Sensitive/long status columns to redact or trim.
     *
     * @var array<int, string>
     */
    protected $longStatusCols = ['caption', 'cw_summary'];

    public function handle()
    {
        $id = $this->resolveId($this->argument('id'));

        if (! $id) {
            $this->error('Could not extract a status id from "'.$this->argument('id').'".');

            return 1;
        }

        $status = Status::withTrashed()->find($id);

        if (! $status) {
            $this->error('No status found with id '.$id.'.');

            return 1;
        }

        $this->line(str_repeat('=', 64));
        $this->info('STATUS ROW (table: statuses)');
        $this->line(str_repeat('=', 64));
        $this->dumpStatus($status);

        $this->newLine();
        $this->line(str_repeat('=', 64));
        $this->info('AUTHOR');
        $this->line(str_repeat('=', 64));
        $this->dumpAuthor($status);

        $this->newLine();
        $this->line(str_repeat('=', 64));
        $this->info('MEDIA');
        $this->line(str_repeat('=', 64));
        $this->dumpMedia($status);

        $this->newLine();
        $this->line(str_repeat('=', 64));
        $this->info('URL HEALTH CHECK');
        $this->line(str_repeat('=', 64));
        $this->urlHealth($status);

        $this->newLine();
        $this->line(str_repeat('=', 64));
        $this->info('CACHE');
        $this->line(str_repeat('=', 64));
        $this->dumpCache($status);

        return 0;
    }

    /**
     * Accept a numeric id or a post URL (…/p/username/ID) and return the id.
     */
    protected function resolveId(string $input): ?string
    {
        $input = trim($input);

        if (ctype_digit($input)) {
            return $input;
        }

        // Extract the last numeric path segment from a URL.
        if (preg_match('#/p/[^/]+/(\d+)#', $input, $m)) {
            return $m[1];
        }

        if (preg_match('#(\d{6,})#', $input, $m)) {
            return $m[1];
        }

        return null;
    }

    protected function dumpStatus(Status $status): void
    {
        $rows = [];
        foreach ($status->getAttributes() as $key => $value) {
            $display = $this->format($value);
            if (in_array($key, $this->longStatusCols, true) && is_string($value) && strlen($value) > 60) {
                $display = mb_strimwidth($value, 0, 60, '…');
            }
            $rows[] = [$key, $display];
        }
        $this->table(['Column', 'Value'], $rows);

        $this->comment('Computed:');
        $this->line('  url():       '.$this->safe(fn () => $status->url()));
        $this->line('  permalink(): '.$this->safe(fn () => $status->permalink()));
        $this->line('  is remote:   '.($status->uri ? 'yes ('.$status->uri.')' : 'no (local)'));
        if ($status->deleted_at) {
            $this->error('  ✗ Status is soft-deleted ('.$status->deleted_at.').');
        }
    }

    protected function dumpAuthor(Status $status): void
    {
        $acct = AccountService::get($status->profile_id, true);
        if (! $acct) {
            $this->error('No account found for profile_id '.$status->profile_id.'.');

            return;
        }
        $this->table(['Field', 'Value'], [
            ['profile_id', $status->profile_id],
            ['username', $acct['username'] ?? 'null'],
            ['acct', $acct['acct'] ?? 'null'],
            ['url', $acct['url'] ?? 'null'],
            ['local', ($acct['local'] ?? null) ? 'true' : 'false'],
        ]);
    }

    protected function dumpMedia(Status $status): void
    {
        $media = Media::withTrashed()->whereStatusId($status->id)->orderBy('order')->get();

        if ($media->isEmpty()) {
            $this->comment('No media attached to this status.');

            return;
        }

        $cloudHost = $this->cloudHost();

        foreach ($media as $i => $m) {
            $this->newLine();
            $this->comment('Media #'.($i + 1).' (id '.$m->id.', order '.$m->order.')');
            $rows = [];
            $cols = [
                'media_path', 'thumbnail_path', 'cdn_url', 'thumbnail_url',
                'optimized_url', 'remote_url', 'remote_media', 'mime', 'size',
                'version', 'replicated_at', 'original_sha256', 'processed_at',
                'deleted_at',
            ];
            $attrs = $m->getAttributes();
            foreach ($cols as $c) {
                if (array_key_exists($c, $attrs)) {
                    $rows[] = [$c, $this->format($attrs[$c])];
                }
            }
            $this->table(['Media Column', 'Value'], $rows);

            $this->line('  computed url():          '.$this->safe(fn () => $m->url()));
            $this->line('  computed thumbnailUrl(): '.$this->safe(fn () => $m->thumbnailUrl()));
            $this->line('  expected (from path):    '.$this->expectedUrl($m->media_path));

            // Per-field host comparison.
            $this->compareHost('  cdn_url', $m->cdn_url, $cloudHost);
            $this->compareHost('  thumbnail_url', $m->thumbnail_url, $cloudHost);
            $this->compareHost('  optimized_url', $m->optimized_url, $cloudHost);
        }
    }

    protected function urlHealth(Status $status): void
    {
        $cloudHost = $this->cloudHost();
        if (! $cloudHost) {
            $this->comment('Cloud storage not configured (or no cloud disk url); skipping host comparison.');

            return;
        }

        $this->line('Configured cloud host (correct base): '.$cloudHost);
        $this->newLine();

        $media = Media::withTrashed()->whereStatusId($status->id)->get();
        $stale = [];

        foreach ($media as $m) {
            if ($m->remote_media || Str::startsWith((string) $m->media_path, 'http')) {
                continue;
            }
            foreach (['cdn_url', 'thumbnail_url', 'optimized_url'] as $field) {
                $val = $m->{$field};
                if (! $val) {
                    continue;
                }
                $host = parse_url($val, PHP_URL_HOST);
                if ($host && $cloudHost && strcasecmp($host, $cloudHost) !== 0) {
                    $stale[] = 'media '.$m->id.' '.$field.' points at '.$host.' (expected '.$cloudHost.')';
                }
            }
        }

        if ($stale) {
            $this->error('STALE MEDIA URLS DETECTED:');
            foreach ($stale as $s) {
                $this->line('  ✗ '.$s);
            }
            $this->newLine();
            $this->comment('Fix with: php artisan admin:MigrateLocalMediaURL '.$status->id);
            $this->comment('(or --all to scan every local media row)');
        } else {
            $this->info('All local media URLs point at the configured cloud host. ✓');
        }
    }

    protected function dumpCache(Status $status): void
    {
        $cached = MediaService::get($status->id);
        if (empty($cached)) {
            $this->comment('No cached media_attachments entry (MediaService).');

            return;
        }
        $this->comment('Cached media_attachments (MediaService, 6h TTL) — served to clients:');
        foreach ($cached as $i => $item) {
            $this->line('  ['.$i.'] url:         '.($item['url'] ?? 'null'));
            $this->line('  ['.$i.'] preview_url: '.($item['preview_url'] ?? 'null'));
        }
        $this->newLine();
        $this->comment('If these still show a stale host after a DB fix, run: php artisan cache:clear');
    }

    protected function compareHost(string $label, ?string $url, ?string $cloudHost): void
    {
        if (! $url) {
            return;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host || ! $cloudHost) {
            return;
        }
        if (strcasecmp($host, $cloudHost) !== 0) {
            $this->line('<fg=red>'.$label.' host: '.$host.' ✗ (expected '.$cloudHost.')</>');
        } else {
            $this->line('<fg=green>'.$label.' host: '.$host.' ✓</>');
        }
    }

    protected function expectedUrl(?string $mediaPath): string
    {
        if (! $mediaPath || Str::startsWith($mediaPath, 'http')) {
            return $mediaPath ?? 'null';
        }

        try {
            return (string) Storage::disk(config('filesystems.cloud'))->url($mediaPath);
        } catch (\Throwable $e) {
            return '(cloud disk not resolvable in this environment)';
        }
    }

    protected function cloudHost(): ?string
    {
        try {
            if (! (bool) config_cache('pixelfed.cloud_storage')) {
                // Still try to read the configured cloud disk host for reference.
            }
            $url = Storage::disk(config('filesystems.cloud'))->url('probe');
            $host = parse_url($url, PHP_URL_HOST);

            return $host ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function safe(callable $fn): string
    {
        try {
            return (string) ($fn() ?? 'null');
        } catch (\Throwable $e) {
            return 'error: '.$e->getMessage();
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
