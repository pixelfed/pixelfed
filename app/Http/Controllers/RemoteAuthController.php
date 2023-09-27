<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\Account\RemoteAuthService;
use App\Models\RemoteAuth;
use App\Profile;
use App\Instance;
use App\User;
use Purify;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use App\Util\Lexer\RestrictedNames;
use App\Services\EmailService;
use App\Services\MediaStorageService;
use App\Util\ActivityPub\Helpers;
use InvalidArgumentException;

class RemoteAuthController extends Controller
{
    public function start(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        if($request->user()) {
            return redirect('/');
        }
        return view('auth.remote.start');
    }

    public function startRedirect(Request $request)
    {
        return redirect('/login');
    }

    public function getAuthDomains(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);

        if(config('remote-auth.mastodon.domains.only_custom')) {
            $res = config('remote-auth.mastodon.domains.custom');
            if(!$res || !strlen($res)) {
                return [];
            }
            $res = explode(',', $res);
            return response()->json($res);
        }

        if( config('remote-auth.mastodon.domains.custom') &&
            !config('remote-auth.mastodon.domains.only_default') &&
            strlen(config('remote-auth.mastodon.domains.custom')) > 3 &&
            strpos(config('remote-auth.mastodon.domains.custom'), '.') > -1
        ) {
            $res = config('remote-auth.mastodon.domains.custom');
            if(!$res || !strlen($res)) {
                return [];
            }
            $res = explode(',', $res);
            return response()->json($res);
        }

        $res = config('remote-auth.mastodon.domains.default');
        $res = explode(',', $res);

