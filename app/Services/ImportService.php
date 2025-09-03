<?php

namespace App\Services;

use App\Models\ImportPost;
use Cache;
use DB;
use Illuminate\Database\QueryException;

class ImportService
{
    const CACHE_KEY = 'pf:import-service:';

    public static function getId($userId, $year, $month, $day)
    {
        if ($userId > 999999) {
            return null;
        }
        if ($year < 9 || $year > (int) now()->addYear()->format('y')) {
            return null;
        }
        if ($month < 1 || $month > 12) {
            return null;
        }
        if ($day < 1 || $day > 31) {
            return null;
        }

        $start = 1;
        $maxAttempts = 10;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($userId, $year, $month, $day, $start) {
                    $maxExistingIncr = ImportPost::where('user_id', $userId)
                        ->where('creation_year', $year)
                        ->where('creation_month', $month)
                        ->where('creation_day', $day)
                        ->lockForUpdate()
                        ->max('creation_id') ?? 0;

                    $incr = $maxExistingIncr + 1;

                    if ($incr > 999) {
                        [$newYear, $newMonth, $newDay] = self::getNextValidDate($year, $month, $day);
                        if (! $newYear) {
                            throw new \Exception('Could not find valid next date');
                        }

                        return self::getId($userId, $newYear, $newMonth, $newDay);
                    }

                    $uid = str_pad($userId, 6, 0, STR_PAD_LEFT);
                    $yearStr = str_pad($year, 2, 0, STR_PAD_LEFT);
                    $monthStr = str_pad($month, 2, 0, STR_PAD_LEFT);
                    $dayStr = str_pad($day, 2, 0, STR_PAD_LEFT);
                    $zone = $yearStr.$monthStr.$dayStr.str_pad($incr, 3, 0, STR_PAD_LEFT);

                    return [
                        'id' => $start.$uid.$zone,
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                        'incr' => $incr,
                        'user_id' => $userId,
                    ];
                }, 3);
            } catch (QueryException $e) {
                if ($e->getCode() === '40001') {
                    usleep(random_int(1000, 10000));

                    continue;
                }
                throw $e;
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'Could not find valid next date') !== false) {
                    return null;
                }
                throw $e;
            }
        }

        return null;
    }

    public static function getAndReserveId($userId, $year, $month, $day, $importPostData = [])
    {
        if ($userId > 999999) {
            return null;
        }
        if ($year < 9 || $year > (int) now()->addYear()->format('y')) {
            return null;
        }
        if ($month < 1 || $month > 12) {
            return null;
        }
        if ($day < 1 || $day > 31) {
            return null;
        }

        $start = 1;
        $maxAttempts = 10;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($userId, $year, $month, $day, $start, $importPostData) {
                    $maxExistingIncr = ImportPost::where('user_id', $userId)
                        ->where('creation_year', $year)
                        ->where('creation_month', $month)
                        ->where('creation_day', $day)
                        ->lockForUpdate()
                        ->max('creation_id') ?? 0;

                    $incr = $maxExistingIncr + 1;

                    if ($incr > 999) {
                        [$newYear, $newMonth, $newDay] = self::getNextValidDate($year, $month, $day);
                        if (! $newYear) {
                            throw new \Exception('Could not find valid next date');
                        }

                        return self::getAndReserveId($userId, $newYear, $newMonth, $newDay, $importPostData);
                    }

                    $uid = str_pad($userId, 6, 0, STR_PAD_LEFT);
                    $yearStr = str_pad($year, 2, 0, STR_PAD_LEFT);
                    $monthStr = str_pad($month, 2, 0, STR_PAD_LEFT);
                    $dayStr = str_pad($day, 2, 0, STR_PAD_LEFT);
                    $zone = $yearStr.$monthStr.$dayStr.str_pad($incr, 3, 0, STR_PAD_LEFT);

                    $idData = [
                        'id' => $start.$uid.$zone,
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                        'incr' => $incr,
                        'user_id' => $userId,
                    ];

                    $placeholder = new ImportPost(array_merge([
                        'user_id' => $userId,
                        'creation_year' => $year,
                        'creation_month' => $month,
                        'creation_day' => $day,
                        'creation_id' => $incr,
                        'reserved_at' => now(),
                    ], $importPostData));

                    $placeholder->save();

                    return [
                        'id_data' => $idData,
                        'import_post' => $placeholder,
                    ];
                }, 3);
            } catch (QueryException $e) {
                if ($e->getCode() === '40001') {
                    usleep(random_int(1000, 10000));

                    continue;
                }

                if ($e->getCode() === '23000') {
                    usleep(random_int(100, 1000));

                    continue;
                }

                throw $e;
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'Could not find valid next date') !== false) {
                    return null;
                }
                throw $e;
            }
        }

        return null;
    }

    public static function getUniqueCreationId($userId, $year, $month, $day, $excludeImportPostId = null)
    {
        return DB::transaction(function () use ($userId, $year, $month, $day, $excludeImportPostId) {
            $query = ImportPost::where('user_id', $userId)
                ->where('creation_year', $year)
                ->where('creation_month', $month)
                ->where('creation_day', $day)
                ->lockForUpdate();

            if ($excludeImportPostId) {
                $query->where('id', '!=', $excludeImportPostId);
            }

            $maxExistingIncr = $query->max('creation_id') ?? 0;

            $incr = $maxExistingIncr + 1;

            while ($incr <= 999) {
                $uid = str_pad($userId, 6, 0, STR_PAD_LEFT);
                $yearStr = str_pad($year, 2, 0, STR_PAD_LEFT);
                $monthStr = str_pad($month, 2, 0, STR_PAD_LEFT);
                $dayStr = str_pad($day, 2, 0, STR_PAD_LEFT);
                $zone = $yearStr.$monthStr.$dayStr.str_pad($incr, 3, 0, STR_PAD_LEFT);
                $statusId = '1'.$uid.$zone;

                $statusExists = DB::table('statuses')->where('id', $statusId)->exists();

                $importExists = ImportPost::where('user_id', $userId)
                    ->where('creation_year', $year)
                    ->where('creation_month', $month)
                    ->where('creation_day', $day)
                    ->where('creation_id', $incr)
                    ->when($excludeImportPostId, function ($q) use ($excludeImportPostId) {
                        return $q->where('id', '!=', $excludeImportPostId);
                    })
                    ->exists();

                if (! $statusExists && ! $importExists) {
                    return [
                        'incr' => $incr,
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                        'status_id' => $statusId,
                    ];
                }

                $incr++;
            }

            [$newYear, $newMonth, $newDay] = self::getNextValidDate($year, $month, $day);
            if (! $newYear) {
                return null;
            }

            return self::getUniqueCreationId($userId, $newYear, $newMonth, $newDay, $excludeImportPostId);
        }, 3);
    }

    public static function getPostCount($profileId, $refresh = false)
    {
        $key = self::CACHE_KEY.'totalPostCountByProfileId:'.$profileId;
        if ($refresh) {
            Cache::forget($key);
        }

        return intval(Cache::remember($key, 21600, function () use ($profileId) {
            return ImportPost::whereProfileId($profileId)->whereSkipMissingMedia(false)->count();
        }));
    }

    public static function getAttempts($profileId)
    {
        $key = self::CACHE_KEY.'attemptsByProfileId:'.$profileId;

        return intval(Cache::remember($key, 21600, function () use ($profileId) {
            return ImportPost::whereProfileId($profileId)
                ->whereSkipMissingMedia(false)
                ->get()
                ->groupBy(function ($item) {
                    return $item->created_at->format('Y-m-d');
                })
                ->count();
        }));
    }

    public static function clearAttempts($profileId)
    {
        $key = self::CACHE_KEY.'attemptsByProfileId:'.$profileId;

        return Cache::forget($key);
    }

    public static function getImportedFiles($profileId, $refresh = false)
    {
        $key = self::CACHE_KEY.'importedPostsByProfileId:'.$profileId;
        if ($refresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, 21600, function () use ($profileId) {
            return ImportPost::whereProfileId($profileId)
                ->get()
                ->filter(function ($ip) {
                    return StatusService::get($ip->status_id) == null;
                })
                ->map(function ($ip) {
                    return collect($ip->media)->map(function ($m) {
                        return $m['uri'];
                    });
                })->values()->flatten();
        });
    }

    public static function clearImportedFiles($profileId)
    {
        $key = self::CACHE_KEY.'importedPostsByProfileId:'.$profileId;

        return Cache::forget($key);
    }

    private static function getNextValidDate($year, $month, $day)
    {
        try {
            $fullYear = $year < 50 ? 2000 + $year : 1900 + $year;
            $date = \Carbon\Carbon::createFromDate($fullYear, $month, $day);
            $nextDay = $date->addDay();

            $nextYear2Digit = (int) $nextDay->format('y');

            return [
                $nextYear2Digit,
                $nextDay->month,
                $nextDay->day,
            ];
        } catch (\Exception $e) {
            return [null, null, null];
        }
    }
}
