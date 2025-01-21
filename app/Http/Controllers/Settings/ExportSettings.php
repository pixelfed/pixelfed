<?php

namespace App\Http\Controllers\Settings;

use App\Status;
use App\Transformer\ActivityPub\ProfileTransformer;
use App\Transformer\Api\StatusTransformer as StatusApiTransformer;
use App\UserFilter;
use Auth;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use Storage;

trait ExportSettings
{
    private const CHUNK_SIZE = 1000;

    private const STORAGE_BASE = 'user_exports';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dataExport()
    {
        return view('settings.dataexport');
    }

    public function exportAccount()
    {
        $profile = Auth::user()->profile;
        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($profile, new ProfileTransformer);

        $data = $fractal->createData($resource)->toArray();

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }, 'account.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function exportFollowing()
    {
        $profile = Auth::user()->profile;
        $userId = Auth::id();

        $userExportPath = 'user_exports/'.$userId;
        $filename = 'pixelfed-following.json';
        $tempPath = $userExportPath.'/'.$filename;

        if (! Storage::exists($userExportPath)) {
            Storage::makeDirectory($userExportPath);
        }

        try {
            Storage::put($tempPath, '[');

            $profile->following()
                ->chunk(1000, function ($following) use ($tempPath) {
                    $urls = $following->map(function ($follow) {
                        return $follow->url();
                    });

                    $json = json_encode($urls,
                        JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_UNESCAPED_UNICODE
                    );

                    $json = trim($json, '[]');
                    if (Storage::size($tempPath) > 1) {
                        $json = ','.$json;
                    }

                    Storage::append($tempPath, $json);
                });

            Storage::append($tempPath, ']');

            return response()->stream(
                function () use ($tempPath) {
                    $handle = fopen(Storage::path($tempPath), 'rb');
                    while (! feof($handle)) {
                        echo fread($handle, 8192);
                        flush();
                    }
                    fclose($handle);

                    Storage::delete($tempPath);
                },
                200,
                [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => 'attachment; filename="pixelfed-following.json"',
                ]
            );

        } catch (\Exception $e) {
            if (Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
            throw $e;
        }
    }

    public function exportFollowers()
    {
        $profile = Auth::user()->profile;
        $userId = Auth::id();

        $userExportPath = 'user_exports/'.$userId;
        $filename = 'pixelfed-followers.json';
        $tempPath = $userExportPath.'/'.$filename;

        if (! Storage::exists($userExportPath)) {
            Storage::makeDirectory($userExportPath);
        }

        try {
            Storage::put($tempPath, '[');

            $profile->followers()
                ->chunk(1000, function ($followers) use ($tempPath) {
                    $urls = $followers->map(function ($follower) {
                        return $follower->url();
                    });

                    $json = json_encode($urls,
                        JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_UNESCAPED_UNICODE
                    );

                    $json = trim($json, '[]');
                    if (Storage::size($tempPath) > 1) {
                        $json = ','.$json;
                    }

                    Storage::append($tempPath, $json);
                });

            Storage::append($tempPath, ']');

            return response()->stream(
                function () use ($tempPath) {
                    $handle = fopen(Storage::path($tempPath), 'rb');
                    while (! feof($handle)) {
                        echo fread($handle, 8192);
                        flush();
                    }
                    fclose($handle);

                    Storage::delete($tempPath);
                },
                200,
                [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => 'attachment; filename="pixelfed-followers.json"',
                ]
            );

        } catch (\Exception $e) {
            if (Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
            throw $e;
        }
    }

    public function exportMuteBlockList()
    {
        $profile = Auth::user()->profile;
        $exists = UserFilter::select('id')
            ->whereUserId($profile->id)
            ->exists();
        if (! $exists) {
            return redirect()->back();
        }
        $data = Cache::remember('account:export:profile:muteblocklist:'.Auth::user()->profile->id, now()->addMinutes(60), function () use ($profile) {
            return json_encode([
                'muted' => $profile->mutedProfileUrls(),
                'blocked' => $profile->blockedProfileUrls(),
            ], JSON_PRETTY_PRINT);
        });

        return response()->streamDownload(function () use ($data) {
            echo $data;
        }, 'muted-and-blocked-accounts.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function exportStatuses(Request $request)
    {
        $profile = Auth::user()->profile;
        $userId = Auth::id();
        $userExportPath = self::STORAGE_BASE.'/'.$userId;
        $filename = 'pixelfed-statuses.json';
        $tempPath = $userExportPath.'/'.$filename;

        if (! Storage::exists($userExportPath)) {
            Storage::makeDirectory($userExportPath);
        }

        Storage::put($tempPath, '[');
        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);

        try {
            Status::whereProfileId($profile->id)
                ->chunk(self::CHUNK_SIZE, function ($statuses) use ($fractal, $tempPath) {
                    $resource = new Fractal\Resource\Collection($statuses, new StatusApiTransformer);
                    $data = $fractal->createData($resource)->toArray();

                    $json = json_encode($data,
                        JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_UNESCAPED_UNICODE
                    );

                    $json = trim($json, '[]');
                    if (Storage::size($tempPath) > 1) {
                        $json = ','.$json;
                    }

                    Storage::append($tempPath, $json);
                });

            Storage::append($tempPath, ']');

            return response()->stream(
                function () use ($tempPath) {
                    $handle = fopen(Storage::path($tempPath), 'rb');
                    while (! feof($handle)) {
                        echo fread($handle, 8192);
                        flush();
                    }
                    fclose($handle);
                    Storage::delete($tempPath);
                },
                200,
                [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => 'attachment; filename="pixelfed-statuses.json"',
                ]
            );

        } catch (\Exception $e) {
            if (Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
            throw $e;
        }
    }
}
