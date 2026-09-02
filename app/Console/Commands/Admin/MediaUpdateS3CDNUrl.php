<?php

namespace App\Console\Commands\Admin;

use App\Models\Media;
use App\Models\Status;
use App\Services\MediaService;
use App\Services\StatusService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('admin:MigrateLocalS3MediaURL {id? : A status id (or post URL) to fix; omit with --all} {--all : Scan every local media row and fix any with a stale host} {--oldDomain= : Only rewrite URLs whose host matches this old backend (default: rewrite all stale hosts)} {--newDomain= : Target host to rewrite to (default: the configured cloud disk host from .env)} {--dry-run : Report what would change without writing} {--force : Skip the confirmation prompt}')]
#[Description('Rewrite stale local media cloud URLs (cdn_url, thumbnail_url, optimized_url) from their storage paths to the configured S3/cloud host. Replaces media:cloud-url-rewrite.')]
class MediaUpdateS3CDNUrl extends Command
{
    /**
     * The target host to rewrite URLs to.
     */
    protected ?string $newHost = null;

    /**
     * Optional old host filter; when set, only URLs on this host are rewritten.
     */
    protected ?string $oldHost = null;

    public function handle()
    {
        // This command only makes sense for instances serving media from a
        // cloud/object-storage backend. Local-storage instances (PF_ENABLE_CLOUD
        // unset/false) serve media from the app domain and have no cloud
        // cdn_url to migrate, so refuse rather than risk rewriting local URLs.
        if (! (bool) config_cache('pixelfed.cloud_storage')) {
            $this->error('Cloud storage is not enabled (PF_ENABLE_CLOUD is false).');
            $this->line('This instance serves media from local storage; there are no cloud media URLs to migrate.');

            return 1;
        }

        // Safe default target = the currently configured cloud disk host,
        // driven by AWS_URL in .env. Allow explicit override via --newDomain.
        $configuredHost = $this->cloudHost();
        $override = $this->normalizeHost($this->option('newDomain'));
        $this->newHost = $override ?: $configuredHost;

        if (! $this->newHost) {
            $this->error('Could not resolve a target host.');
            $this->line('The cloud disk ('.config('filesystems.cloud').') did not return a usable URL.');
            $this->line('Set AWS_URL in your .env, or pass --newDomain explicitly.');

            return 1;
        }

        // Defensive guard: when auto-detecting the target (no --newDomain
        // override), never rewrite media URLs to the app's own domain. That
        // would indicate local storage or a misconfigured cloud disk URL
        // (AWS_URL). An explicit --newDomain is treated as a deliberate choice.
        if (! $override) {
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            if ($appHost && strcasecmp($this->newHost, $appHost) === 0) {
                $this->error('Refusing to run: auto-detected target host ('.$this->newHost.') is the app domain.');
                $this->line('That indicates local storage or a misconfigured cloud disk URL (AWS_URL).');
                $this->line('If you really intend this, pass an explicit --newDomain.');

                return 1;
            }
        }

        $this->oldHost = $this->normalizeHost($this->option('oldDomain'));

        $id = $this->argument('id');
        $all = $this->option('all');

        if (! $id && ! $all) {
            $this->error('Provide a status id/URL, or pass --all.');

            return 1;
        }

        if ($id && $all) {
            $this->error('Pass either a status id or --all, not both.');

            return 1;
        }

        // Show the plan and require explicit approval of the target host.
        $this->info('Target host (newDomain): '.$this->newHost.($override ? ' (override)' : ' (from configured cloud disk)'));
        $this->info('Filter (oldDomain):      '.($this->oldHost ?: 'none — rewriting all stale hosts'));
        if ($this->newHost !== $configuredHost) {
            $this->warn('Note: target host differs from the configured cloud disk host ('.($configuredHost ?: 'unresolved').').');
        }

        if (! $this->option('dry-run') && ! $this->option('force')) {
            if (! $this->confirm('Rewrite media URLs to "'.$this->newHost.'"?', false)) {
                $this->comment('Aborted.');

                return 0;
            }
        }
        $this->newLine();

        if ($id) {
            return $this->handleSingle($id);
        }

        return $this->handleAll();
    }

    /**
     * Extract a bare host from a domain/URL option value.
     */
    protected function normalizeHost(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        // Accept full URLs or bare hosts.
        if (str_contains($value, '://')) {
            $host = parse_url($value, PHP_URL_HOST);

            return $host ?: null;
        }

        // Strip any accidental path/scheme fragments.
        $host = parse_url('https://'.$value, PHP_URL_HOST);

        return $host ?: null;
    }

    protected function handleSingle(string $id): int
    {
        $statusId = $this->resolveStatusId($id);
        if (! $statusId) {
            $this->error('Could not extract a status id from "'.$id.'".');

            return 1;
        }

        $status = Status::withTrashed()->find($statusId);
        if (! $status) {
            $this->error('No status found with id '.$statusId.'.');

            return 1;
        }

        $media = Media::whereStatusId($status->id)->get();
        if ($media->isEmpty()) {
            $this->comment('Status '.$status->id.' has no media.');

            return 0;
        }

        $fixed = 0;
        foreach ($media as $m) {
            if ($this->migrateOne($m)) {
                $fixed++;
            }
        }

        if ($fixed > 0 && ! $this->option('dry-run')) {
            $this->bustCaches($status->id);
        }

        $this->newLine();
        $this->info(($this->option('dry-run') ? 'Would fix ' : 'Fixed ').$fixed.' media row(s) for status '.$status->id.'.');
        if ($fixed > 0 && ! $this->option('dry-run')) {
            $this->comment('Caches busted for this status.');
        }

        return 0;
    }

