<?php

namespace App\Services;

use App\Models\IpDataset;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class IpDataService
{
    public const string ACTIVE_CACHE_KEY = 'pf:ip-data:active:v1';

    public const string LOOKUP_CACHE_PREFIX = 'pf:ip-data:lookup:v1:';

    public const string TOP_CACHE_PREFIX = 'pf:ip-data:top:v1:';

    public const int LOOKUP_TTL = 604800;

    public const int CANDIDATES = 4;

    public const string ATTRIBUTION_URL = 'https://iplocate.io';

    private const string V4_MAPPED = "\0\0\0\0\0\0\0\0\0\0\xff\xff";

    public static function active(): array
    {
        return Cache::rememberForever(self::ACTIVE_CACHE_KEY, function () {
            try {
                $ids = IpDataset::query()
                    ->where('status', IpDataset::STATUS_ACTIVE)
                    ->orderBy('id')
                    ->pluck('id', 'type')
                    ->all();
            } catch (Throwable) {
                $ids = [];
            }

            return [
                IpDataset::TYPE_COUNTRY => isset($ids[IpDataset::TYPE_COUNTRY]) ? (int) $ids[IpDataset::TYPE_COUNTRY] : null,
                IpDataset::TYPE_ASN => isset($ids[IpDataset::TYPE_ASN]) ? (int) $ids[IpDataset::TYPE_ASN] : null,
            ];
        });
    }

    public static function flush(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
    }

    public static function available(): bool
    {
        $active = self::active();

        return $active[IpDataset::TYPE_COUNTRY] !== null || $active[IpDataset::TYPE_ASN] !== null;
    }

    public static function key(?string $ip): ?string
    {
        if (! is_string($ip)) {
            return null;
        }

        $ip = trim($ip);

        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $bin = inet_pton($ip);

        if ($bin === false) {
            return null;
        }

        if (strlen($bin) === 4) {
            $bin = self::V4_MAPPED.$bin;
        }

        return strlen($bin) === 16 ? bin2hex($bin) : null;
    }

    public static function parseNetwork(string $network): ?array
    {
        $network = trim($network);
        $bits = null;

        if (str_contains($network, '/')) {
            [$address, $bits] = explode('/', $network, 2);

            if ($bits === '' || ! ctype_digit($bits)) {
                return null;
            }

            $bits = (int) $bits;
        } else {
            $address = $network;
        }

        if (filter_var($address, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $bin = inet_pton($address);

        if ($bin === false) {
            return null;
        }

        $v4 = strlen($bin) === 4;
        $bits ??= $v4 ? 32 : 128;

        if ($bits > ($v4 ? 32 : 128)) {
            return null;
        }

        $prefix = $v4 ? $bits + 96 : $bits;
        $start = self::mask($v4 ? self::V4_MAPPED.$bin : $bin, $prefix, false);

        return [
            'start' => bin2hex($start),
            'prefix' => $prefix,
            'v4' => $v4,
        ];
    }

    public static function endKey(string $start, int $prefix): string
    {
        return bin2hex(self::mask((string) hex2bin($start), $prefix, true));
    }

    public static function cidr(string $start, int $prefix): string
    {
        $bin = (string) hex2bin($start);

        if ($prefix >= 96 && str_starts_with($bin, self::V4_MAPPED)) {
            return inet_ntop(substr($bin, 12)).'/'.($prefix - 96);
        }

        return inet_ntop($bin).'/'.$prefix;
    }

    public static function display(string $key): string
    {
        $bin = (string) hex2bin($key);

        if (str_starts_with($bin, self::V4_MAPPED)) {
            return (string) inet_ntop(substr($bin, 12));
        }

        return (string) inet_ntop($bin);
    }

    protected static function mask(string $bin, int $prefix, bool $fill): string
    {
        $prefix = max(0, min(128, $prefix));
        $whole = intdiv($prefix, 8);

        if ($whole >= 16) {
            return $bin;
        }

        $rem = $prefix % 8;
        $mask = $rem === 0 ? 0 : (0xFF << (8 - $rem)) & 0xFF;
        $byte = ord($bin[$whole]);
        $middle = $fill ? (($byte & $mask) | (~$mask & 0xFF)) : ($byte & $mask);

        return substr($bin, 0, $whole).chr($middle).str_repeat($fill ? "\xff" : "\0", 15 - $whole);
    }

    public static function lookup(?string $ip): ?array
    {
        $key = self::key($ip);

        if ($key === null) {
            return null;
        }

        $active = self::active();

        if ($active[IpDataset::TYPE_COUNTRY] === null && $active[IpDataset::TYPE_ASN] === null) {
            return null;
        }

        try {
            $result = Cache::remember(
                self::cacheKey($active, $key),
                self::LOOKUP_TTL,
                fn () => self::resolve($active, $key) ?? []
            );
        } catch (Throwable) {
            return null;
        }

        return $result === [] ? null : $result;
    }

    public static function lookupMany(array $ips): array
    {
        $out = [];
        $keys = [];

        foreach ($ips as $ip) {
            if (! is_string($ip) || $ip === '' || array_key_exists($ip, $out)) {
                continue;
            }

            $out[$ip] = null;
            $key = self::key($ip);

            if ($key !== null) {
                $keys[$ip] = $key;
            }
        }

        $active = self::active();

        if (! count($keys) || ($active[IpDataset::TYPE_COUNTRY] === null && $active[IpDataset::TYPE_ASN] === null)) {
            return $out;
        }

        try {
            $cacheKeys = [];
            foreach ($keys as $ip => $key) {
                $cacheKeys[$ip] = self::cacheKey($active, $key);
            }

            $cached = Cache::many(array_values($cacheKeys));

            foreach ($cacheKeys as $ip => $cacheKey) {
                $hit = $cached[$cacheKey] ?? null;

                if (! is_array($hit)) {
                    $hit = self::resolve($active, $keys[$ip]) ?? [];
                    Cache::put($cacheKey, $hit, self::LOOKUP_TTL);
                }

                $out[$ip] = $hit === [] ? null : $hit;
            }
        } catch (Throwable) {
            return $out;
        }

        return $out;
    }

    protected static function cacheKey(array $active, string $key): string
    {
        return self::LOOKUP_CACHE_PREFIX.(int) $active[IpDataset::TYPE_COUNTRY].':'.(int) $active[IpDataset::TYPE_ASN].':'.$key;
    }

    protected static function resolve(array $active, string $key): ?array
    {
        $country = null;
        $asn = null;

        if ($active[IpDataset::TYPE_COUNTRY] !== null) {
            $row = self::findNetwork(
                'ip_country_networks',
                $active[IpDataset::TYPE_COUNTRY],
                $key,
                ['n.country_code', 'i.name', 'i.continent_code'],
                'ip_countries',
                'i.code',
                'n.country_code'
            );

            if ($row) {
                $country = [
                    'code' => (string) $row->country_code,
                    'name' => $row->name ?? (string) $row->country_code,
                    'continent' => $row->continent_code ?? null,
                    'network' => self::cidr($row->start, (int) $row->prefix),
                ];
            }
        }

        if ($active[IpDataset::TYPE_ASN] !== null) {
            $row = self::findNetwork(
                'ip_asn_networks',
                $active[IpDataset::TYPE_ASN],
                $key,
                ['n.asn', 'i.name', 'i.org', 'i.domain', 'i.country_code'],
                'ip_asns',
                'i.asn',
                'n.asn'
            );

            if ($row) {
                $asn = [
                    'number' => (int) $row->asn,
                    'name' => $row->name ?? null,
                    'org' => $row->org ?? null,
                    'domain' => $row->domain ?? null,
                    'country' => $row->country_code ?? null,
                    'network' => self::cidr($row->start, (int) $row->prefix),
                ];
            }
        }

        if ($country === null && $asn === null) {
            return null;
        }

        return [
            'ip' => self::display($key),
            'country' => $country,
            'asn' => $asn,
        ];
    }

    protected static function findNetwork(string $table, int $datasetId, string $key, array $columns, string $infoTable, string $infoKey, string $networkKey): ?object
    {
        $rows = DB::table($table.' as n')
            ->leftJoin($infoTable.' as i', $infoKey, '=', $networkKey)
            ->where('n.dataset_id', $datasetId)
            ->where('n.start', '<=', $key)
            ->orderByDesc('n.start')
            ->limit(self::CANDIDATES)
            ->get(array_merge(['n.start', 'n.prefix'], $columns));

        foreach ($rows as $row) {
            if (strcmp(self::endKey($row->start, (int) $row->prefix), $key) >= 0) {
                return $row;
            }
        }

        return null;
    }

    public static function signupNetwork(?string $ip): ?array
    {
        $result = self::lookup($ip);

        if ($result === null) {
            return null;
        }

        return [
            'asn' => $result['asn']['number'] ?? null,
            'country' => $result['country']['code'] ?? null,
        ];
    }

    public static function stamp(User $user): void
    {
        try {
            if (! self::available()) {
                return;
            }

            $network = self::signupNetwork($user->app_register_ip);
            $user->signup_country = $network['country'] ?? null;
            $user->signup_asn = $network['asn'] ?? null;
        } catch (Throwable) {
        }
    }

    public static function forUser(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        $result = $user->app_register_ip ? self::lookup($user->app_register_ip) : null;

        if ($result !== null) {
            return $result;
        }

        return self::fromStamp($user->signup_country, $user->signup_asn !== null ? (int) $user->signup_asn : null, $user->app_register_ip);
    }

    public static function fromStamp(?string $country, ?int $asn, ?string $ip = null): ?array
    {
        if ($country === null && $asn === null) {
            return null;
        }

        try {
            $countryInfo = $country ? DB::table('ip_countries')->where('code', $country)->first() : null;
            $asnInfo = $asn ? DB::table('ip_asns')->where('asn', $asn)->first() : null;
        } catch (Throwable) {
            $countryInfo = null;
            $asnInfo = null;
        }

        return [
            'ip' => $ip,
            'country' => $country ? [
                'code' => $country,
                'name' => $countryInfo->name ?? $country,
                'continent' => $countryInfo->continent_code ?? null,
                'network' => null,
            ] : null,
            'asn' => $asn ? [
                'number' => $asn,
                'name' => $asnInfo->name ?? null,
                'org' => $asnInfo->org ?? null,
                'domain' => $asnInfo->domain ?? null,
                'country' => $asnInfo->country_code ?? null,
                'network' => null,
            ] : null,
        ];
    }

    public static function asnLabels(array $asns): array
    {
        $asns = array_values(array_unique(array_filter(array_map('intval', $asns))));

        if (! count($asns)) {
            return [];
        }

        try {
            return DB::table('ip_asns')
                ->whereIn('asn', $asns)
                ->get(['asn', 'name', 'org'])
                ->mapWithKeys(fn ($row) => [(int) $row->asn => (string) ($row->org ?: $row->name)])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    public static function countryLabels(array $codes): array
    {
        $codes = array_values(array_unique(array_filter(array_map(fn ($c) => strtoupper((string) $c), $codes))));

        if (! count($codes)) {
            return [];
        }

        try {
            return DB::table('ip_countries')
                ->whereIn('code', $codes)
                ->pluck('name', 'code')
                ->map(fn ($name) => (string) $name)
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    public static function datasets(): array
    {
        $out = [];

        foreach (IpDataset::TYPES as $type) {
            $out[$type] = [
                'active' => IpDataset::query()
                    ->where('type', $type)
                    ->where('status', IpDataset::STATUS_ACTIVE)
                    ->orderByDesc('id')
                    ->first(),
                'running' => IpDataset::query()
                    ->where('type', $type)
                    ->whereIn('status', IpDataset::RUNNING)
                    ->orderByDesc('id')
                    ->first(),
            ];
        }

        return $out;
    }

    public static function topSignups(string $column, int $days, int $limit = 8): array
    {
        if (! in_array($column, ['signup_asn', 'signup_country'], true)) {
            return [];
        }

        return Cache::remember(self::TOP_CACHE_PREFIX.$column.':'.$days.':'.$limit, 600, function () use ($column, $days, $limit) {
            $rows = DB::table('users')
                ->select($column)
                ->selectRaw('count(*) as total')
                ->selectRaw('sum(case when status is not null or delete_after is not null then 1 else 0 end) as removed')
                ->whereNotNull($column)
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy($column)
                ->orderByDesc('total')
                ->limit($limit)
                ->get();

            $values = $rows->pluck($column)->all();
            $labels = $column === 'signup_asn' ? self::asnLabels($values) : self::countryLabels($values);

            return $rows->map(fn ($row) => [
                'value' => $column === 'signup_asn' ? (int) $row->{$column} : (string) $row->{$column},
                'label' => $labels[$column === 'signup_asn' ? (int) $row->{$column} : (string) $row->{$column}] ?? null,
                'total' => (int) $row->total,
                'removed' => (int) $row->removed,
            ])->values()->all();
        });
    }
}
