<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Profile;
use App\User;

class ValidateRemoteAuthentication
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('In handler for Remote Authentication Validation based on token');
		$owt = $request->query('owt');
        if (!isset($owt)) {
                return $next($request);
		}

		// https://codeberg.org/streams/streams/src/branch/dev/spec/OpenWebAuth/Home.md
		//  The OpenWebAuth token service MUST discard tokens after first use and SHOULD discard unused tokens within a few minutes of generation.
		\Log::debug('owt token provided');
		// purge tokens older than 3 minutes
		self::purge(3);
		// find out which user to log in based on owt token
		$owtMatchDb = DB::table('owa_verifications')->where('token', $owt);
		$r = $owtMatchDb->first();
		if (!$r) {
			\Log::info('Token not found');
			return next($request);
		}
		$owtMatchDb->delete();
		
		\Log::debug('Found this as token in our DB: ' . print_r($r, true));
		$remote_profile = $r->remote_url;	
		
		\Log::debug('Trying to log in in as ' . $remote_profile);
		$p = Profile::where('remote_url', $remote_profile)->first();
		if (!$p) {
			\Log::info('Failed to locate the profile in DB');
			return next($request);
		}

		$username = $p->username;
		\Log::info('Trying to log in username ' . $username);

		$user = User::whereUsername($username)->first();
		if (!$user) {
			\Log::info('User not found as an OpenWebAuth visitor - adding now');
			$user = User::create([ 
				'username' => $username,
				'name' => $p->name,
				'email' => $username,  // not an email address but a unique id must be filled as email in the DB
				'password' => substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 32), // password cannot be NULL
			]);

			// these attributes are not mass assignable so saving them here separately
			$user->register_source = 'owa';
			$user->profile_id = $p->id;
			$user->save();

			$p->user_id = $user->id;
			$p->save();
		} else {
			if ($user->register_source === 'owa') {
				\Log::info('User found as a visitor');
			} else {
				\Log::info('User found with register source = ' . $user->register_source);
			}
		}

		\Log::info('Logging user <' . $user->name . '> in now');
		Auth::login($user);
		\Log::info('user logged in');

		// avoid session fixation attack by regenerating a new session ID
		$request->session()->regenerate();

		return $next($request);
    }

    private function purge($minutes) {
	    $purged = DB::table('owa_verifications')->where('created_at', '<', now()->subMinutes($minutes)->toDateTimeString())->delete();
	    \Log::debug('Purged ' . print_r($purged, true) . ' token(s)');
    }
}
