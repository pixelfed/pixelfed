<?php

namespace App\Console\Commands\Admin;

use App\Models\RegistrationBlock;
use App\Models\User;
use App\Services\RegistrationBlockExchangeService;
use App\Services\RegistrationBlockService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class RegistrationBlocksCommand extends Command
{
    protected $signature = 'admin:registration-blocks
        {action : list, add, remove, test, scan, export or import}
        {args?* : add: <type> <pattern>, remove: <id>, export: [path], import: <path>}
        {--match=exact : exact, wildcard or regex (add)}
        {--note= : Admin note (add), or a note for every imported block (import)}
        {--expires= : Duration like 12h, 7d or 4w (add, import). Import keeps the expiry from the file when omitted, use never to clear it}
        {--type= : Filter by ip, email, email_domain or username, comma separated for export and import (list, scan, export, import)}
        {--format= : json or csv, detected from the path when omitted (export, import)}
        {--include-notes : Include admin notes in the export (export)}
        {--include-inactive : Include disabled and expired blocks (export)}
        {--dry-run : Report what would be imported without saving (import)}
        {--ip= : IP to test (test)}
        {--email= : Email to test (test)}
        {--username= : Username to test (test)}
        {--days=30 : Only scan accounts created in the last N days, 0 for all (scan)}
        {--limit=500 : Stop scanning after this many matches (scan)}';

    protected $description = 'Manage registration blocks by IP, email, email domain or username, and scan existing accounts against them';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'list' => $this->listBlocks(),
            'add' => $this->addBlock(),
            'remove' => $this->removeBlock(),
            'test' => $this->testBlock(),
            'scan' => $this->scanAccounts(),
            'export' => $this->exportBlocks(),
            'import' => $this->importBlocks(),
            default => $this->invalidAction(),
        };
    }

    protected function invalidAction(): int
    {
        $this->error('Unknown action. Use list, add, remove, test, scan, export or import.');

        return self::FAILURE;
    }

    protected function listBlocks(): int
    {
        $type = $this->option('type');

        $rows = RegistrationBlock::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('id')
            ->get()
            ->map(fn (RegistrationBlock $b) => [
                $b->id,
                $b->type,
                $b->type === 'ip' ? '' : $b->match_type,
                $b->pattern,
                $b->status(),
                $b->hits,
                $b->expires_at?->toDateTimeString() ?? 'never',
                (string) $b->note,
            ]);

        if ($rows->isEmpty()) {
            $this->info('No registration blocks.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Type', 'Match', 'Pattern', 'Status', 'Hits', 'Expires', 'Note'], $rows->all());

        return self::SUCCESS;
    }

    protected function addBlock(): int
    {
        $args = $this->argument('args');

        if (count($args) < 2) {
            $this->error('Usage: admin:registration-blocks add <ip|email|email_domain|username> <pattern> [--match=] [--note=] [--expires=]');

            return self::FAILURE;
        }

        [$type, $pattern] = $args;

        try {
            $block = RegistrationBlockService::store(
                $type,
                (string) $this->option('match'),
                $pattern,
                $this->option('note'),
                RegistrationBlockService::parseDuration($this->option('expires'))
            );
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Saved block #{$block->id}: {$block->type} {$block->match_type} {$block->pattern}");

        return self::SUCCESS;
    }

    protected function removeBlock(): int
    {
        $id = $this->argument('args')[0] ?? null;
        $block = $id ? RegistrationBlock::find($id) : null;

        if (! $block) {
            $this->error('Block not found.');

            return self::FAILURE;
        }

        $block->delete();
        $this->info("Removed block #{$id}.");

        return self::SUCCESS;
    }

    protected function testBlock(): int
    {
        $match = RegistrationBlockService::check(
            $this->option('ip'),
            $this->option('email'),
            $this->option('username'),
            false
        );

        if (! $match) {
            $this->info('Allowed. No active block matches.');

            return self::SUCCESS;
        }

        $this->warn("Blocked by #{$match['id']}: {$match['type']} {$match['match_type']} {$match['pattern']}");

        return self::SUCCESS;
    }

    /**
     * Read-only report of existing accounts that match a current block.
     * Nothing is changed, review the list before suspending anyone.
     */
    protected function scanAccounts(): int
    {
        $rules = RegistrationBlockService::rules();
        $type = $this->option('type');

        if ($type) {
            $rules = array_values(array_filter($rules, fn ($r) => $r['type'] === $type));
        }

        if (! count($rules)) {
            $this->info('No active blocks to scan with.');

            return self::SUCCESS;
        }

        $days = (int) $this->option('days');
        $limit = max(1, (int) $this->option('limit'));
        $matches = [];
        $scanned = 0;

        User::query()
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', ['deleted', 'delete']))
            ->when($days > 0, fn ($q) => $q->where('created_at', '>=', now()->subDays($days)))
            ->select(['id', 'username', 'email', 'app_register_ip', 'status', 'created_at'])
            ->chunkById(1000, function ($users) use ($rules, $limit, &$matches, &$scanned) {
                foreach ($users as $user) {
                    $scanned++;
                    $rule = RegistrationBlockService::find($rules, $user->app_register_ip, $user->email, $user->username);

                    if ($rule) {
                        $matches[] = [
                            $user->id,
                            $user->username,
                            $user->email,
                            (string) $user->app_register_ip,
                            (string) ($user->status ?? 'active'),
                            $user->created_at?->toDateString(),
                            "#{$rule['id']} {$rule['type']} {$rule['pattern']}",
                        ];
                    }

                    if (count($matches) >= $limit) {
                        return false;
                    }
                }

                return true;
            });

        $this->line("Scanned {$scanned} accounts.");

        if (! count($matches)) {
            $this->info('No existing accounts match.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Username', 'Email', 'Signup IP', 'Status', 'Created', 'Rule'], $matches);
        $this->warn(count($matches).' matching accounts. Nothing was changed.');

        return self::SUCCESS;
    }

    /**
     * @return string[]
     */
    protected function typesOption(): array
    {
        $types = array_filter(array_map('trim', explode(',', (string) $this->option('type'))));

        return $types ? array_values(array_intersect($types, RegistrationBlockService::TYPES)) : RegistrationBlockService::TYPES;
    }

    protected function formatFor(?string $path): ?string
    {
        $format = $this->option('format');

        if ($format) {
            return in_array($format, RegistrationBlockExchangeService::FORMATS, true) ? $format : null;
        }

        $ext = $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        return in_array($ext, RegistrationBlockExchangeService::FORMATS, true) ? $ext : null;
    }

    protected function exportBlocks(): int
    {
        $path = $this->argument('args')[0] ?? null;
        $format = $this->formatFor($path) ?? 'json';
        $types = $this->typesOption();

        if (! count($types)) {
            $this->error('Unknown --type, use ip, email, email_domain or username.');

            return self::FAILURE;
        }

        $payload = RegistrationBlockExchangeService::export(
            $types,
            (bool) $this->option('include-notes'),
            (bool) $this->option('include-inactive')
        );

        $body = $format === 'csv'
            ? RegistrationBlockExchangeService::toCsv($payload)
            : RegistrationBlockExchangeService::toJson($payload);

        if ($path === null) {
            $this->output->write($body);

            return self::SUCCESS;
        }

        if (@file_put_contents($path, $body) === false) {
            $this->error("Could not write to {$path}");

            return self::FAILURE;
        }

        $this->info("Exported {$payload['count']} blocks to {$path}");

        return self::SUCCESS;
    }

    protected function importBlocks(): int
    {
        $path = $this->argument('args')[0] ?? null;

        if (! $path || ! is_readable($path)) {
            $this->error('Usage: admin:registration-blocks import <path> [--type=] [--expires=] [--note=] [--dry-run]');

            return self::FAILURE;
        }

        try {
            $rows = RegistrationBlockExchangeService::parse((string) file_get_contents($path), $this->formatFor($path));
            $expires = $this->option('expires') === null
                ? false
                : RegistrationBlockService::parseDuration($this->option('expires'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $result = RegistrationBlockExchangeService::import($rows, [
            'types' => $this->typesOption(),
            'expires' => $expires,
            'note' => $this->option('note'),
            'dry_run' => (bool) $this->option('dry-run'),
        ]);

        foreach ($result['invalid'] as $line) {
            $this->line("<fg=red>invalid</> {$line}");
        }

        foreach ($result['broad'] as $line) {
            $this->line("<fg=yellow>skipped</> {$line}");
        }

        $this->table(['Added', 'Already blocked', 'Expired', 'Other types', 'Invalid', 'Too broad'], [[
            $result['added'],
            $result['existing'],
            $result['expired'],
            $result['filtered'],
            count($result['invalid']),
            count($result['broad']),
        ]]);

        if ($result['dry_run']) {
            $this->warn('Dry run, nothing was saved.');
        }

        return self::SUCCESS;
    }
}
