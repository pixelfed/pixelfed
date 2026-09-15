<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;

class ExportLanguages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'i18n:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build and export js localization files.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (config('app.env') !== 'local') {
            $this->error('This command is meant for development purposes and should only be run in a local environment');

            return Command::FAILURE;
        }

        $path = lang_path();
        $langs = [];

        foreach (new \DirectoryIterator($path) as $io) {
            $name = $io->getFilename();
            $skip = ['vendor'];
            if ($io->isDot() || in_array($name, $skip)) {
                continue;
            }

            if ($io->isDir()) {
                array_push($langs, $name);
            }
        }

        $exportDir = resource_path('assets/js/i18n/');
        $exportDirAlt = public_path('_lang/');

        // Remove orphaned exports whose locale no longer maps to a lang/
        // folder (e.g. left over after a language folder is renamed).
        $this->purgeOrphanedJsonFiles($exportDir, $langs);
        $this->purgeOrphanedJsonFiles($exportDirAlt, $langs);

        foreach ($langs as $lang) {
            $strings = \Lang::get('web', [], $lang);
            $strings = $this->stripEmptyStrings($strings);
            $json = json_encode($strings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $path = "{$exportDir}{$lang}.json";
            file_put_contents($path, $json);
            $pathAlt = "{$exportDirAlt}{$lang}.json";
            file_put_contents($pathAlt, $json);
        }

        return Command::SUCCESS;
    }

    /**
     * Delete .json exports in the given directory that don't correspond to
     * a current language folder, leaving valid locale files untouched.
     *
     * @param  array<int, string>  $langs  Valid locale names (lang/ folders).
     */
    protected function purgeOrphanedJsonFiles(string $dir, array $langs): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $valid = array_flip($langs);

        foreach (glob(rtrim($dir, '/').'/*.json') as $file) {
            $locale = basename($file, '.json');
            if (! isset($valid[$locale])) {
                @unlink($file);
            }
        }
    }

    /**
     * Recursively remove empty string values so untranslated Crowdin
     * placeholders don't override the UI's fallback (English) strings.
     */
    protected function stripEmptyStrings(array $strings): array
    {
        $result = [];

        foreach ($strings as $key => $value) {
            if (is_array($value)) {
                $filtered = $this->stripEmptyStrings($value);
                if (! empty($filtered)) {
                    $result[$key] = $filtered;
                }
            } elseif (is_string($value)) {
                if (trim($value) !== '') {
                    $result[$key] = $value;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
