<?php

namespace App\Services;

use App\Models\RegistrationBlock;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class RegistrationBlockExchangeService
{
    public const string FORMAT = 'pixelfed.registration_blocks';

    public const int VERSION = 1;

    public const array FORMATS = ['json', 'csv'];

    public const array CSV_COLUMNS = ['type', 'match_type', 'pattern', 'note', 'expires_at'];

    public const int MAX_IMPORT_ROWS = 5000;

    private const array CANARY_EMAILS = [
        'alex.smith@gmail.com',
        'maria1990@outlook.com',
        'j.doe@yahoo.com',
        'sam@proton.me',
        'kim@icloud.com',
        'photos@gmx.de',
        'contact@example.org',
    ];

    private const array CANARY_USERNAMES = [
        'alex',
        'maria',
        'john_doe',
        'sam1990',
        'photography',
        'kim.lee',
        'jean-luc',
        'user123',
    ];

    /**
     * @param  string[]  $types
     * @return array{format: string, version: int, exported_at: string, source: string|null, count: int, blocks: array<int, array<string, string|null>>}
     */
    public static function export(array $types = [], bool $includeNotes = false, bool $includeInactive = false): array
    {
        $types = array_values(array_intersect($types ?: RegistrationBlockService::TYPES, RegistrationBlockService::TYPES));

        $blocks = RegistrationBlock::query()
            ->whereIn('type', $types)
            ->when(! $includeInactive, fn ($q) => $q->enforced())
            ->orderBy('type')
            ->orderBy('id')
            ->get()
            ->map(function (RegistrationBlock $b) use ($includeNotes, $includeInactive): array {
                $row = [
                    'type' => $b->type,
                    'match_type' => $b->type === 'ip' ? 'exact' : $b->match_type,
                    'pattern' => $b->pattern,
                ];

                if ($includeNotes) {
                    $row['note'] = $b->note;
                }

                $row['expires_at'] = $b->expires_at?->toIso8601String();

                if ($includeInactive) {
                    $row['status'] = $b->status();
                }

                return $row;
            })
            ->values()
            ->all();

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'source' => config('pixelfed.domain.app'),
            'count' => count($blocks),
            'blocks' => $blocks,
        ];
    }

    public static function toJson(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    }

    public static function toCsv(array $payload): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::CSV_COLUMNS, ',', '"', '');

        foreach ($payload['blocks'] as $row) {
            fputcsv($handle, array_map(
                fn (string $col) => self::csvEscape($row[$col] ?? null),
                self::CSV_COLUMNS
            ), ',', '"', '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public static function filename(string $format): string
    {
        $host = Str::slug((string) (config('pixelfed.domain.app') ?: 'pixelfed'));

        return "registration-blocks-{$host}-".now()->format('Y-m-d').'.'.$format;
    }

    /**
     * @return array<int, array{type: string, match_type: string, pattern: string, note: string|null, expires_at: string|null}>
     *
     * @throws InvalidArgumentException
     */
    public static function parse(string $contents, ?string $format = null): array
    {
        $contents = trim(preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? '');

        if ($contents === '') {
            throw new InvalidArgumentException('The import is empty.');
        }

        $format ??= in_array($contents[0], ['{', '['], true) ? 'json' : 'csv';

        $rows = match ($format) {
            'json' => self::parseJson($contents),
            'csv' => self::parseCsv($contents),
            default => throw new InvalidArgumentException('Unsupported format, use json or csv.'),
        };

        if (! count($rows)) {
            throw new InvalidArgumentException('No blocks were found in the import.');
        }

        if (count($rows) > self::MAX_IMPORT_ROWS) {
            throw new InvalidArgumentException('Imports are limited to '.number_format(self::MAX_IMPORT_ROWS).' blocks at a time.');
        }

        return $rows;
    }

    /**
     * @return array<int, array{type: string, match_type: string, pattern: string, note: string|null, expires_at: string|null}>
     */
    protected static function parseJson(string $contents): array
    {
        try {
            $data = json_decode($contents, true, 16, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new InvalidArgumentException('The import is not valid JSON.');
        }

        if (! is_array($data)) {
            throw new InvalidArgumentException('The import is not a registration blocks export.');
        }

        if (! array_is_list($data)) {
            if (isset($data['format']) && $data['format'] !== self::FORMAT) {
                throw new InvalidArgumentException('The import is not a registration blocks export.');
            }

            if (isset($data['version']) && (int) $data['version'] > self::VERSION) {
                throw new InvalidArgumentException('This export was made by a newer version of Pixelfed.');
            }

            $data = $data['blocks'] ?? null;

            if (! is_array($data)) {
                throw new InvalidArgumentException('The import has no blocks list.');
            }
        }

        return array_values(array_map(
            fn ($row) => self::row(is_array($row) ? $row : []),
            $data
        ));
    }

    /**
     * @return array<int, array{type: string, match_type: string, pattern: string, note: string|null, expires_at: string|null}>
     */
    protected static function parseCsv(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];
        $header = null;
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }

            $cells = array_map(fn ($c) => self::csvUnescape((string) $c), str_getcsv($line, ',', '"', ''));

            if ($header === null) {
                $normalized = array_map(fn ($c) => strtolower(trim($c)), $cells);

                if (in_array('type', $normalized, true) && in_array('pattern', $normalized, true)) {
                    $header = $normalized;

                    continue;
                }

                $header = self::CSV_COLUMNS;
            }

            $assoc = [];
            foreach ($header as $i => $col) {
                $assoc[$col] = $cells[$i] ?? null;
            }

            $rows[] = self::row($assoc);
        }

        return $rows;
    }

    /**
     * @return array{type: string, match_type: string, pattern: string, note: string|null, expires_at: string|null}
     */
    protected static function row(array $row): array
    {
        $str = fn ($v) => is_scalar($v) ? trim((string) $v) : '';

        $note = $str($row['note'] ?? null);
        $expires = $str($row['expires_at'] ?? null);

        return [
            'type' => strtolower($str($row['type'] ?? null)),
            'match_type' => strtolower($str($row['match_type'] ?? null)) ?: 'exact',
            'pattern' => $str($row['pattern'] ?? null),
            'note' => $note !== '' ? $note : null,
            'expires_at' => $expires !== '' ? $expires : null,
        ];
    }

    /**
     * @param  array<int, array{type: string, match_type: string, pattern: string, note: string|null, expires_at: string|null}>  $rows
     * @param  array{types?: string[], expires?: CarbonInterface|null|false, note?: string|null, dry_run?: bool, created_by?: int|null}  $options
     * @return array{added: int, existing: int, expired: int, filtered: int, invalid: array<int, string>, broad: array<int, string>, dry_run: bool}
     */
    public static function import(array $rows, array $options = []): array
    {
        $types = array_values(array_intersect($options['types'] ?? RegistrationBlockService::TYPES, RegistrationBlockService::TYPES));
        $expiresOverride = array_key_exists('expires', $options) ? $options['expires'] : false;
        $noteOverride = isset($options['note']) && trim((string) $options['note']) !== '' ? trim((string) $options['note']) : null;
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $createdBy = $options['created_by'] ?? null;

        $result = [
            'added' => 0,
            'existing' => 0,
            'expired' => 0,
            'filtered' => 0,
            'invalid' => [],
            'broad' => [],
            'dry_run' => $dryRun,
        ];

        $seen = [];

        foreach ($rows as $i => $row) {
            $line = $i + 1;
            $type = $row['type'];
            $matchType = $type === 'ip' ? 'exact' : $row['match_type'];
            $label = "#{$line} ".($type ?: '?').' '.Str::limit($row['pattern'], 80);

            if ($type !== '' && in_array($type, RegistrationBlockService::TYPES, true) && ! in_array($type, $types, true)) {
                $result['filtered']++;

                continue;
            }

            $error = RegistrationBlockService::validatePattern($type, $matchType, $row['pattern']);
            if ($error !== null) {
                $result['invalid'][] = "{$label}: {$error}";

                continue;
            }

            if ($expiresOverride === false) {
                try {
                    $expiresAt = $row['expires_at'] !== null ? Carbon::parse($row['expires_at']) : null;
                } catch (Throwable) {
                    $result['invalid'][] = "{$label}: Invalid expires_at date.";

                    continue;
                }

                if ($expiresAt !== null && $expiresAt->isPast()) {
                    $result['expired']++;

                    continue;
                }
            } else {
                $expiresAt = $expiresOverride;
            }

            $pattern = RegistrationBlockService::normalizePattern($type, $matchType, $row['pattern']);

            if (self::isTooBroad($type, $matchType, $pattern)) {
                $result['broad'][] = "{$label}: Matches common legitimate signups, add it by hand if you really want it.";

                continue;
            }

            $key = $type.'|'.$matchType.'|'.$pattern;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $exists = RegistrationBlock::query()
                ->where('type', $type)
                ->where('match_type', $matchType)
                ->where('pattern', $pattern)
                ->exists();

            if ($exists) {
                $result['existing']++;

                continue;
            }

            if (! $dryRun) {
                RegistrationBlockService::store(
                    $type,
                    $matchType,
                    $pattern,
                    $noteOverride ?? $row['note'],
                    $expiresAt,
                    $createdBy
                );
            }

            $result['added']++;
        }

        return $result;
    }

    public static function isTooBroad(string $type, string $matchType, string $pattern): bool
    {
        $rule = [['id' => 0, 'type' => $type, 'match_type' => $matchType, 'pattern' => $pattern, 'expires_at' => null]];

        return match ($type) {
            'email', 'email_domain' => collect(self::CANARY_EMAILS)
                ->contains(fn ($email) => RegistrationBlockService::find($rule, email: $email) !== null),
            'username' => collect(self::CANARY_USERNAMES)
                ->contains(fn ($username) => RegistrationBlockService::find($rule, username: $username) !== null),
            default => false,
        };
    }

    protected static function csvEscape(?string $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }

    protected static function csvUnescape(string $value): string
    {
        if (strlen($value) > 1 && $value[0] === "'" && in_array($value[1], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return substr($value, 1);
        }

        return $value;
    }
}
