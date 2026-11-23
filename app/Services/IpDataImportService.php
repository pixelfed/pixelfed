<?php

namespace App\Services;

use App\Jobs\IpDataPipeline\BackfillSignupNetworks;
use App\Jobs\IpDataPipeline\ImportIpDataset;
use App\Jobs\IpDataPipeline\PurgeIpDataset;
use App\Models\IpDataset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;
use ZipArchive;

class IpDataImportService
{
    public const int CHUNK_MIN = 262144;

    public const int CHUNK_MAX = 8388608;

    public const int MAX_UPLOAD = 1073741824;

    public const int MAX_EXTRACTED = 2147483648;

    public const int SLICE_ROWS = 100000;

    public const int SLICE_SECONDS = 150;

    public const int INSERT_BATCH = 1000;

    public const int PURGE_BATCH = 20000;

    public const float SHRINK_RATIO = 0.5;

    public const array COLUMNS = [
        IpDataset::TYPE_COUNTRY => [
            'required' => ['network', 'country_code'],
            'optional' => ['continent_code', 'country_name'],
        ],
        IpDataset::TYPE_ASN => [
            'required' => ['network', 'asn'],
            'optional' => ['country_code', 'name', 'org', 'domain'],
        ],
    ];

    public const string HEADER_HELP = 'Expected network,continent_code,country_code,country_name or network,asn,country_code,name,org,domain.';

    public static function directory(string $sub = ''): string
    {
        $path = storage_path('app/ip-data'.($sub !== '' ? '/'.$sub : ''));

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $path;
    }

    public static function zipSupported(): bool
    {
        return class_exists(ZipArchive::class);
    }

    public static function chunkSize(): int
    {
        $limits = array_filter([
            self::iniBytes((string) ini_get('upload_max_filesize')),
            self::iniBytes((string) ini_get('post_max_size')),
        ]);

        $limit = count($limits) ? min($limits) - 65536 : self::CHUNK_MAX;

        return (int) max(self::CHUNK_MIN, min(self::CHUNK_MAX, $limit));
    }