        return response()->json($res);
    }

    public function redirect(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);

        $this->validate($request, ['domain' => 'required']);

        $domain = $request->input('domain');

        if(str_starts_with(strtolower($domain), 'http')) {
            $res = [
                'domain' => $domain,
                'ready' => false,
                'action' => 'incompatible_domain'
            ];
            return response()->json($res);
        }

        $validateInstance = Helpers::validateUrl('https://' . $domain . '/?block-check=' . time());

        if(!$validateInstance) {
             $res = [
                'domain' => $domain,
                'ready' => false,
                'action' => 'blocked_domain'
            ];
            return response()->json($res);
        }

        $compatible = RemoteAuthService::isDomainCompatible($domain);

        if(!$compatible) {
            $res = [
                'domain' => $domain,
                'ready' => false,
                'action' => 'incompatible_domain'
            ];
            return response()->json($res);
        }

        if(config('remote-auth.mastodon.domains.only_default')) {
            $defaultDomains = explode(',', config('remote-auth.mastodon.domains.default'));
            if(!in_array($domain, $defaultDomains)) {
                $res = [
                    'domain' => $domain,
                    'ready' => false,
                    'action' => 'incompatible_domain'
                ];
                return response()->json($res);
            }
        }

        if(config('remote-auth.mastodon.domains.only_custom') && config('remote-auth.mastodon.domains.custom')) {
            $customDomains = explode(',', config('remote-auth.mastodon.domains.custom'));
            if(!in_array($domain, $customDomains)) {
                $res = [
                    'domain' => $domain,
                    'ready' => false,
                    'action' => 'incompatible_domain'
                ];
                return response()->json($res);
            }
        }

        $client = RemoteAuthService::getMastodonClient($domain);

        abort_unless($client, 422, 'Invalid mastodon client');

        $request->session()->put('state', $state = Str::random(40));
        $request->session()->put('oauth_domain', $domain);

        $query = http_build_query([
            'client_id' => $client->client_id,
            'redirect_uri' => $client->redirect_uri,
            'response_type' => 'code',
            'scope' => 'read',
            'state' => $state,
        ]);

        $request->session()->put('oauth_redirect_to', 'https://' . $domain . '/oauth/authorize?' . $query);

        $dsh = Str::random(17);
        $res = [
            'domain' => $domain,
            'ready' => true,
            'dsh' => $dsh
        ];

        return response()->json($res);
    }

    public function preflight(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);

        if(!$request->filled('d') || !$request->filled('dsh') || !$request->session()->exists('oauth_redirect_to')) {
            return redirect('/login');
        }

        return redirect()->away($request->session()->pull('oauth_redirect_to'));
    }

    public function handleCallback(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);

        $domain = $request->session()->get('oauth_domain');

        if($request->filled('code')) {
            $code = $request->input('code');
            $state = $request->session()->pull('state');

            throw_unless(
                strlen($state) > 0 && $state === $request->state,
                InvalidArgumentException::class,
                'Invalid state value.'
            );

            $res = RemoteAuthService::getToken($domain, $code);

            if(!$res || !isset($res['access_token'])) {
                $request->session()->regenerate();
                return redirect('/login');
            }

            $request->session()->put('oauth_remote_session_token', $res['access_token']);
            return redirect('/auth/mastodon/getting-started');
        }

        return redirect('/login');
    }

    public function onboarding(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        if($request->user()) {
            return redirect('/');
        }
        return view('auth.remote.onboarding');
    }

    public function sessionCheck(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        abort_if($request->user(), 403);
        abort_unless($request->session()->exists('oauth_domain'), 403);
        abort_unless($request->session()->exists('oauth_remote_session_token'), 403);

        $domain = $request->session()->get('oauth_domain');
        $token = $request->session()->get('oauth_remote_session_token');

        $res = RemoteAuthService::getVerifyCredentials($domain, $token);

        abort_if(!$res || !isset($res['acct']), 403, 'Invalid credentials');

        $webfinger = strtolower('@' . $res['acct'] . '@' . $domain);
        $request->session()->put('oauth_masto_webfinger', $webfinger);

        if(config('remote-auth.mastodon.max_uses.enabled')) {
            $limit = config('remote-auth.mastodon.max_uses.limit');
            $uses = RemoteAuthService::lookupWebfingerUses($webfinger);
            if($uses >= $limit) {
                return response()->json([
                    'code' => 200,
                    'msg' => 'Success!',
                    'action' => 'max_uses_reached'
                ]);
            }
        }

        $exists = RemoteAuth::whereDomain($domain)->where('webfinger', $webfinger)->whereNotNull('user_id')->first();
        if($exists && $exists->user_id) {
            return response()->json([
                'code' => 200,
                'msg' => 'Success!',
                'action' => 'redirect_existing_user'
            ]);
        }

        return response()->json([
            'code' => 200,
            'msg' => 'Success!',
            'action' => 'onboard'
        ]);
    }

    public function sessionGetMastodonData(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        abort_if($request->user(), 403);
        abort_unless($request->session()->exists('oauth_domain'), 403);
        abort_unless($request->session()->exists('oauth_remote_session_token'), 403);

        $domain = $request->session()->get('oauth_domain');
        $token = $request->session()->get('oauth_remote_session_token');

        $res = RemoteAuthService::getVerifyCredentials($domain, $token);
        $res['_webfinger'] = strtolower('@' . $res['acct'] . '@' . $domain);
        $res['_domain'] = strtolower($domain);
        $request->session()->put('oauth_remasto_id', $res['id']);

        $ra = RemoteAuth::updateOrCreate([
            'domain' => $domain,
            'webfinger' => $res['_webfinger'],
        ], [
            'software' => 'mastodon',
            'ip_address' => $request->ip(),
            'bearer_token' => $token,
            'verify_credentials' => $res,
            'last_verify_credentials_at' => now(),
            'last_successful_login_at' => now()
        ]);

        $request->session()->put('oauth_masto_raid', $ra->id);

        return response()->json($res);
    }

    public function sessionValidateUsername(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        abort_if($request->user(), 403);
        abort_unless($request->session()->exists('oauth_domain'), 403);
        abort_unless($request->session()->exists('oauth_remote_session_token'), 403);

        $this->validate($request, [
            'username' => [
                'required',
                'min:2',
                'max:15',
                function ($attribute, $value, $fail) {
                    $dash = substr_count($value, '-');
                    $underscore = substr_count($value, '_');
                    $period = substr_count($value, '.');

                    if(ends_with($value, ['.php', '.js', '.css'])) {
                        return $fail('Username is invalid.');
                    }

                    if(($dash + $underscore + $period) > 1) {
                        return $fail('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');
                    }

                    if (!ctype_alnum($value[0])) {
                        return $fail('Username is invalid. Must start with a letter or number.');
                    }

                    if (!ctype_alnum($value[strlen($value) - 1])) {
                        return $fail('Username is invalid. Must end with a letter or number.');
                    }

                    $val = str_replace(['_', '.', '-'], '', $value);
                    if(!ctype_alnum($val)) {
                        return $fail('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');
                    }

                    $restricted = RestrictedNames::get();
                    if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
                        return $fail('Username cannot be used.');
                    }
                }
            ]
        ]);
        $username = strtolower($request->input('username'));

        $exists = User::where('username', $username)->exists();

        return response()->json([
            'code' => 200,
            'username' => $username,
            'exists' => $exists
        ]);
    }

    public function sessionValidateEmail(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        abort_if($request->user(), 403);
        abort_unless($request->session()->exists('oauth_domain'), 403);
        abort_unless($request->session()->exists('oauth_remote_session_token'), 403);

        $this->validate($request, [
            'email' => [
                'required',
                'email:strict,filter_unicode,dns,spoof',
            ]
        ]);

        $email = $request->input('email');
        $banned = EmailService::isBanned($email);
        $exists = User::where('email', $email)->exists();

        return response()->json([
            'code' => 200,
            'email' => $email,
            'exists' => $exists,
            'banned' => $banned
        ]);
    }

    public function sessionGetMastodonFollowers(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        abort_unless($request->session()->exists('oauth_domain'), 403);
        abort_unless($request->session()->exists('oauth_remote_session_token'), 403);
        abort_unless($request->session()->exists('oauth_remasto_id'), 403);

        $domain = $request->session()->get('oauth_domain');
        $token = $request->session()->get('oauth_remote_session_token');
        $id = $request->session()->get('oauth_remasto_id');

        $res = RemoteAuthService::getFollowing($domain, $token, $id);

        if(!$res) {
            return response()->json([
                'code' => 200,
                'following' => []
            ]);
        }

        $res = collect($res)->filter(fn($acct) => Helpers::validateUrl($acct['url']))->values()->toArray();

        return response()->json([
            'code' => 200,
            'following' => $res
        ]);
    }

    public function handleSubmit(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        abort_unless($request->session()->exists('oauth_domain'), 403);
        abort_unless($request->session()->exists('oauth_remote_session_token'), 403);
        abort_unless($request->session()->exists('oauth_remasto_id'), 403);
        abort_unless($request->session()->exists('oauth_masto_webfinger'), 403);
        abort_unless($request->session()->exists('oauth_masto_raid'), 403);

        $this->validate($request, [
            'email' => 'required|email:strict,filter_unicode,dns,spoof',
            'username' => [
                'required',
                'min:2',
                'max:15',
                'unique:users,username',
                function ($attribute, $value, $fail) {
                    $dash = substr_count($value, '-');
                    $underscore = substr_count($value, '_');
                    $period = substr_count($value, '.');

                    if(ends_with($value, ['.php', '.js', '.css'])) {
                        return $fail('Username is invalid.');
                    }

                    if(($dash + $underscore + $period) > 1) {
                        return $fail('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');
                    }

                    if (!ctype_alnum($value[0])) {
                        return $fail('Username is invalid. Must start with a letter or number.');
                    }

                    if (!ctype_alnum($value[strlen($value) - 1])) {
                        return $fail('Username is invalid. Must end with a letter or number.');
                    }

                    $val = str_replace(['_', '.', '-'], '', $value);
                    if(!ctype_alnum($val)) {
                        return $fail('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');
                    }

                    $restricted = RestrictedNames::get();
                    if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
                        return $fail('Username cannot be used.');
                    }
                }
            ],
            'password' => 'required|string|min:8|confirmed',
            'name' => 'nullable|max:30'
        ]);

        $email = $request->input('email');
        $username = $request->input('username');
        $password = $request->input('password');
        $name = $request->input('name');

        $user = $this->createUser([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'email' => $email
        ]);

        $raid = $request->session()->pull('oauth_masto_raid');
        $webfinger = $request->session()->pull('oauth_masto_webfinger');
        $token = $user->createToken('Onboarding')->accessToken;

        $ra = RemoteAuth::where('id', $raid)->where('webfinger', $webfinger)->firstOrFail();
        $ra->user_id = $user->id;
        $ra->save();

        return [
            'code' => 200,
            'msg' => 'Success',
            'token' => $token
        ];
    }

    public function storeBio(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        abort_unless($request->user(), 404);
        abort_unless($request->session()->exists('oauth_domain'), 403);
        abort_unless($request->session()->exists('oauth_remote_session_token'), 403);
        abort_unless($request->session()->exists('oauth_remasto_id'), 403);

        $this->validate($request, [
            'bio' => 'required|nullable|max:500',
        ]);

        $profile = $request->user()->profile;
        $profile->bio = Purify::clean($request->input('bio'));
        $profile->save();

        return [200];
    }

    public function accountToId(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        abort_if($request->user(), 404);
        abort_unless($request->session()->exists('oauth_domain'), 403);
        abort_unless($request->session()->exists('oauth_remote_session_token'), 403);
        abort_unless($request->session()->exists('oauth_remasto_id'), 403);

        $this->validate($request, [
            'account' => 'required|url'
        ]);

        $account = $request->input('account');
        abort_unless(substr(strtolower($account), 0, 8) === 'https://', 404);

        $host = strtolower(config('pixelfed.domain.app'));
        $domain = strtolower(parse_url($account, PHP_URL_HOST));

        if($domain == $host) {
            $username = Str::of($account)->explode('/')->last();
            $user = User::where('username', $username)->first();
            if($user) {
                return ['id' => (string) $user->profile_id];
            } else {
                return [];
            }
        } else {
            try {
                $profile = Helpers::profileFetch($account);
                if($profile) {
                    return ['id' => (string) $profile->id];
                } else {
                    return [];
                }
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                return;
            } catch (Exception $e) {
                return [];
            }
        }
    }

    public function storeAvatar(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        abort_unless($request->user(), 404);
        $this->validate($request, [
            'avatar_url' => 'required|active_url',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        abort_if(!$profile->avatar, 404, 'Missing avatar');

        $avatar = $profile->avatar;
        $avatar->remote_url = $request->input('avatar_url');
        $avatar->save();

        MediaStorageService::avatar($avatar, config_cache('pixelfed.cloud_storage') == false);

        return [200];
    }

    public function finishUp(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        abort_unless($request->user(), 404);

        $currentWebfinger = '@' . $request->user()->username . '@' . config('pixelfed.domain.app');
        $ra = RemoteAuth::where('user_id', $request->user()->id)->firstOrFail();
        RemoteAuthService::submitToBeagle(
            $ra->webfinger,
            $ra->verify_credentials['url'],
            $currentWebfinger,
            $request->user()->url()
        );

        return [200];
    }

    public function handleLogin(Request $request)
    {
        abort_unless((
            config_cache('pixelfed.open_registration') &&
            config('remote-auth.mastodon.enabled')
        ) || (
            config('remote-auth.mastodon.ignore_closed_state') &&
            config('remote-auth.mastodon.enabled')
        ), 404);
        abort_if($request->user(), 404);
        abort_unless($request->session()->exists('oauth_domain'), 403);
        abort_unless($request->session()->exists('oauth_remote_session_token'), 403);
        abort_unless($request->session()->exists('oauth_masto_webfinger'), 403);

        $domain = $request->session()->get('oauth_domain');
        $wf = $request->session()->get('oauth_masto_webfinger');

        $ra = RemoteAuth::where('webfinger', $wf)->where('domain', $domain)->whereNotNull('user_id')->firstOrFail();

        $user = User::findOrFail($ra->user_id);
        abort_if($user->is_admin || $user->status != null, 422, 'Invalid auth action');
        Auth::loginUsingId($ra->user_id);
        return [200];
    }

    protected function createUser($data)
    {
        event(new Registered($user = User::create([
            'name'     => Purify::clean($data['name']),
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => config('remote-auth.mastodon.contraints.skip_email_verification') ? now() : null,
            'app_register_ip' => request()->ip(),
            'register_source' => 'mastodon'
        ])));

        $this->guarder()->login($user);

        return $user;
    }

    protected function guarder()
    {
        return Auth::guard();
    }
}
