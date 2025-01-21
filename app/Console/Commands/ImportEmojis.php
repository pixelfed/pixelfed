<?php

namespace App\Console\Commands;

use App\Models\CustomEmoji;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ImportEmojis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:emojis
                            {path : Path to a tar.gz archive with the emojis}
                            {--prefix : Define a prefix for the emjoi shortcode}
                            {--suffix : Define a suffix for the emjoi shortcode}
                            {--overwrite : Overwrite existing emojis}
                            {--disabled : Import all emojis as disabled}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import emojis to the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = $this->argument('path');

        if (!file_exists($path) || !mime_content_type($path) == 'application/x-tar') {
            $this->error('Path does not exist or is not a tarfile');
            return Command::FAILURE;
        }

        $imported = 0;
        $skipped = 0;
        $failed = 0;

        $tar = new \PharData($path);
        $tar->decompress();

        foreach (new \RecursiveIteratorIterator($tar) as $entry) {
            $this->line("Processing {$entry->getFilename()}");
            if (!$entry->isFile() || !$this->isImage($entry) || !$this->isEmoji($entry->getPathname())) {
                $failed++;
                continue;
            }

            $filename = pathinfo($entry->getFilename(), PATHINFO_FILENAME);
            $extension = pathinfo($entry->getFilename(), PATHINFO_EXTENSION);

            // Skip macOS shadow files
            if (str_starts_with($filename, '._')) {
                continue;
            }

            $shortcode = implode('', [
                $this->option('prefix'),
                $filename,
                $this->option('suffix'),
            ]);

            $customEmoji = CustomEmoji::whereShortcode($shortcode)->first();

            if ($customEmoji && !$this->option('overwrite')) {
                $skipped++;
                continue;
            }

            $emoji = $customEmoji ?? new CustomEmoji();
            $emoji->shortcode = $shortcode;
            $emoji->domain = config('pixelfed.domain.app');
            $emoji->disabled = $this->option('disabled');
            $emoji->save();

            $fileName = $emoji->id . '.' . $extension;
            Storage::putFileAs('public/emoji', $entry->getPathname(), $fileName);
            $emoji->media_path = 'emoji/' . $fileName;
            $emoji->save();
            $imported++;
            Cache::forget('pf:custom_emoji');
        }

        $this->line("Imported: {$imported}");
        $this->line("Skipped: {$skipped}");
        $this->line("Failed: {$failed}");

        //delete file
        unlink(str_replace('.tar.gz', '.tar', $path));

        return Command::SUCCESS;
    }

    private function isImage($file)
    {
        $image = getimagesize($file->getPathname());
        return $image !== false;
    }

    private function isEmoji($filename)
    {
        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/webp'];
        $mimeType = mime_content_type($filename);

        return in_array($mimeType, $allowedMimeTypes);
    }
}
