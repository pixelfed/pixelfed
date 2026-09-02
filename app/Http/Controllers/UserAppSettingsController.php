<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\StoreUserAppSettings;
use App\Http\Resources\UserAppSettingsResource;
use App\Models\UserAppSettings;
use App\Services\Account\AccountAppSettingsService;
use Illuminate\Http\Request;

class UserAppSettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    public function get(Request $request)
    {
        abort_if(! $request->user(), 403);

        $settings = UserAppSettings::whereUserId($request->user()->id)->first();

        if (! $settings) {
            return [
                'id' => (string) $request->user()->profile_id,
                'username' => $request->user()->username,
                'updated_at' => null,
                'common' => AccountAppSettingsService::default(),
            ];
        }

        return new UserAppSettingsResource($settings);
    }

    public function store(StoreUserAppSettings $request)
    {
        $res = UserAppSettings::updateOrCreate([
            'user_id' => $request->user()->id,
        ], [
            'profile_id' => $request->user()->profile_id,
            'common' => $request->common,
        ]
        );

        return new UserAppSettingsResource($res);
    }
}
