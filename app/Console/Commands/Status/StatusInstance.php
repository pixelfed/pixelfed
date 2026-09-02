<?php

namespace App\Console\Commands\Status;

use App\Models\Instance;
use App\Models\Profile;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('status:instance {id : Instance id, domain (example.com), or a URL/webfinger containing a domain}')]
#[Description('Show all metadata for a federated instance: DB columns, moderation state, sync timestamps, and related counts')]
class StatusInstance extends Command
{
    public function handle(): int
    {
        $instance = $this->resolve($this->argument('id'));

        if (! $instance) {
            $this->error('No instance found for "'.$this->argument('id').'".');

            return self::FAILURE;
        }

        $this->section('INSTANCE ROW (table: instances)');
        $this->dumpRow($instance);

        $this->newLine();
        $this->section('MODERATION STATE');
        $this->table(['Field', 'Value'], [
            ['banned', $this->b($instance->banned)],
            ['unlisted', $this->b($instance->unlisted)],
            ['auto_cw', $this->b($instance->auto_cw)],
            ['limit_reason', $instance->limit_reason ?? 'null'],
            ['active_deliver', $this->b($instance->active_deliver)],
            ['valid_nodeinfo', $this->b($instance->valid_nodeinfo)],
        ]);

        $this->newLine();
        $this->section('RELATED COUNTS (local records for this domain)');
        $this->table(['Relation', 'Count'], [
            ['profiles', Profile::whereDomain($instance->domain)->count()],
        ]);

        return self::SUCCESS;
    }

    protected function resolve(string $input): ?Instance
    {
        $input = trim($input);

        if (ctype_digit($input)) {
            return Instance::find($input);
        }

        $domain = $this->extractDomain($input);

        if (! $domain) {
            return null;
        }

        return Instance::whereDomain($domain)->first();
    }

    /**
     * Extract a bare domain from a raw domain, URL, or @user@domain webfinger.
     */
    protected function extractDomain(string $input): ?string
    {
        $input = ltrim($input, '@');

        // user@domain or @user@domain
        if (str_contains($input, '@')) {
            $input = last(explode('@', $input));
        }

        // URL form
        if (Str::startsWith($input, ['http://', 'https://'])) {
            $host = parse_url($input, PHP_URL_HOST);

            return $host ?: null;
        }

        // Strip any path if a bare host/path slipped through.
        $input = explode('/', $input)[0];

        return $input !== '' ? strtolower($input) : null;
    }

    protected function dumpRow(Instance $instance): void
    {
        $rows = [];
        foreach ($instance->getAttributes() as $key => $value) {
            $rows[] = [$key, $this->format($value, $key)];
        }
        $this->table(['Column', 'Value'], $rows);
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

        if ($key === 'notes' && strlen($value) > 120) {
            return mb_strimwidth($value, 0, 120, '…');
        }

        return $value;
    }
}
