<?php

namespace App\Http\Controllers;

use App\Models\UserOidcMapping;
use App\Rules\EmailNotBanned;
use App\Rules\PixelfedUsername;
use App\Services\EmailService;
use App\Services\UserOidcService;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Purify;

class RemoteOidcController extends Controller
{
    protected $fractal;

    public function start(UserOidcService $provider, Request $request)
    {
        abort_unless((bool) config('remote-auth.oidc.enabled'), 404);
        if ($request->user()) {
            return redirect('/');
        }

        $url = $provider->getAuthorizationUrl([
            'scope' => $provider->getDefaultScopes(),
        ]);

        $request->session()->put('oauth2state', $provider->getState());

        return redirect($url);
    }

    public function handleCallback(UserOidcService $provider, Request $request)
    {
        abort_unless((bool) config('remote-auth.oidc.enabled'), 404);

        if ($request->user()) {
            return redirect('/');
        }

        abort_unless($request->input('state'), 400);
        abort_unless($request->input('code'), 400);

        abort_unless(hash_equals($request->session()->pull('oauth2state'), $request->input('state')), 400, 'invalid state');

        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $request->get('code'),
        ]);

        $userInfo = $provider->getResourceOwner($accessToken);
        $userInfoId = $userInfo->getId();
        $userInfoData = $userInfo->toArray();

        $mappedUser = UserOidcMapping::where('oidc_id', $userInfoId)->first();
        if ($mappedUser) {
            $this->guarder()->login($mappedUser->user);

            return redirect('/');
        }

        abort_if(EmailService::isBanned($userInfoData['email']), 400, 'Banned email.');

        $user = $this->createUser([
            'username' => $userInfoData[config('remote-auth.oidc.field_username')],
            'name' => $userInfoData['name'] ?? $userInfoData['display_name'] ?? $userInfoData[config('remote-auth.oidc.field_username')] ?? null,
            'email' => $userInfoData['email'],
        ]);

        UserOidcMapping::create([
            'user_id' => $user->id,
            'oidc_id' => $userInfoId,
        ]);

        return redirect('/');
    }

    protected function createUser($data)
    {
        $this->validate(new Request($data), [
            'email' => [
                'required',
                'string',
                'email:strict,filter_unicode,dns,spoof',
                'max:255',
                'unique:users',
                new EmailNotBanned,
            ],
            'username' => [
                'required',
                'min:2',
                'max:30',
                'unique:users,username',
                new PixelfedUsername,
            ],
            'name' => 'nullable|max:30',
        ]);

        event(new Registered($user = User::create([
            'name' => Purify::clean($data['name']),
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make(Str::password()),
            'email_verified_at' => now(),
            'app_register_ip' => request()->ip(),
            'register_source' => 'oidc',
        ])));

        $this->guarder()->login($user);

        return $user;
    }

    protected function guarder()
    {
        return Auth::guard();
    }
}
