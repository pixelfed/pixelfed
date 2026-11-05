<?php

namespace App\Console\Commands\Admin;

use App\Models\RegistrationBlock;
use App\Models\User;
use App\Services\RegistrationBlockService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class RegistrationBlocksCommand extends Command
{
    protected $signature = 'admin:registration-blocks
        {action : list, add, remove, test or scan}
        {args?* : add: <type> <pattern>, remove: <id>}
        {--match=exact : exact, wildcard or regex (add)}
        {--note= : Admin note (add)}
        {--expires= : Duration like 12h, 7d or 4w (add)}
        {--type= : Filter by ip, email, email_domain or username (list, scan)}
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
            default => $this->invalidAction(),
        };
    }

    protected function invalidAction(): int
    {
        $this->error('Unknown action. Use list, add, remove, test or scan.');

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
}
