<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateConfigSchema extends Command
{
    protected $signature = 'config:schema
        {--output= : Write schema to file instead of stdout}
        {--pretty : Pretty-print JSON output}
        {--filter=all : Which options to include: all, cached, env}';

    protected $description = 'Generate a JSON Schema document describing all configurable options';

    /**
     * Config files (relative to config_path()) whose env() calls we scan.
     * Add a filename here when a new config file with relevant env() calls is introduced.
     */
    private const CONFIG_FILES = [
        'app',
        'pixelfed',
        'federation',
        'instance',
        'captcha',
        'media',
        'filesystems',
        'autospam',
    ];

    public function handle(): int
    {
        // 1. Extract {dot-key => [env, default, cast]} from config files via subprocess
        $envMappings = $this->extractEnvMappings();

        // 2. Read ConfigCacheService source for the allowed + protected key lists
        $cachedKeys   = $this->loadCachedKeys();
        $sensitiveKeys = $this->loadSensitiveKeys();

        // 3. Human-authored overlay: descriptions, groups, constraints
        $meta = $this->loadMetaOverlay();

        // 4. Union of all keys we care about
        $allKeys = array_values(array_unique(array_merge(
            array_keys($envMappings),
            $cachedKeys,
        )));
        sort($allKeys);

        // 5. Apply filter
        $filter = $this->option('filter');
        if ($filter === 'cached') {
            $allKeys = array_values(array_filter($allKeys, fn ($k) => in_array($k, $cachedKeys)));
        } elseif ($filter === 'env') {
            $allKeys = array_values(array_filter($allKeys, fn ($k) => isset($envMappings[$k])));
        }

        // 6. Build schema properties
        $properties = [];
        foreach ($allKeys as $key) {
            $mapping = $envMappings[$key] ?? null;
            $overlay = $meta[$key] ?? [];

            $prop = [
                'description' => $overlay['description'] ?? '',
                'type'        => $overlay['type'] ?? $this->inferType($mapping),
            ];

            // default — prefer extracted value; overlay can override via 'type' only
            $default = $mapping['default'] ?? null;
            if ($default !== null) {
                $prop['default'] = $default;
            }

            // Extra JSON Schema constraint keywords from overlay
            foreach (['enum', 'minimum', 'maximum', 'format'] as $kw) {
                if (isset($overlay[$kw])) {
                    $prop[$kw] = $overlay[$kw];
                }
            }

            // Extension annotations
            if ($mapping && isset($mapping['env'])) {
                $prop['x-env'] = $mapping['env'];
            }
            $prop['x-config-cache'] = in_array($key, $cachedKeys);
            $prop['x-group']        = $overlay['group'] ?? $this->inferGroup($key);
            if (in_array($key, $sensitiveKeys)) {
                $prop['x-sensitive'] = true;
            }

            $properties[$key] = $prop;
        }

        $schema = [
            '$schema'     => 'https://json-schema.org/draft/2020-12/schema',
            'title'       => 'Pixelfed Instance Configuration',
            'description' =>
                'JSON Schema for all configurable Pixelfed options. '
                .'x-env: environment variable name. '
                .'x-config-cache: true when editable at runtime via the admin panel. '
                .'x-sensitive: true when the value is a secret and must not be logged or displayed.',
            'type'        => 'object',
            'properties'  => $properties,
        ];

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($this->option('pretty')) {
            $flags |= JSON_PRETTY_PRINT;
        }
        $json = json_encode($schema, $flags);

        $output = $this->option('output');
        if ($output) {
            file_put_contents($output, $json.PHP_EOL);
            $this->info("Schema written to {$output}");
        } else {
            $this->line($json);
        }

        return self::SUCCESS;
    }

    // ── Source data loaders ───────────────────────────────────────────────────

    /**
     * Spawn a clean PHP subprocess (no Laravel bootstrap) that stubs env() and
     * evaluates each config file, then returns a JSON map of
     * { "dot.key" => { "env": "VAR", "default": value, "cast": "int"|null } }.
     *
     * Running out-of-process is necessary because env() is already defined by
     * Laravel in the parent process and cannot be redefined.
     */
    private function extractEnvMappings(): array
    {
        $script  = $this->buildExtractorScript();
        $tmpFile = tempnam(sys_get_temp_dir(), 'pf_schema_extract_');
        file_put_contents($tmpFile, $script);

        $php    = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
        $cmd    = escapeshellarg($php)
                .' '.escapeshellarg($tmpFile)
                .' '.escapeshellarg(config_path())
                .' '.escapeshellarg(json_encode(self::CONFIG_FILES));

        exec($cmd, $lines, $rc);
        unlink($tmpFile);

        if ($rc !== 0) {
            $this->warn('Config extractor subprocess failed; env mappings may be incomplete.');

            return [];
        }

        return json_decode(implode('', $lines), true) ?? [];
    }

    /**
     * Parse ConfigCacheService::get() source to extract the $allowed array.
     * This is intentionally a simple regex rather than a full PHP parse — the
     * format of the array is stable and any false-positives would just add extra
     * entries that get filtered out downstream.
     */
    private function loadCachedKeys(): array
    {
        $source = file_get_contents(app_path('Services/ConfigCacheService.php'));

        if (! preg_match('/\$allowed\s*=\s*\[(.*?)\];/s', $source, $m)) {
            $this->warn('Could not locate $allowed array in ConfigCacheService.');

            return [];
        }

        preg_match_all("/'([a-z][a-z0-9._\-]+)'/", $m[1], $keys);

        return $keys[1];
    }

    /**
     * Parse ConfigCacheService::PROTECTED_KEYS to find sensitive keys.
     */
    private function loadSensitiveKeys(): array
    {
        $source = file_get_contents(app_path('Services/ConfigCacheService.php'));

        if (! preg_match('/PROTECTED_KEYS\s*=\s*\[(.*?)\];/s', $source, $m)) {
            return [];
        }

        preg_match_all("/'([^']+)'/", $m[1], $keys);

        return $keys[1];
    }

    /**
     * Load config/schema-meta.php — human-authored overlay that supplies
     * descriptions, x-group, and JSON Schema constraint keywords (minimum,
     * maximum, enum, format) that cannot be derived automatically.
     *
     * The file is a plain PHP array (same format as the other config/ files),
     * which means it supports // comments and requires no extra dependencies.
     */
    private function loadMetaOverlay(): array
    {
        $path = config_path('schema-meta.php');
        if (! file_exists($path)) {
            return [];
        }

        return require $path;
    }

    // ── Inference helpers ─────────────────────────────────────────────────────

    private function inferType(?array $mapping): string|array
    {
        if (! $mapping) {
            return 'string';
        }

        // Explicit PHP cast beats default-value inference
        $cast = $mapping['cast'] ?? null;
        if ($cast === 'int')    return 'integer';
        if ($cast === 'bool')   return 'boolean';
        if ($cast === 'float')  return 'number';
        if ($cast === 'string') return 'string';

        $default = $mapping['default'];
        if (is_bool($default))  return 'boolean';
        if (is_int($default))   return 'integer';
        if (is_float($default)) return 'number';
        if ($default === null)  return ['string', 'null'];

        return 'string';
    }

    /**
     * Infer an x-group from the dot-notation key when schema-meta.json has no
     * explicit group for the key.  Rules are checked longest-prefix first so
     * that more-specific prefixes win.
     */
    private function inferGroup(string $key): string
    {
        static $rules = null;

        if ($rules === null) {
            // Sorted longest-first so specific prefixes beat short ones
            $raw = [
                'instance.curated_registration.' => 'registration',
                'instance.user_filters.'          => 'accounts',
                'instance.landing.'               => 'discovery',
                'instance.discover.'              => 'discovery',
                'instance.timeline.'              => 'discovery',
                'instance.hide_nsfw_on_public_feeds' => 'discovery',
                'instance.stories.'               => 'features',
                'instance.contact.'               => 'notifications',
                'instance.reports.'               => 'notifications',
                'instance.notifications.'         => 'notifications',
                'instance.embed.'                 => 'features',
                'instance.avatar.'                => 'features',
                'instance.restrict.'              => 'features',
                'instance.label.'                 => 'features',
                'instance.parental_controls.'     => 'features',
                'instance.custom_filters.'        => 'features',
                'instance.oauth.'                 => 'api',
                'instance.admin.'                 => 'branding',
                'instance.banner.'                => 'branding',
                'instance.page.'                  => 'branding',
                'instance.enable_cc'              => 'application',
                'instance.force_https_urls'       => 'application',
                'instance.total_count_estimate'   => 'application',
                'instance.software-update.'       => 'application',
                'instance.admin_invites.'         => 'features',
                'instance.allow_new'              => 'features',
                'instance.description'            => 'branding',
                'instance.username.'              => 'accounts',
                'pixelfed.directory.'             => 'application',
                'pixelfed.directory'              => 'application',
                'system.'                         => 'application',
                'pixelfed.open_registration'      => 'registration',
                'pixelfed.enforce_email'          => 'registration',
                'pixelfed.max_photo'              => 'media',
                'pixelfed.max_album'              => 'media',
                'pixelfed.image_quality'          => 'media',
                'pixelfed.media_type'             => 'media',
                'pixelfed.optimize_'              => 'media',
                'pixelfed.max_avatar'             => 'media',
                'pixelfed.max_altext'             => 'media',
                'pixelfed.max_collection'         => 'media',
                'pixelfed.media_fast'             => 'media',
                'pixelfed.max_caption'            => 'content',
                'pixelfed.max_bio'                => 'content',
                'pixelfed.max_name'               => 'content',
                'pixelfed.min_password'           => 'content',
                'pixelfed.max_account'            => 'accounts',
                'pixelfed.enforce_account'        => 'accounts',
                'pixelfed.max_users'              => 'accounts',
                'pixelfed.enforce_max'            => 'accounts',
                'pixelfed.account_delet'          => 'accounts',
                'pixelfed.user_invites.'          => 'accounts',
                'pixelfed.cloud_storage'          => 'storage',
                'pixelfed.import.'                => 'features',
                'pixelfed.bouncer.'               => 'features',
                'pixelfed.oauth'                  => 'api',
                'pixelfed.allow_app'              => 'api',
                'pixelfed.app_registration'       => 'api',
                'pixelfed.domain.'                => 'application',
                'federation.'                     => 'federation',
                'filesystems.'                    => 'storage',
                'captcha.'                        => 'captcha',
                'autospam.'                       => 'features',
                'media.'                          => 'media',
                'account.'                        => 'accounts',
                'uikit.'                          => 'branding',
                'about.'                          => 'branding',
                'app.name'                        => 'branding',
                'app.short_description'           => 'branding',
                'app.description'                 => 'branding',
                'app.banner_image'                => 'branding',
                'app.rules'                       => 'branding',
                'app.'                            => 'application',
                'instance.'                       => 'general',
            ];

            // Sort by key length descending so longest (most specific) prefix wins
            uksort($raw, fn ($a, $b) => strlen($b) - strlen($a));
            $rules = $raw;
        }

        foreach ($rules as $prefix => $group) {
            if ($key === $prefix || str_starts_with($key, $prefix)) {
                return $group;
            }
        }

        return 'general';
    }

    // ── Subprocess extractor script ───────────────────────────────────────────

    /**
     * Returns the source of a standalone PHP script that:
     *  - stubs all Laravel helpers (env, storage_path, config, app, …)
     *  - preprocesses type-cast patterns like (int) env(…) → env_cast('int', …)
     *  - evals each target config file
     *  - walks the resulting array to collect {dot-key → {env, default, cast}}
     *  - writes JSON to stdout
     *
     * argv[1] = config directory path
     * argv[2] = JSON-encoded array of config file prefixes (without .php)
     */
    private function buildExtractorScript(): string
    {
        // Single-quoted heredoc: no PHP interpolation, so the embedded $variables
        // belong to the subprocess script, not to this method.
        return <<<'EXTRACTOR'
<?php
error_reporting(0);

$configDir   = $argv[1] ?? __DIR__;
$targetFiles = json_decode($argv[2] ?? '[]', true);

// ── Sentinel ──────────────────────────────────────────────────────────────────
// Represents an env() call in a config file.  Truthy (so ternaries on env()
// behave as if the variable is set), and has a safe __toString() so it
// survives string-concatenation contexts without crashing.
class EnvSentinel
{
    public string $envKey;
    public mixed  $default;
    public ?string $cast;

    public function __construct(string $k, mixed $d = null, ?string $c = null)
    {
        $this->envKey  = $k;
        $this->default = $d instanceof self ? null : $d; // nested env() → null default
        $this->cast    = $c;
    }

    public function __toString(): string { return ''; }
}

// ── Stubs ─────────────────────────────────────────────────────────────────────
function env(string $key, mixed $default = null): EnvSentinel
{
    return new EnvSentinel($key, $default);
}

function env_cast(string $cast, string $key, mixed $default = null): EnvSentinel
{
    return new EnvSentinel($key, $default, $cast);
}

function storage_path(string $path = ''): string  { return '/tmp/'.$path; }
function database_path(string $path = ''): string { return '/tmp/'.$path; }
function resource_path(string $path = ''): string { return '/tmp/'.$path; }
function public_path(string $path = ''): string   { return '/tmp/'.$path; }
function base_path(string $path = ''): string     { return '/tmp/'.$path; }
function config(mixed $key = null, mixed $default = null): mixed { return $default; }

function app(mixed $abstract = null): object
{
    return new class {
        public function getLocale(): string { return 'en'; }
        public function __get(string $n): mixed { return null; }
        public function __call(string $n, array $a): mixed { return null; }
    };
}

// ── Preprocessor ──────────────────────────────────────────────────────────────
// Rewrites (int) env(  →  env_cast('int',   (and similarly for other casts)
// so type information is preserved through the eval without crashing on object casts.
function preprocessSource(string $source): string
{
    $casts = ['int', 'bool', 'float', 'string'];
    foreach ($casts as $cast) {
        $source = preg_replace(
            '/\(\s*'.$cast.'\s*\)\s*env\s*\(/',
            "env_cast('".$cast."', ",
            $source
        );
    }
    // Strip opening PHP tag so the source is eval()-able
    $source = preg_replace('/^\s*<\?php\s*/i', '', $source);
    return $source;
}

// ── Array walker ──────────────────────────────────────────────────────────────
function walkConfig(array $arr, string $prefix, array &$results): void
{
    foreach ($arr as $key => $value) {
        $dotKey = $prefix.'.'.$key;
        if ($value instanceof EnvSentinel) {
            $results[$dotKey] = [
                'env'     => $value->envKey,
                'default' => $value->default,
                'cast'    => $value->cast,
            ];
        } elseif (is_array($value)) {
            walkConfig($value, $dotKey, $results);
        }
        // Literal non-env values: ignored (not configurable via env)
    }
}

// ── Main ──────────────────────────────────────────────────────────────────────
$results = [];

foreach ($targetFiles as $prefix) {
    $file = $configDir.'/'.$prefix.'.php';
    if (! file_exists($file)) {
        continue;
    }

    $source = preprocessSource(file_get_contents($file));

    // eval() returns the value of the last expression — config files end with
    // `return [...]` so this gives us the config array directly.
    $config = eval($source);

    if (! is_array($config)) {
        continue;
    }

    walkConfig($config, $prefix, $results);
}

echo json_encode($results);
EXTRACTOR;
    }
}
