<?php

namespace App\Console\Commands\Status;

use App\Models\MediaContentCredential;
use App\Services\ContentCredentials\C2paManager;
use App\Services\ContentCredentials\SignerCertificate;
use Illuminate\Console\Command;

final class C2paStatus extends Command
{
    protected $signature = 'c2pa:status';

    protected $description = 'Show the Content Credentials (C2PA) configuration and driver health';

    public function handle(): int
    {
        $enabled = (bool) config('c2pa.enabled');
        $driverName = (string) config('c2pa.driver');
        $driver = C2paManager::make($driverName);
        $available = $driver->available();

        $anchors = config('c2pa.trust_anchors');
        $anchorCount = is_string($anchors) && is_readable($anchors)
            ? substr_count((string) file_get_contents($anchors), 'BEGIN CERTIFICATE')
            : 0;

        $rows = [
            ['Enabled', $enabled ? 'yes' : 'no'],
            ['Driver', $driverName],
            ['Driver available', $available ? 'yes ('.$driver->version().')' : 'no'],
            ['Can redact', $driver->canRedact() ? 'yes' : 'no'],
            ['Trust anchors', $anchorCount ? "{$anchorCount} certificates" : 'none (manifests can be Valid, never Trusted)'],
            ['AI labels', config('c2pa.ai_label') ? 'on' : 'off'],
            ['Remote declarations', config('c2pa.remote') ? 'stored' : 'ignored'],
            ['Signing', config('c2pa.sign.enabled') ? 'on, mode '.config('c2pa.sign.mode') : 'off'],
        ];

        if (config('c2pa.sign.enabled')) {
            $certPath = (string) config('c2pa.sign.cert');
            $cert = is_readable($certPath) ? SignerCertificate::describe((string) file_get_contents($certPath)) : null;
            $rows[] = ['Signing certificate', $cert ? "{$cert['subject']} (issuer {$cert['issuer']})" : 'missing'];
            if ($cert) {
                $rows[] = ['Certificate expires', $cert['expires_at'].($cert['expired'] ? ' EXPIRED' : '')];
            }
            $rows[] = ['Signing key', is_readable((string) config('c2pa.sign.key')) ? 'readable' : 'missing'];
            $rows[] = ['Timestamp authority', config('c2pa.sign.tsa_url') ?: 'none'];
            $rows[] = ['Redacted assertions', implode(', ', (array) config('c2pa.sign.redact')) ?: 'none'];
        }

        $rows[] = ['Stored credentials', MediaContentCredential::count()];
        $rows[] = ['AI labelled media', MediaContentCredential::whereNotNull('ai_label')->count()];
        $rows[] = ['Instance signed media', MediaContentCredential::whereNotNull('instance_signed_at')->count()];

        $this->table(['', ''], $rows);

        if ($enabled && ! $available) {
            $this->error("The {$driverName} driver is not available, uploads are processed without Content Credentials.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
