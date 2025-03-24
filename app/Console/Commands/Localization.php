<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class Localization extends Command
{
    protected $signature = 'localization:generate';

    protected $description = 'Generate JSON files for all available localizations';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $languages = $this->discoverLangs();

        foreach ($languages as $lang) {
            $this->info("Processing {$lang} translations...");
            $this->buildTranslations($lang);
        }

        $this->info('All language files have been processed successfully!');
    }

    protected function buildTranslations(string $lang)
    {
        $path = base_path("resources/lang/{$lang}");
        $keys = [];
        $kcount = 0;

        if (! File::isDirectory($path)) {
            $this->error("Directory not found: {$path}");

            return;
        }

        foreach (new \DirectoryIterator($path) as $io) {
            if ($io->isDot() || ! $io->isFile()) {
                continue;
            }

            $key = $io->getBasename('.php');
            try {
                $translations = __($key, [], $lang);
                $keys[$key] = [];

                foreach ($translations as $k => $str) {
                    $keys[$key][$k] = $str;
                    $kcount++;
                }

                ksort($keys[$key]);
            } catch (\Exception $e) {
                $this->warn("Failed to process {$lang}/{$key}.php: {$e->getMessage()}");
            }
        }

        $result = $this->prepareOutput($keys, $kcount);
        $this->saveTranslations($result, $lang);
    }

    protected function prepareOutput(array $keys, int $keyCount): array
    {
        $output = $keys;
        $hash = hash('sha256', json_encode($output));

        $output['_meta'] = [
            'key_count' => $keyCount,
            'generated' => now()->toAtomString(),
            'hash_sha256' => $hash,
        ];

        ksort($output);

        return $output;
    }

    protected function saveTranslations(array $translations, string $lang)
    {
        $directory = public_path('_lang');
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = "{$directory}/{$lang}.json";
        $contents = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        File::put($filename, $contents);
        $this->info("Generated {$lang}.json");
    }

    protected function discoverLangs(): array
    {
        $path = base_path('resources/lang');
        $languages = [];

        foreach (new \DirectoryIterator($path) as $io) {
            $name = $io->getFilename();

            if (! $io->isDot() && $io->isDir() && $name !== 'vendor') {
                $languages[] = $name;
            }
        }

        return $languages;
    }
}
