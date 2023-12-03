<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Profile;
use App\Services\FollowerService;

class AttemptRemoteAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
	\Log::debug('In handler for Remote Authentication Attempt');
	$zid = $request->query('zid');
	if (!isset($zid) || preg_match('/^.+@.+$/i', $zid) === false) {
		return $next($request);
	}
	// strip possible leading "@" to get typical user@domain handle
	if (strncmp($zid, '@', 1) !== 0) {
		$zid = '@' . $zid;
	}
	\Log::info('Remote user (zid) = ' . print_r($zid, true));
	$remoteDest = $request->fullUrlWithoutQuery(['zid']);
	\Log::debug('Remote destination = ' . $remoteDest);

    $path = parse_url($remoteDest, PHP_URL_PATH);
	if (str_starts_with($path, '/magic')) {
		\Log::info('Destination already contains the /magic endpoint - avoiding recursion - not going to attempt remote auth');
		return $next($request);
	}
	
    $domain = substr(strrchr($zid, '@'), 1);
	$remoteUrl = 'https://' . $domain . '/magic?f=&rev=1&owa=1&bdest=' . bin2hex($remoteDest);
	\Log::info('Remote url = ' . print_r($remoteUrl, true));

	return redirect()->away($remoteUrl);
    }
}