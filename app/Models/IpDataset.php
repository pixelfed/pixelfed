<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpDataset extends Model
{
    public const string TYPE_COUNTRY = 'country';

    public const string TYPE_ASN = 'asn';

    public const array TYPES = [self::TYPE_COUNTRY, self::TYPE_ASN];

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_IMPORTING = 'importing';

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_REPLACED = 'replaced';

    public const string STATUS_FAILED = 'failed';

    public const string STATUS_CANCELLED = 'cancelled';

    public const array RUNNING = [self::STATUS_PENDING, self::STATUS_IMPORTING];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'source_size' => 'integer',
            'bytes_total' => 'integer',
            'byte_offset' => 'integer',
            'rows_imported' => 'integer',
            'rows_skipped' => 'integer',
            'networks_v4' => 'integer',
            'networks_v6' => 'integer',
            'entities' => 'integer',
            'allow_smaller' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'activated_at' => 'datetime',
            'purged_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRunning(): bool
    {
        return in_array($this->status, self::RUNNING, true);
    }

    public function networksTable(): string
    {
        return $this->type === self::TYPE_ASN ? 'ip_asn_networks' : 'ip_country_networks';
    }

    public function label(): string
    {
        return match ($this->type) {
            self::TYPE_COUNTRY => 'Country',
            self::TYPE_ASN => 'ASN',
            default => 'Unknown',
        };
    }

    public function progress(): int
    {
        if ($this->status === self::STATUS_ACTIVE) {
            return 100;
        }

        if ($this->bytes_total < 1) {
            return 0;
        }

        return (int) min(99, floor($this->byte_offset * 100 / $this->bytes_total));
    }

    public function estimatedRows(): ?int
    {
        if ($this->byte_offset < 1 || $this->rows_imported < 1 || $this->bytes_total < 1) {
            return null;
        }

        return (int) round($this->rows_imported * ($this->bytes_total / $this->byte_offset));
    }

    public function duration(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        return (int) $this->started_at->diffInSeconds($this->finished_at ?? now(), true);
    }

    public function toApi(): array
    {
        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'label' => $this->label(),
            'status' => $this->status,
            'source_name' => $this->source_name,
            'source_size' => $this->source_size,
            'progress' => $this->progress(),
            'rows_imported' => $this->rows_imported,
            'rows_estimated' => $this->estimatedRows(),
            'networks_v4' => $this->networks_v4,
            'networks_v6' => $this->networks_v6,
            'entities' => $this->entities,
            'error' => $this->error,
            'duration' => $this->duration(),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'activated_at' => $this->activated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
