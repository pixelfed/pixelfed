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
     */
    public function handle(): int
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
        // folder (e.g. left over after a language folder is deleted/renamed).
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

        $this->writeLocalesManifest($langs);

        return Command::SUCCESS;
    }

    /**
     * Write a static locales manifest (lang/locales.json) that is the single
     * source of truth for the available UI languages. Generated here so it is
     * always regenerated alongside the exported strings, avoiding a runtime
     * cache that can go stale (see Localization::languages()).
     *
     * Sorted by English display name so consumers render an alphabetical list.
     *
     * @param  array<int, string>  $langs  Valid locale codes (lang/ folders).
     */
    /**
     * Display-name overrides for locale codes that ICU cannot resolve
     * correctly. Crowdin uses some non-standard codes (e.g. custom
     * languages) that would otherwise render as the wrong language or as
     * the raw code via locale_get_display_name().
     *
     * @var array<string, array{name: string, nativeName: string}>
     */
    protected const LOCALE_OVERRIDES = [
        // Crowdin custom "Pirate English". Uses the BCP-47 private-use form
        // en-x-pirate (the old en-PT code collided with English (Portugal)).
        'en-x-pirate' => ['name' => 'Pirate (English)', 'nativeName' => 'Pirate (English)'],

        // Klingon. 'tlh' is valid BCP-47 (ICU renders "Klingon"), but ICU has
        // no native-name form, so pin both for a consistent label. Mapped from
        // Crowdin's tlh-AA (AA is a fake region) via crowdin.yml.
        'tlh' => ['name' => 'Klingon', 'nativeName' => 'tlhIngan Hol'],
    ];

    protected function writeLocalesManifest(array $langs): void
    {
        $locales = array_map(function ($code) {
            if (isset(self::LOCALE_OVERRIDES[$code])) {
                return array_merge(['code' => $code], self::LOCALE_OVERRIDES[$code]);
            }

            return [
                'code' => $code,
                'name' => locale_get_display_name($code, 'en'),
                'nativeName' => locale_get_display_name($code, $code),
            ];
        }, $langs);

        usort($locales, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $json = json_encode($locales, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Server-side source of truth (used by Localization::languages()).
        file_put_contents(lang_path('locales.json'), $json);

        // Public copy so the SPA can fetch the same ordered list.
        $publicManifest = public_path('_lang/locales.json');
        file_put_contents($publicManifest, $json);
        @chmod($publicManifest, 0644);
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
        // Not a locale export, but written by writeLocalesManifest().
        $keep = ['locales' => true];

        foreach (glob(rtrim($dir, '/').'/*.json') as $file) {
            $locale = basename($file, '.json');
            if (! isset($valid[$locale]) && ! isset($keep[$locale])) {
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
                if ($filtered !== []) {
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
