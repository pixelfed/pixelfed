<?php

namespace App\Console\Commands\Status;

use App\Models\Media;
use App\Models\Status;
use App\Services\AccountService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('status:media {id : Media id, or a media URL/path} {--check : Perform a live HEAD request against the resolved media URL}')]
#[Description('Show all metadata for a single media row: DB columns, computed URLs, parent status, attachment state, and cache')]
class StatusMedia extends Command
{
    public function handle()
    {
        $id = $this->resolveId($this->argument('id'));

        if (! $id) {
            $this->error('Could not extract a media id from "'.$this->argument('id').'".');

            return 1;
        }

        $media = Media::withTrashed()->find($id);

        if (! $media) {
            $this->error('No media found with id '.$id.'.');

            return 1;
        }

        $this->section('MEDIA ROW (table: media)');
        $this->dumpMedia($media);

        $this->newLine();
        $this->section('COMPUTED');
        $this->dumpComputed($media);

        $this->newLine();
        $this->section('ATTACHMENT STATE');
        $this->dumpAttachmentState($media);

        $this->newLine();
        $this->section('PARENT STATUS');
        $this->dumpStatus($media);

        $this->newLine();
        $this->section('OWNER');
        $this->dumpOwner($media);

        $this->newLine();
        $this->section('METADATA');
        $this->dumpMetadata($media);

        if ($this->option('check')) {
            $this->newLine();
            $this->section('LIVE URL CHECK');
            $this->urlCheck($media);
        }

        return 0;
    }

    /**
     * Accept a numeric id, or a URL/path whose last numeric segment is used.
     */
    protected function resolveId(string $input): ?string
    {
        $input = trim($input);

        if (ctype_digit($input)) {
            return $input;
        }

        // If a media row exists for this exact path/url, prefer that.
        $byPath = Media::withTrashed()
            ->where('media_path', $input)
            ->orWhere('remote_url', $input)
            ->orWhere('cdn_url', $input)
            ->value('id');

        if ($byPath) {
            return (string) $byPath;
        }

        if (preg_match('#(\d{4,})#', $input, $m)) {
            return $m[1];
        }

        return null;
    }

    protected function dumpMedia(Media $media): void
    {
        $rows = [];
        foreach ($media->getAttributes() as $key => $value) {
            $rows[] = [$key, $this->format($value, $key)];
        }
        $this->table(['Column', 'Value'], $rows);

        if ($media->deleted_at) {
            $this->error('  ✗ Media is soft-deleted ('.$media->deleted_at.').');
        }
    }

    protected function dumpComputed(Media $media): void
    {
        $this->table(['Computed', 'Value'], [
            ['url()', $this->safe(fn () => $media->url())],
            ['thumbnailUrl()', $this->safe(fn () => $media->thumbnailUrl())],
            ['mimeType()', $this->safe(fn () => $media->mimeType())],
            ['mediaType()', $this->safe(fn () => $media->mediaType())],
            ['activityVerb()', $this->safe(fn () => $media->activityVerb())],
            ['expected (from path)', $this->expectedUrl($media->media_path)],
        ]);
    }

    protected function dumpAttachmentState(Media $media): void
    {
        $isRemote = (bool) $media->remote_media;
        $hasLocalFile = ! $isRemote && $media->media_path && ! Str::startsWith($media->media_path, 'http');

        $this->table(['Field', 'Value'], [
            ['status_id', $media->status_id ?? 'null (orphaned / not attached)'],
            ['profile_id', $media->profile_id ?? 'null'],
            ['user_id', $media->user_id ?? 'null (remote)'],
            ['remote_media', $isRemote ? 'true' : 'false'],
            ['order', $media->order ?? 'null'],
            ['media_path is remote URL', Str::startsWith((string) $media->media_path, 'http') ? 'yes' : 'no'],
        ]);

        if ($media->status_id === null) {
            $this->warn('  This media is NOT attached to a status (status_id is null).');
            $this->line('  → media:gc will delete it once it is older than 2h.');
        }

        if ($hasLocalFile) {
            $exists = $this->localExists($media->media_path);
            $line = '  local file ('.$media->media_path.'): '.($exists ? 'present ✓' : 'MISSING ✗');
            $exists ? $this->info($line) : $this->error($line);
        }
    }

