<?php

namespace App\Console\Commands\Status;

use App\Models\CustomEmoji;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('status:emoji {id : Custom emoji id, shortcode (:blobcat:), or media filename (1234.png)} {--check : Perform a live HEAD request against the emoji image_remote_url}')]
#[Description('Show all metadata for a custom emoji: DB columns, origin, and local storage state')]
class StatusEmoji extends Command
{
    public function handle(): int
    {
        $emoji = $this->resolve($this->argument('id'));

        if (! $emoji) {
            $this->error('No custom emoji found for "'.$this->argument('id').'" (tried id, shortcode, then media filename).');

            return self::FAILURE;
        }

        $this->section('CUSTOM EMOJI ROW (table: custom_emoji)');
        $this->dumpRow($emoji);

        $this->newLine();
        $this->section('ORIGIN');
        $isRemote = ! empty($emoji->image_remote_url) || ! empty($emoji->domain);
        $this->table(['Field', 'Value'], [
            ['origin', $isRemote ? 'remote' : 'local'],
            ['domain', $emoji->domain ?? 'null'],
            ['uri', $emoji->uri ?? 'null'],
            ['image_remote_url', $emoji->image_remote_url ?? 'null'],
            ['disabled', $this->b($emoji->disabled)],
        ]);

        $this->newLine();
        $this->section('LOCAL STORAGE STATE');
        if ($emoji->media_path) {
            $exists = $this->localExists($emoji->media_path);
            $this->table(['Field', 'Value'], [
                ['media_path', $emoji->media_path],
                ['stored file', $exists ? 'present ✓' : 'MISSING ✗'],
                ['url', Str::startsWith($emoji->media_path, 'http') ? $emoji->media_path : url('/storage/'.$emoji->media_path)],
            ]);
            if (! $exists) {
                $this->error('  ✗ Local file is missing.');
                if (! empty($emoji->image_remote_url)) {
                    $this->comment('  Fix with: php artisan admin:resyncemoji "'.basename($emoji->media_path).'"');
                }
            }
        } else {
            $this->comment('No media_path set for this emoji.');
        }

        if ($this->option('check')) {
            $this->newLine();
            $this->section('LIVE URL CHECK (image_remote_url)');
            $this->urlCheck($emoji);
        }

        return self::SUCCESS;
    }

    protected function resolve(string $input): ?CustomEmoji
    {
        $input = trim($input);

        if (ctype_digit($input)) {
            return CustomEmoji::find($input);
        }

        // Shortcode form, with or without surrounding colons.
        $shortcode = ':'.trim($input, ':').':';
        if ($emoji = CustomEmoji::whereShortcode($shortcode)->first()) {
            return $emoji;
        }

        // Media filename form (e.g. 1234.png -> emoji/1234.png).
        $filename = basename($input);

        return CustomEmoji::whereMediaPath('emoji/'.$filename)
            ->orWhere('media_path', $filename)
            ->first();
    }

    protected function dumpRow(CustomEmoji $emoji): void
    {
        $rows = [];
        foreach ($emoji->getAttributes() as $key => $value) {
            $rows[] = [$key, $this->format($value)];
        }
        $this->table(['Column', 'Value'], $rows);
    }

    protected function urlCheck(CustomEmoji $emoji): void
    {
        $url = $emoji->image_remote_url;

        if (! $url || ! Str::startsWith($url, 'http')) {
            $this->comment('No image_remote_url to check (local emoji).');

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
                    $this->line('  → Origin no longer serves this emoji (deleted upstream).');
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
