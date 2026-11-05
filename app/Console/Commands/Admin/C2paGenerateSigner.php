<?php

namespace App\Console\Commands\Admin;

use App\Services\ContentCredentials\SignerCertificate;
use Illuminate\Console\Command;

final class C2paGenerateSigner extends Command
{
    protected $signature = 'c2pa:generate-signer
        {--org= : Organization name in the certificates, defaults to the instance domain}
        {--days=1825 : Validity of the signing certificate}
        {--force : Overwrite existing files}';

    protected $description = 'Create a Content Credentials (C2PA) signing certificate for this instance';

    public function handle(): int
    {
        $domain = (string) (config('pixelfed.domain.app') ?: parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($domain === '') {
            $this->error('Set APP_DOMAIN first.');

            return self::FAILURE;
        }

        $org = (string) ($this->option('org') ?: $domain);
        $days = max(30, (int) $this->option('days'));

        $cert = (string) config('c2pa.sign.cert');
        $key = (string) config('c2pa.sign.key');
        $dir = dirname($cert);
        $ca = $dir.'/ca.pem';
        $caKey = $dir.'/ca.key';

        foreach ([$cert, $key, $ca, $caKey] as $path) {
            if (is_file($path) && ! $this->option('force')) {
                $this->error("{$path} already exists, use --force to replace it.");

                return self::FAILURE;
            }
        }

        $generated = SignerCertificate::generate($domain, $org, $days);

        foreach ([dirname($cert), dirname($key)] as $d) {
            if (! is_dir($d) && ! mkdir($d, 0700, true) && ! is_dir($d)) {
                $this->error("Could not create {$d}");

                return self::FAILURE;
            }
        }

        $this->write($cert, $generated['chain'], 0644);
        $this->write($key, $generated['key'], 0600);
        $this->write($ca, $generated['ca'], 0644);
        $this->write($caKey, $generated['ca_key'], 0600);

        $this->info("Signing certificate for {$domain} written, valid until {$generated['expires_at']}.");
        $this->line('');
        $this->line("  chain   {$cert}");
        $this->line("  key     {$key}");
        $this->line("  ca      {$ca}");
        $this->line("  ca key  {$caKey}  (keep this offline if you can)");
        $this->line('');
        $this->line('Add these to .env:');
        $this->line('  PF_C2PA_SIGN=true');
        $this->line('  PF_C2PA_SIGN_CERT='.$cert);
        $this->line('  PF_C2PA_SIGN_KEY='.$key);
        $this->line('');
        $this->line('This CA is not on the C2PA trust list, so other validators will show');
        $this->line('the signer as unknown. Add ca.pem to PF_C2PA_TRUST_ANCHORS to trust it here.');

        return self::SUCCESS;
    }

    protected function write(string $path, string $contents, int $mode): void
    {
        file_put_contents($path, $contents);
        chmod($path, $mode);
    }
}