    protected function handleAll(): int
    {
        $fixed = 0;
        $scanned = 0;
        $affectedStatusIds = [];

        Media::whereNull('remote_url')
            ->where(function ($q) {
                $q->whereNull('remote_media')->orWhere('remote_media', false);
            })
            ->lazyById(1000, 'id')
            ->each(function ($m) use (&$fixed, &$scanned, &$affectedStatusIds) {
                $scanned++;
                if ($this->migrateOne($m)) {
                    $fixed++;
                    if ($m->status_id) {
                        $affectedStatusIds[$m->status_id] = true;
                    }
                }
            });

        if (! $this->option('dry-run')) {
            foreach (array_keys($affectedStatusIds) as $sid) {
                $this->bustCaches($sid);
            }
        }

        $this->newLine();
        $this->info('Scanned '.$scanned.' local media rows; '.($this->option('dry-run') ? 'would fix ' : 'fixed ').$fixed.'.');
        if ($fixed > 0 && ! $this->option('dry-run')) {
            $this->comment('Caches busted for '.count($affectedStatusIds).' affected status(es).');
            $this->comment('Tip: run `php artisan cache:clear` if any stale URLs remain cached elsewhere.');
        }

        return 0;
    }

    /**
     * Rebuild any stale URL field on a single media row from its storage path.
     * Only writes when a field's host differs from the target host.
     *
     * @return bool whether the row was (or would be) changed
     */
    protected function migrateOne(Media $media): bool
    {
        // Never touch remote media or rows whose media_path is an absolute URL.
        if ($media->remote_media || Str::startsWith((string) $media->media_path, 'http')) {
            return false;
        }

        $changes = [];

        // cdn_url and optimized_url are both derived from media_path;
        // thumbnail_url is derived from thumbnail_path.
        $map = [
            'cdn_url' => $media->media_path,
            'optimized_url' => $media->media_path,
            'thumbnail_url' => $media->thumbnail_path,
        ];

        foreach ($map as $field => $path) {
            $current = $media->{$field};
            if (! $current) {
                // Field not set; leave it as-is (nothing to migrate).
                continue;
            }
            if (! $path) {
                // No source path to rebuild from; skip.
                continue;
            }
            $host = parse_url($current, PHP_URL_HOST);
            if (! $this->shouldRewrite($host)) {
                continue;
            }

            $rebuilt = $this->targetUrl($path);
            if (! $rebuilt) {
                continue;
            }

            $changes[$field] = ['from' => $current, 'to' => $rebuilt];
        }

        if (empty($changes)) {
            return false;
        }

        $this->warn('media '.$media->id.(($media->status_id) ? ' (status '.$media->status_id.')' : '').':');
        foreach ($changes as $field => $c) {
            $fromHost = parse_url($c['from'], PHP_URL_HOST);
            $this->line('  '.$field.': '.$fromHost.' -> '.$this->newHost);
        }

        if ($this->option('dry-run')) {
            return true;
        }

        foreach ($changes as $field => $c) {
            $media->{$field} = $c['to'];
        }
        $media->save();

        return true;
    }

    protected function bustCaches($statusId): void
    {
        MediaService::del($statusId);
        StatusService::del($statusId, true);
    }

    /**
     * Decide whether a URL on $currentHost should be rewritten.
     * Skips when already on the target host, and honours the optional
     * --oldDomain filter.
     */
    protected function shouldRewrite(?string $currentHost): bool
    {
        if (! $currentHost) {
            return false;
        }
        // Already on the target host.
        if (strcasecmp($currentHost, $this->newHost) === 0) {
            return false;
        }
        // With --oldDomain, only rewrite that specific host.
        if ($this->oldHost !== null && strcasecmp($currentHost, $this->oldHost) !== 0) {
            return false;
        }

        return true;
    }

    /**
     * Build the target URL for a storage path against the target host.
     * Uses the configured cloud disk to produce the correct path, then
     * swaps in --newDomain when it overrides the configured host.
     */
    protected function targetUrl(string $path): ?string
    {
        $url = $this->diskUrl($path);
        if (! $url) {
            return null;
        }

        $diskHost = parse_url($url, PHP_URL_HOST);
        if ($diskHost && strcasecmp($diskHost, $this->newHost) !== 0) {
            // Override host was requested; swap it into the disk-built URL.
            $url = preg_replace('#^(https?://)'.preg_quote($diskHost, '#').'#i', '$1'.$this->newHost, $url);
        }

        return $url;
    }

    protected function diskUrl(string $path): ?string
    {
        try {
            return (string) Storage::disk(config('filesystems.cloud'))->url($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function cloudHost(): ?string
    {
        try {
            $url = Storage::disk(config('filesystems.cloud'))->url('probe');
            $host = parse_url($url, PHP_URL_HOST);

            return $host ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function resolveStatusId(string $input): ?string
    {
        $input = trim($input);
        if (ctype_digit($input)) {
            return $input;
        }
        if (preg_match('#/p/[^/]+/(\d+)#', $input, $m)) {
            return $m[1];
        }
        if (preg_match('#(\d{6,})#', $input, $m)) {
            return $m[1];
        }

        return null;
    }
}