    public static function iniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1073741824,
            'm' => $number * 1048576,
            'k' => $number * 1024,
            default => $number,
        };
    }

    public static function validUploadId(string $uploadId): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9]{16,64}$/', $uploadId);
    }

    public static function validName(string $name): bool
    {
        $name = strtolower($name);

        return str_ends_with($name, '.csv') || str_ends_with($name, '.zip');
    }

    public static function receiveChunk(string $uploadId, int $offset, int $total, string $name, UploadedFile $chunk, bool $final, ?int $userId = null): array
    {
        if (! self::validUploadId($uploadId)) {
            throw new InvalidArgumentException('Invalid upload.');
        }

        $name = Str::limit(basename(str_replace('\\', '/', $name)), 200, '');

        if (! self::validName($name)) {
            throw new InvalidArgumentException('Upload a .csv or .csv.zip file.');
        }

        if (str_ends_with(strtolower($name), '.zip') && ! self::zipSupported()) {
            throw new InvalidArgumentException('This server can not read zip files. Upload the unzipped .csv file instead.');
        }

        if ($total < 1 || $total > self::MAX_UPLOAD) {
            throw new InvalidArgumentException('That file is too large.');
        }

        $part = self::directory('uploads').'/'.$uploadId.'.part';
        $lock = Cache::lock('pf:ip-data:upload:'.$uploadId, 60);

        if (! $lock->get()) {
            throw new InvalidArgumentException('This upload is busy, try again.');
        }

        try {
            clearstatcache(true, $part);
            $current = is_file($part) ? (int) filesize($part) : 0;

            if ($offset === 0 && $current > 0) {
                unlink($part);
                $current = 0;
            }

            if ($offset !== $current) {
                return ['received' => $current, 'expected_offset' => $current, 'dataset' => null];
            }

            $size = (int) $chunk->getSize();

            if ($size < 1 || $offset + $size > $total) {
                throw new InvalidArgumentException('Chunk does not fit the upload.');
            }

            $in = fopen($chunk->getRealPath(), 'rb');
            $out = fopen($part, 'ab');

            if ($in === false || $out === false) {
                throw new InvalidArgumentException('Could not save the upload.');
            }

            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);

            clearstatcache(true, $part);
            $received = (int) filesize($part);

            if (! $final) {
                return ['received' => $received, 'expected_offset' => null, 'dataset' => null];
            }

            if ($received !== $total) {
                unlink($part);
                throw new InvalidArgumentException('The upload was incomplete, try again.');
            }

            $dataset = self::stage($part, $name, $total, $userId);

            return ['received' => $received, 'expected_offset' => null, 'dataset' => $dataset->toApi()];
        } finally {
            $lock->release();
        }
    }

    public static function stageLocal(string $path, bool $force = false, ?int $userId = null, bool $dispatch = true): IpDataset
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('File not found or not readable.');
        }

        $name = basename($path);

        if (! self::validName($name)) {
            throw new InvalidArgumentException('Use a .csv or .csv.zip file.');
        }

        $copy = self::directory('uploads').'/'.Str::random(32).'.part';

        if (! copy($path, $copy)) {
            throw new InvalidArgumentException('Could not copy the file into storage.');
        }

        return self::stage($copy, $name, (int) filesize($copy), $userId, $force, $dispatch);
    }

    public static function stage(string $part, string $name, int $size, ?int $userId = null, bool $force = false, bool $dispatch = true): IpDataset
    {
        $dataset = IpDataset::create([
            'status' => IpDataset::STATUS_PENDING,
            'source_name' => $name,
            'source_size' => $size,
            'allow_smaller' => $force,
            'created_by' => $userId,
        ]);

        $csv = self::directory().'/'.$dataset->id.'.csv';

        try {
            if (str_ends_with(strtolower($name), '.zip')) {
                self::extractZip($part, $csv);
                if (is_file($part)) {
                    unlink($part);
                }
            } elseif (! rename($part, $csv)) {
                throw new InvalidArgumentException('Could not move the upload into place.');
            }

            [$type] = self::readHeader($csv);

            $dataset->update([
                'type' => $type,
                'path' => $csv,
                'bytes_total' => (int) filesize($csv),
            ]);
        } catch (Throwable $e) {
            if (is_file($part)) {
                unlink($part);
            }
            $dataset->update(['path' => is_file($csv) ? $csv : null]);
            self::fail($dataset, $e instanceof InvalidArgumentException ? $e->getMessage() : 'The file could not be read.');

            return $dataset->refresh();
        }

        IpDataset::query()
            ->where('type', $type)
            ->whereIn('status', IpDataset::RUNNING)
            ->where('id', '!=', $dataset->id)
            ->get()
            ->each(fn (IpDataset $older) => self::cancel($older, 'Replaced by a newer upload.'));

        if ($dispatch) {
            ImportIpDataset::dispatch($dataset->id);
        }

        return $dataset;
    }

    public static function extractZip(string $zipPath, string $destination): void
    {
        if (! self::zipSupported()) {
            throw new InvalidArgumentException('This server can not read zip files. Upload the unzipped .csv file instead.');
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new InvalidArgumentException('The zip file could not be opened.');
        }

        try {
            $entry = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);

                if ($stat === false) {
                    continue;
                }

                $entryName = (string) $stat['name'];

                if (str_starts_with($entryName, '__MACOSX/') || str_starts_with(basename($entryName), '.')) {
                    continue;
                }

                if (str_ends_with(strtolower($entryName), '.csv')) {
                    $entry = $stat;
                    break;
                }
            }

            if ($entry === null) {
                throw new InvalidArgumentException('The zip file has no .csv file in it.');
            }

            if ((int) $entry['size'] > self::MAX_EXTRACTED) {
                throw new InvalidArgumentException('The .csv file in the zip is too large.');
            }

            $in = $zip->getStream((string) $entry['name']);
            $out = fopen($destination, 'wb');

            if ($in === false || $out === false) {
                throw new InvalidArgumentException('The zip file could not be read.');
            }

            $written = stream_copy_to_stream($in, $out, self::MAX_EXTRACTED + 1);
            fclose($in);
            fclose($out);

            if ($written === false || $written > self::MAX_EXTRACTED) {
                throw new InvalidArgumentException('The .csv file in the zip is too large.');
            }
        } finally {
            $zip->close();
        }
    }

    public static function readHeader(string $csv): array
    {
        $handle = fopen($csv, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('The file could not be read.');
        }

        $header = fgetcsv($handle, null, ',', '"', '');
        fclose($handle);

        return self::detect(is_array($header) ? $header : []);
    }

    public static function detect(array $header): array
    {
        $names = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        if (isset($names[0])) {
            $names[0] = trim(str_replace("\xEF\xBB\xBF", '', $names[0]));
        }

        $type = match (true) {
            in_array('asn', $names, true) => IpDataset::TYPE_ASN,
            in_array('country_code', $names, true) => IpDataset::TYPE_COUNTRY,
            default => null,
        };

        if ($type === null) {
            throw new InvalidArgumentException('Unrecognized header row. '.self::HEADER_HELP);
        }

        $map = [];

        foreach (self::COLUMNS[$type]['required'] as $column) {
            $index = array_search($column, $names, true);

            if ($index === false) {
                throw new InvalidArgumentException("Missing the {$column} column. ".self::HEADER_HELP);
            }

            $map[$column] = (int) $index;
        }

        foreach (self::COLUMNS[$type]['optional'] as $column) {
            $index = array_search($column, $names, true);
            $map[$column] = $index === false ? null : (int) $index;
        }

        return [$type, $map];
    }

    public static function parseRow(string $type, array $map, array $row): ?array
    {
        $network = IpDataService::parseNetwork((string) ($row[$map['network']] ?? ''));

        if ($network === null) {
            return null;
        }

        $cell = fn (string $column, int $limit = 255): ?string => $map[$column] !== null && isset($row[$map[$column]]) && trim((string) $row[$map[$column]]) !== ''
            ? mb_substr(trim((string) $row[$map[$column]]), 0, $limit)
            : null;

        if ($type === IpDataset::TYPE_COUNTRY) {
            $code = strtoupper((string) $cell('country_code', 8));

            if (! preg_match('/^[A-Z]{2}$/', $code)) {
                return null;
            }

            $continent = strtoupper((string) $cell('continent_code', 8));

            return [
                ['start' => $network['start'], 'prefix' => $network['prefix'], 'country_code' => $code],
                [
                    'code' => $code,
                    'continent_code' => preg_match('/^[A-Z]{2}$/', $continent) ? $continent : null,
                    'name' => $cell('country_name', 120) ?? $code,
                ],
                $network['v4'],
            ];
        }

        $asn = RegistrationBlockService::normalizeAsn((string) $cell('asn', 32));

        if ($asn === null) {
            return null;
        }

        $country = strtoupper((string) $cell('country_code', 8));

        return [
            ['start' => $network['start'], 'prefix' => $network['prefix'], 'asn' => $asn],
            [
                'asn' => $asn,
                'name' => $cell('name'),
                'org' => $cell('org'),
                'domain' => $cell('domain'),
                'country_code' => preg_match('/^[A-Z]{2}$/', $country) ? $country : null,
            ],
            $network['v4'],
        ];
    }

    public static function importSlice(IpDataset $dataset, int $maxRows = self::SLICE_ROWS, int $maxSeconds = self::SLICE_SECONDS): bool
    {
        $dataset->refresh();

        if (! $dataset->isRunning()) {
            return true;
        }

        if (! $dataset->path || ! is_file($dataset->path)) {
            self::fail($dataset, 'The uploaded file is missing, upload it again.');

            return true;
        }

        $handle = fopen($dataset->path, 'rb');

        if ($handle === false) {
            self::fail($dataset, 'The uploaded file could not be read.');

            return true;
        }

        try {
            $header = fgetcsv($handle, null, ',', '"', '');
            [$type, $map] = self::detect(is_array($header) ? $header : []);
        } catch (InvalidArgumentException $e) {
            fclose($handle);
            self::fail($dataset, $e->getMessage());

            return true;
        }

        if ($dataset->byte_offset > 0) {
            fseek($handle, $dataset->byte_offset);
        }

        if ($dataset->status === IpDataset::STATUS_PENDING) {
            $dataset->update(['status' => IpDataset::STATUS_IMPORTING, 'started_at' => now()]);
        }

        $table = $dataset->networksTable();
        $started = microtime(true);
        $batch = [];
        $extras = [];
        $read = 0;
        $inserted = 0;
        $skipped = 0;
        $v4 = 0;
        $v6 = 0;
        $eof = false;

        while ($read < $maxRows) {
            $row = fgetcsv($handle, null, ',', '"', '');

            if ($row === false) {
                $eof = true;
                break;
            }

            $read++;

            if ($row === [null]) {
                continue;
            }

            $parsed = self::parseRow($type, $map, $row);

            if ($parsed === null) {
                $skipped++;

                continue;
            }

            [$record, $extra, $isV4] = $parsed;
            $record['dataset_id'] = $dataset->id;
            $batch[] = $record;
            $isV4 ? $v4++ : $v6++;
            $extras[$type === IpDataset::TYPE_ASN ? $extra['asn'] : $extra['code']] = $extra;

            if (count($batch) >= self::INSERT_BATCH) {
                $inserted += DB::table($table)->insertOrIgnore($batch);
                $batch = [];
            }

            if ($read % 5000 === 0 && microtime(true) - $started > $maxSeconds) {
                break;
            }
        }

        if (count($batch)) {
            $inserted += DB::table($table)->insertOrIgnore($batch);
        }

        self::storeExtras($type, $extras);

        $offset = $eof ? $dataset->bytes_total : (int) ftell($handle);
        fclose($handle);

        IpDataset::query()->where('id', $dataset->id)->update([
            'byte_offset' => $offset,
            'rows_imported' => DB::raw('rows_imported + '.(int) $inserted),
            'rows_skipped' => DB::raw('rows_skipped + '.(int) $skipped),
            'networks_v4' => DB::raw('networks_v4 + '.(int) $v4),
            'networks_v6' => DB::raw('networks_v6 + '.(int) $v6),
            'updated_at' => now(),
        ]);

        $dataset->refresh();

        if ($dataset->status === IpDataset::STATUS_CANCELLED) {
            PurgeIpDataset::dispatch($dataset->id);

            return true;
        }

        if ($eof) {
            self::finalize($dataset);

            return true;
        }

        return false;
    }

    protected static function storeExtras(string $type, array $extras): void
    {
        if (! count($extras)) {
            return;
        }

        $now = now();

        foreach (array_chunk(array_values($extras), self::INSERT_BATCH) as $chunk) {
            $rows = array_map(fn ($row) => $row + ['updated_at' => $now], $chunk);

            if ($type === IpDataset::TYPE_ASN) {
                DB::table('ip_asns')->upsert($rows, ['asn'], ['name', 'org', 'domain', 'country_code', 'updated_at']);
            } else {
                DB::table('ip_countries')->upsert($rows, ['code'], ['continent_code', 'name', 'updated_at']);
            }
        }
    }

    public static function finalize(IpDataset $dataset): void
    {
        $dataset->refresh();

        if ($dataset->status !== IpDataset::STATUS_IMPORTING) {
            return;
        }

        if ($dataset->rows_imported < 1) {
            self::fail($dataset, 'No networks were found in the file.');

            return;
        }

        $current = IpDataset::query()
            ->where('type', $dataset->type)
            ->where('status', IpDataset::STATUS_ACTIVE)
            ->orderByDesc('id')
            ->first();

        if ($current && ! $dataset->allow_smaller && $dataset->rows_imported < $current->rows_imported * self::SHRINK_RATIO) {
            self::fail($dataset, sprintf(
                'Only %s networks, less than half of the active data (%s). Run php artisan app:ip-data import with --force to use it anyway.',
                number_format($dataset->rows_imported),
                number_format($current->rows_imported)
            ));

            return;
        }

        $entities = (int) DB::table($dataset->networksTable())
            ->where('dataset_id', $dataset->id)
            ->distinct()
            ->count($dataset->type === IpDataset::TYPE_ASN ? 'asn' : 'country_code');

        DB::transaction(function () use ($dataset, $entities) {
            IpDataset::query()
                ->where('type', $dataset->type)
                ->where('status', IpDataset::STATUS_ACTIVE)
                ->where('id', '!=', $dataset->id)
                ->update(['status' => IpDataset::STATUS_REPLACED, 'updated_at' => now()]);

            $dataset->update([
                'status' => IpDataset::STATUS_ACTIVE,
                'entities' => $entities,
                'byte_offset' => $dataset->bytes_total,
                'finished_at' => now(),
                'activated_at' => now(),
            ]);
        });

        IpDataService::flush();
        self::removeFile($dataset);

        IpDataset::query()
            ->where('type', $dataset->type)
            ->whereIn('status', [IpDataset::STATUS_REPLACED, IpDataset::STATUS_FAILED, IpDataset::STATUS_CANCELLED])
            ->whereNull('purged_at')
            ->pluck('id')
            ->each(fn ($id) => PurgeIpDataset::dispatch((int) $id));

        BackfillSignupNetworks::dispatch();
    }

    public static function fail(IpDataset $dataset, string $message): void
    {
        $dataset->update([
            'status' => IpDataset::STATUS_FAILED,
            'error' => Str::limit($message, 490),
            'finished_at' => now(),
        ]);

        self::removeFile($dataset);

        if ($dataset->rows_imported > 0 || $dataset->byte_offset > 0) {
            PurgeIpDataset::dispatch($dataset->id);
        } else {
            $dataset->update(['purged_at' => now()]);
        }
    }

    public static function cancel(IpDataset $dataset, string $message = 'Cancelled.'): bool
    {
        if (! $dataset->isRunning()) {
            return false;
        }

        $dataset->update([
            'status' => IpDataset::STATUS_CANCELLED,
            'error' => Str::limit($message, 490),
            'finished_at' => now(),
        ]);

        self::removeFile($dataset);
        PurgeIpDataset::dispatch($dataset->id);

        return true;
    }

    public static function removeFile(IpDataset $dataset): void
    {
        if ($dataset->path && is_file($dataset->path)) {
            unlink($dataset->path);
        }

        if ($dataset->path) {
            $dataset->update(['path' => null]);
        }
    }

    public static function purgeSlice(IpDataset $dataset, int $limit = self::PURGE_BATCH): int
    {
        if ($dataset->status === IpDataset::STATUS_ACTIVE || $dataset->isRunning()) {
            return 0;
        }

        $deleted = DB::table($dataset->networksTable())
            ->where('dataset_id', $dataset->id)
            ->limit($limit)
            ->delete();

        if ($deleted < $limit) {
            $dataset->update(['purged_at' => now()]);
        }

        return $deleted;
    }

    public static function cleanupUploads(int $olderThanSeconds = 86400): int
    {
        $removed = 0;

        foreach (glob(self::directory('uploads').'/*.part') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < time() - $olderThanSeconds) {
                unlink($file);
                $removed++;
            }
        }

        return $removed;
    }

    public static function backfillSlice(int $afterId = 0, int $limit = 1000, bool $all = false): ?int
    {
        if (! IpDataService::available()) {
            return null;
        }

        $users = DB::table('users')
            ->select(['id', 'app_register_ip', 'signup_country', 'signup_asn'])
            ->where('id', '>', $afterId)
            ->whereNotNull('app_register_ip')
            ->when(! $all, fn ($q) => $q->whereNull('signup_country')->whereNull('signup_asn'))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($users->isEmpty()) {
            return null;
        }

        $results = IpDataService::lookupMany($users->pluck('app_register_ip')->all());

        foreach ($users as $user) {
            $result = $results[$user->app_register_ip] ?? null;
            $country = $result['country']['code'] ?? null;
            $asn = $result['asn']['number'] ?? null;

            if ($country === $user->signup_country && $asn === ($user->signup_asn !== null ? (int) $user->signup_asn : null)) {
                continue;
            }

            DB::table('users')->where('id', $user->id)->update([
                'signup_country' => $country,
                'signup_asn' => $asn,
            ]);
        }

        return (int) $users->last()->id;
    }
}
