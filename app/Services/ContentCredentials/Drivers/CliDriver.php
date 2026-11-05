<?php

namespace App\Services\ContentCredentials\Drivers;

use App\Services\ContentCredentials\C2paException;
use App\Services\ContentCredentials\Contracts\C2paDriver;
use App\Services\ContentCredentials\SignRequest;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class CliDriver implements C2paDriver
{
    protected const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/heic' => 'heic',
    ];

    protected const NO_MANIFEST = [
        'No claim found',
        'ManifestNotFound',
        'JumbfNotFound',
        'no JUMBF data found',
        'No manifest',
        'must fetch remote manifests',
    ];

    public function __construct(
        protected string $binary,
        protected int $timeout = 30,
        protected ?string $trustAnchors = null,
    ) {}

    public function name(): string
    {
        return 'cli';
    }

    public function available(): bool
    {
        return $this->version() !== null;
    }

    public function version(): ?string
    {
        try {
            $result = Process::timeout(10)->run([$this->binary, '--version']);
        } catch (\Throwable) {
            return null;
        }

        if (! $result->successful()) {
            return null;
        }

        return trim(Str::after(trim($result->output()), 'c2patool')) ?: null;
    }

    public function canRedact(): bool
    {
        return true;
    }

    public function read(string $bytes, string $mime): ?array
    {
        $dir = $this->workspace();

        try {
            $source = $dir.'/source.'.$this->extension($mime);
            file_put_contents($source, $bytes);

            $settings = $this->writeSettings($dir);
            $result = Process::timeout($this->timeout)->run([$this->binary, '--settings', $settings, $source]);

            if (! $result->successful()) {
                if (Str::contains($result->errorOutput().$result->output(), self::NO_MANIFEST, true)) {
                    return null;
                }

                throw new C2paException('c2patool read failed: '.Str::limit(trim($result->errorOutput()), 300));
            }

            $store = json_decode($result->output(), true);
            if (! is_array($store)) {
                throw new C2paException('c2patool returned invalid JSON');
            }

            return $store;
        } finally {
            $this->cleanup($dir);
        }
    }

    public function sign(SignRequest $request): string
    {
        $dir = $this->workspace();

        try {
            $source = $dir.'/derivative.'.$this->extension($request->mime);
            $parent = $dir.'/'.$this->safeName($request->parentTitle, $this->extension($request->parentMime));
            $output = $dir.'/signed.'.$this->extension($request->mime);
            $manifest = $dir.'/manifest.json';

            file_put_contents($source, $request->bytes);
            file_put_contents($parent, $request->parentBytes);

            $definition = [
                'title' => $request->title,
                'claim_generator_info' => [[
                    'name' => $request->generatorName,
                    'version' => $request->generatorVersion,
                ]],
                'assertions' => [[
                    'label' => 'c2pa.actions',
                    'data' => ['actions' => $request->actions],
                ]],
            ];

            if ($request->redactions !== []) {
                $definition['redactions'] = array_values($request->redactions);
            }

            file_put_contents($manifest, json_encode($definition, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $settings = $this->writeSettings($dir, $request);

            $result = Process::timeout($this->timeout)->run([
                $this->binary,
                '--settings', $settings,
                $source,
                '--manifest', $manifest,
                '--parent', $parent,
                '--output', $output,
                '--force',
            ]);

            if (! $result->successful() || ! is_file($output)) {
                throw new C2paException('c2patool sign failed: '.Str::limit(trim($result->errorOutput()), 300));
            }

            $signed = file_get_contents($output);
            if ($signed === false || $signed === '') {
                throw new C2paException('c2patool produced an empty file');
            }

            return $signed;
        } finally {
            $this->cleanup($dir);
        }
    }

    protected function writeSettings(string $dir, ?SignRequest $signer = null): string
    {
        $lines = [
            'version = 1',
            '',
            '[builder.thumbnail]',
            'enabled = false',
            '',
            '[verify]',
            'remote_manifest_fetch = false',
            'ocsp_fetch = false',
        ];

        if ($this->trustAnchors) {
            $lines[] = '';
            $lines[] = '[[trust.anchors]]';
            $lines[] = 'trust_kind = "manifest"';
            $lines[] = 'trust_anchors = '.$this->literal($this->trustAnchors);
        }

        if ($signer !== null) {
            $lines[] = '';
            $lines[] = '[signer.local]';
            $lines[] = 'alg = '.json_encode($signer->alg);
            $lines[] = 'sign_cert = '.$this->literal($signer->certPem);
            $lines[] = 'private_key = '.$this->literal($signer->keyPem);
            if ($signer->tsaUrl) {
                $lines[] = 'tsa_url = '.json_encode($signer->tsaUrl, JSON_UNESCAPED_SLASHES);
            }
        }

        $path = $dir.'/settings.toml';
        file_put_contents($path, implode("\n", $lines)."\n");
        chmod($path, 0600);

        return $path;
    }

    protected function literal(string $value): string
    {
        if (str_contains($value, "'''")) {
            throw new C2paException('Unsupported PEM content');
        }

        return "'''\n".trim($value)."\n'''";
    }

    protected function extension(string $mime): string
    {
        $mime = $mime === 'image/jpg' ? 'image/jpeg' : $mime;

        return self::EXTENSIONS[$mime] ?? throw new C2paException("Unsupported media type {$mime}");
    }

    protected function safeName(string $title, string $extension): string
    {
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '', pathinfo($title, PATHINFO_FILENAME)) ?: 'original';

        return Str::limit($name, 64, '').'.'.$extension;
    }

    protected function workspace(): string
    {
        $dir = sys_get_temp_dir().'/pf_c2pa_'.Str::random(24);
        if (! @mkdir($dir, 0700, true)) {
            throw new C2paException('Could not create a temporary directory');
        }

        return $dir;
    }

    protected function cleanup(string $dir): void
    {
        foreach (glob($dir.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}