    protected function dumpStatus(Media $media): void
    {
        if (! $media->status_id) {
            $this->comment('No parent status (status_id is null).');

            return;
        }

        $status = Status::withTrashed()->find($media->status_id);

        if (! $status) {
            $this->error('status_id is '.$media->status_id.' but that status ROW DOES NOT EXIST.');
            $this->line('  → The status was hard-deleted but this media still references it (dangling status_id).');

            return;
        }

        $this->table(['Field', 'Value'], [
            ['status id', $status->id],
            ['uri', $status->uri ?? 'null (local)'],
            ['type', $status->type ?? 'null'],
            ['profile_id', $status->profile_id],
            ['created_at', $this->format($status->created_at, 'created_at')],
            ['edited_at', $this->format($status->edited_at ?? null, 'edited_at')],
            ['deleted_at', $this->format($status->deleted_at, 'deleted_at')],
        ]);

        if ($status->deleted_at) {
            $this->error('  ✗ Parent status is soft-deleted; media should be cleaned up.');
        }
    }

    protected function dumpOwner(Media $media): void
    {
        if (! $media->profile_id) {
            $this->comment('No profile_id on this media.');

            return;
        }

        $acct = AccountService::get($media->profile_id, true);

        if (! $acct) {
            $this->error('No account found for profile_id '.$media->profile_id.'.');

            return;
        }

        $this->table(['Field', 'Value'], [
            ['profile_id', $media->profile_id],
            ['username', $acct['username'] ?? 'null'],
            ['acct', $acct['acct'] ?? 'null'],
            ['url', $acct['url'] ?? 'null'],
            ['local', ($acct['local'] ?? null) ? 'true' : 'false'],
        ]);
    }

    protected function dumpMetadata(Media $media): void
    {
        if (empty($media->metadata)) {
            $this->comment('No metadata stored.');

            return;
        }

        $meta = $media->getMetadata();

        if (! is_array($meta)) {
            $this->line($this->format($media->metadata, 'metadata'));

            return;
        }

        $rows = [];
        foreach ($meta as $k => $v) {
            $rows[] = [$k, is_scalar($v) ? (string) $v : json_encode($v)];
        }
        $this->table(['Metadata Key', 'Value'], $rows);
    }

    protected function urlCheck(Media $media): void
    {
        $url = $this->safe(fn () => $media->url());

        if (! $url || ! Str::startsWith($url, 'http')) {
            $this->comment('No fetchable URL to check.');

            return;
        }

        $this->line('HEAD '.$url);

        try {
            $res = Http::withOptions(['allow_redirects' => true])
                ->timeout(10)
                ->head($url);

            $status = $res->status();
            $line = '  HTTP '.$status.' ('.($res->header('content-type') ?: 'no content-type').', '.($res->header('content-length') ?: '?').' bytes)';

            if ($res->successful()) {
                $this->info($line.' ✓');
            } else {
                $this->error($line.' ✗');
                if (in_array($status, [404, 410])) {
                    $this->line('  → Origin no longer serves this media (deleted upstream).');
                } elseif ($status === 403) {
                    $this->line('  → Access denied by origin.');
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
                $disk = Storage::disk(config('filesystems.cloud'));
                if ($disk->exists($mediaPath)) {
                    return true;
                }
            }

            return Storage::disk('local')->exists('public/'.$mediaPath)
                || Storage::disk('local')->exists($mediaPath);
        } catch (\Throwable $e) {
            return false;
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

    protected function section(string $title): void
    {
        $this->line(str_repeat('=', 64));
        $this->info($title);
        $this->line(str_repeat('=', 64));
    }

    protected function safe(callable $fn): string
    {
        try {
            return (string) ($fn() ?? 'null');
        } catch (\Throwable $e) {
            return 'error: '.$e->getMessage();
        }
    }

    protected function format($value, ?string $key = null): string
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
        $value = (string) $value;

        // Trim long blobs (metadata, srcset) for readability.
        if (in_array($key, ['metadata', 'srcset'], true) && strlen($value) > 120) {
            return mb_strimwidth($value, 0, 120, '…');
        }

        return $value;
    }
}
