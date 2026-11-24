<?php

namespace App\Console\Commands\Admin;

use App\Models\Instance;
use App\Services\DeliveryHostService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class DeliveryHostsCommand extends Command
{
    protected $signature = 'app:delivery-hosts
        {action=list : list, show, reset or reset-failed}
        {domain? : show, reset: the remote host}
        {--state=all : list: all, failing, open or failed}
        {--limit=50 : list: maximum number of hosts to show}
        {--force : reset-failed: skip the confirmation}';

    protected $description = 'Inspect and reset the ActivityPub delivery circuit breaker for remote hosts';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'list' => $this->listHosts(),
            'show' => $this->show(),
            'reset' => $this->reset(),
            'reset-failed' => $this->resetFailed(),
            default => $this->unknown(),
        };
    }

    protected function unknown(): int
    {
        $this->error('Unknown action. Use list, show, reset or reset-failed.');

        return self::FAILURE;
    }

    protected function listHosts(): int
    {
        $state = (string) $this->option('state');

        if (! in_array($state, ['all', 'failing', 'open', 'failed'], true)) {
            $this->error('Unknown state. Use all, failing, open or failed.');

            return self::FAILURE;
        }

        $failed = $this->tracked()->whereNotNull('delivery_failed_at')->count();
        $open = $this->open($this->tracked())->count();
        $failing = $this->failing($this->tracked())->count();

        $this->line(sprintf(
            'Failed: %s, open: %s, failing under threshold: %s',
            number_format($failed),
            number_format($open),
            number_format($failing)
        ));

        $query = $this->tracked();

        if ($state === 'failed') {
            $query->whereNotNull('delivery_failed_at');
        } elseif ($state === 'open') {
            $this->open($query);
        } elseif ($state === 'failing') {
            $this->failing($query);
        }

        $rows = $query
            ->orderByDesc('delivery_failures')
            ->orderBy('domain')
            ->limit(max(1, (int) $this->option('limit')))
            ->get()
            ->map(function (Instance $instance): array {
                $delivery = DeliveryHostService::describe($instance);

                return [
                    $instance->domain,
                    $delivery['state'],
                    $delivery['failures'],
                    $delivery['down_since']?->toDateTimeString() ?? '-',
                    $delivery['next_attempt_at']?->toDateTimeString() ?? '-',
                    $delivery['failed_at']?->toDateTimeString() ?? '-',
                ];
            });

        if ($rows->isEmpty()) {
            $this->info('No hosts with delivery failures.');

            return self::SUCCESS;
        }

        $this->table(['Host', 'State', 'Failures', 'Down since', 'Next attempt', 'Failed at'], $rows);

        return self::SUCCESS;
    }

    protected function show(): int
    {
        $domain = $this->domainArgument();

        if (! $domain) {
            $this->error('Usage: app:delivery-hosts show <domain>');

            return self::FAILURE;
        }

        $instance = Instance::whereDomain($domain)->first();

        if (! $instance) {
            $this->info($domain.' has no instances row, so it has never failed a delivery.');

            return self::SUCCESS;
        }

        $delivery = DeliveryHostService::describe($instance);

        $this->table(['Field', 'Value'], [
            ['Host', $instance->domain],
            ['State', $delivery['state']],
            ['Skipped right now', DeliveryHostService::isUnavailable($instance->domain) ? 'yes' : 'no'],
            ['Failures', $delivery['failures']],
            ['Down since', $delivery['down_since']?->toDateTimeString() ?? '-'],
            ['Next attempt', $delivery['next_attempt_at']?->toDateTimeString() ?? '-'],
            ['Failed at', $delivery['failed_at']?->toDateTimeString() ?? '-'],
        ]);

        return self::SUCCESS;
    }

    protected function reset(): int
    {
        $domain = $this->domainArgument();

        if (! $domain) {
            $this->error('Usage: app:delivery-hosts reset <domain>');

            return self::FAILURE;
        }

        DeliveryHostService::reset($domain);

        $this->info('Cleared delivery state for '.$domain.'.');

        return self::SUCCESS;
    }

    protected function resetFailed(): int
    {
        $count = Instance::query()->whereNotNull('delivery_failed_at')->count();

        if ($count === 0) {
            $this->info('No failed hosts.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Clear delivery state for '.number_format($count).' failed hosts?')) {
            return self::SUCCESS;
        }

        $cleared = DeliveryHostService::resetFailed();

        $this->info('Cleared delivery state for '.number_format($cleared).' failed hosts.');

        return self::SUCCESS;
    }

    protected function domainArgument(): ?string
    {
        $domain = $this->argument('domain');

        if (! is_string($domain) || trim($domain) === '') {
            return null;
        }

        $domain = trim($domain);

        if (str_contains($domain, '://')) {
            $domain = DeliveryHostService::domain($domain) ?? $domain;
        }

        return strtolower($domain);
    }

    protected function tracked(): Builder
    {
        return Instance::query()->where(function (Builder $query) {
            $query->where('delivery_failures', '>', 0)
                ->orWhereNotNull('delivery_failed_at');
        });
    }

    protected function open(Builder $query): Builder
    {
        return $query->whereNull('delivery_failed_at')
            ->where('delivery_timeout', true)
            ->whereNotNull('delivery_next_after');
    }

    protected function failing(Builder $query): Builder
    {
        return $query->whereNull('delivery_failed_at')
            ->where(function (Builder $query) {
                $query->where('delivery_timeout', false)
                    ->orWhereNull('delivery_next_after');
            });
    }
}
