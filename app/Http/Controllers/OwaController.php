<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Profile;

use App\Services\WebfingerService;

class OwaController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
	    \Log::info('Owa controller invoked');

	    $ret_response = [ 'success' => false ];

	    $auth_header = $request->header('Authorization');
	    \Log::debug('Auth header: ' . print_r($auth_header, true));

	    if (!str_starts_with(trim($auth_header), 'Signature')) {
		    \Log::debug('Missing Signature Authorization header, auth header = ' . print_r($auth_header, true));
		    return response()->json($ret_response);
	    }
	    $sigblock = self::parse_sigheader($auth_header);

	    if ($sigblock) {
		    \Log::debug('Signature block parsed OK');
		    $keyId = $sigblock['keyId'];
		    \Log::debug('KeyId = ' . print_r($keyId, true));
		    if ($keyId) {
			    // try to find user by keyId
			    $keyId = str_replace('acct:', '', $keyId);

                // strip all fragments and query parameters from the key_id
                $parsed = parse_url($keyId);
                if (str_starts_with($parsed['scheme'],'http')) {
                    unset($parsed['fragment']);
                    unset($parsed['query']);
                    $keyId = self::unparse_url($parsed);
                }
                else {
                    $keyId = str_replace('acct:', '', $keyId);
                }

			    \Log::debug('Looking for user with matching keyId ' . print_r($keyId, true));
			    $r = Profile::where('remote_url', $keyId)->first();
			    \Log::debug('Found profile: ' . print_r($r, true));
			    if (!$r) {
				    // discover for no pre-existing relationship or keyId does not match a remote URL
				    // The principals involved in the authentication may or may not have any pre-existing relationship
				    \Log::debug('Profile not found => discover resource');

				    $wf_account = WebfingerService::lookup($keyId);
				    \Log::debug('Webfinger returned ' . print_r($wf_account, true));

				    $r = Profile::where('remote_url', $keyId /*$wf_account['url']*/)->first();
				    \Log::debug('Found profile: ' . print_r($r, true));

				    if (!$r) {
					    \Log::debug('Not found - returning success = false');    
					    return response()->json($ret_response);
				    }
			    }
			    // verify the signature header
			    $verified = self::sig_verify($request, $sigblock, $keyId, $r->public_key);
			    \Log::debug('Verification output: ' . print_r($verified, true));
				if ($verified && $verified['header_signed'] && $verified['header_valid'] && ($verified['content_valid'] || (!$verified['content_signed']))) {
				    $ret_response['success'] = true;
				    $token = self::random_string(32);
				    DB::insert('INSERT into owa_verifications (token, remote_url, created_at) values (?,?,?)', [$token, $r->remote_url, now()]);
				    $result = '';
				    openssl_public_encrypt($token, $result, $r->public_key);
				    $ret_response['encrypted_token'] = self::base64url_encode($result);
				} else {
				    \Log::info('OWA fail for ' . $r->remote_url);
				}

		    }
	    }

	    return response()->json($ret_response);
    }

    // this should probably belong in a HTTP Signature helper
    /**
     * @brief
     *
     * @param string $header
     * @return array associate array with
     *   - \e string \b keyID
     *   - \e string \b algorithm
     *   - \e array  \b headers
     *   - \e string \b signature
     */
    private function parse_sigheader($header) {
        $ret = [];
        $matches = [];

        if (preg_match('/keyId="(.*?)"/ism', $header, $matches)) {
            $ret['keyId'] = $matches[1];
        }
        if (preg_match('/created=([0-9]*)/ism', $header, $matches)) {
            $ret['(created)'] = $matches[1];
        }
        if (preg_match('/expires=([0-9]*)/ism', $header, $matches)) {
            $ret['(expires)'] = $matches[1];
        }
        if (preg_match('/algorithm="(.*?)"/ism', $header, $matches)) {
            $ret['algorithm'] = $matches[1];
        }
        if (preg_match('/headers="(.*?)"/ism', $header, $matches)) {
            $ret['headers'] = explode(' ', $matches[1]);
        }
        if (preg_match('/signature="(.*?)"/ism', $header, $matches)) {
            $ret['signature'] = base64_decode(preg_replace('/\s+/', '', $matches[1]));
        }

        if (($ret['signature']) && ($ret['algorithm']) && (!$ret['headers'])) {
            $ret['headers'] = ['date'];
        }

        return $ret;
    }

    // See draft-cavage-http-signatures-10
    private function sig_verify($request, $sig_block = null, $signer = null, $public_key = '') {
        \Log::debug('Trying to verify the signature now...');
        $result = [
            'signer' => '',
            'portable_id' => '',
            'header_signed' => false,
            'header_valid' => false,
            'content_signed' => false,
            'content_valid' => false
        ];

        // we have the headers already in the request, but we need to add the pseudo-header (request-target) again
        $request->headers->add([ '(request-target)' => strtolower($request->method()) . ' /' . $request->path() ]);

        if (is_null($sig_block)) {
            $header_sig = $request->header('Signature');
            $header_auth = $request->header('Authorization');
            if ($header_sig) {
                $sig_block = self::parse_sigheader($header_sig);
            } elseif ($header_auth) {
                $sig_block = self::parse_sigheader($header_auth);
            }
            if (!$sig_block) {
                \Log::info('no signature provided.');
                return $result;
            }
        }

        $result['header_signed'] = true;

        $signed_headers = $sig_block['headers'];
        if (!$signed_headers) {
            $signed_headers = ['date'];
        }

        \Log::debug('Signed headers: ' . print_r($signed_headers, true));
        $signed_data = '';
        foreach ($signed_headers as $h) {
            $h_val = $request->header($h);
            \Log::debug('Checking header ' . print_r($h, true) . ' = ' . print_r($h_val, true));
            if ($h_val) {
                $signed_data .= $h . ': ' . $h_val . "\n";
            }
            if ($h === '(created)') {
                if ((!isset($sig_block['(created)'])) || (!intval($sig_block['(created)'])) || intval($sig_block['(created)']) > time()) {
                    \Log::debug('created in future');
                    return $result;
                }
                $signed_data .= '(created): ' . $sig_block['(created)'] . "\n";
            }
            if ($h === '(expires)') {
                if ((!isset($sig_block['(expires)'])) || (!intval($sig_block['(expires)'])) || intval($sig_block['(expires)']) < time()) {
                    \Log::debug('signature expired');
                    return $result;
                }
                $signed_data .= '(expires): ' . $sig_block['(expires)'] . "\n";
            }
            if ($h === 'date') {
                $d = new DateTime($headers[$h]);
                $d->setTimeZone(new DateTimeZone('UTC'));
                $dplus = datetime_convert('UTC', 'UTC', 'now + 1 day');
                $dminus = datetime_convert('UTC', 'UTC', 'now - 1 day');
                $c = $d->format('Y-m-d H:i:s');
                if ($c > $dplus || $c < $dminus) {
                    \Log::debug('bad time: ' . $c);
                    return $result;
                }
            }
        }
        $signed_data = rtrim($signed_data, "\n");
        \Log::debug('Signed data : ' . print_r($signed_data, true));

        $algorithm = null;

        if ($sig_block['algorithm'] === 'rsa-sha256') {
            $algorithm = 'sha256';
        }
        if ($sig_block['algorithm'] === 'rsa-sha512') {
            $algorithm = 'sha512';
        }

        if (!array_key_exists('keyId', $sig_block)) {
            return $result;
        }

        if (is_null($signer) && str_starts_with($sig_block['keyId'], 'http')) {
            $parsed = parse_url($sig_block['keyId']);
            unset($parsed['query']);
	        unset($parsed['fragment']);
            $signer = self::unparse_url($parsed);
        }
        \Log::debug('signer: ' . print_r($signer, true));

        $result['signer'] = $signer;
        $fkey = [];
            $fkey['public_key'] = $public_key;
            if (!($fkey && $fkey['public_key'])) {
                return $result;
            }

        \Log::debug('Going to crypto verify the signature now...');	
        $x = self::crypto_verify($signed_data, $sig_block['signature'], $fkey['public_key'], $algorithm);

        \Log::info('verified: ' . intval($x));

        if (!$x) {
            \Log::debug('Verify failed');
            return $result;
        }

        $result['header_valid'] = true;

        return $result;
    }

    private function crypto_verify($data, $sig, $key, $alg = 'sha256')
    {

        \Log::debug('Crypto verify: data = ' . print_r($data ,true) . ' / key = ' . print_r($key, true) . ' / alg = ' . print_r($alg, true));
        if (! $key) {
            return false;
        }
        // check for passed/provided $alg that is empty
        if (! $alg) {
            $alg = 'sha256';
        }

        try {
            $verify = openssl_verify($data, $sig, $key, $alg);
        } catch (Exception $e) {
            $verify = (-1);
        }

        if ($verify === (-1)) {
            while ($msg = openssl_error_string()) {
                \Log::debug('openssl_verify: ' . $msg);
            }
            \Log::debug('openssl_verify: key: ' . $key);
        }

        return (($verify > 0) ? true : false);
    }

    private function random_string($size = 64)
	{
	    // generate a bit of entropy and run it through the whirlpool
	    $s = hash('whirlpool', rand() . uniqid(rand(), true) . rand(), false);

	    return(substr($s, 0, $size));
	}

    private function base64url_encode($s, $strip_padding = true)
	{

	    $s = strtr(base64_encode($s), '+/', '-_');

	    if ($strip_padding) {
		$s = str_replace('=', '', $s);
	    }

	    return $s;
	}

    private function unparse_url($parsed_url)
    {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = ((isset($parsed_url['port']) && intval($parsed_url['port'])) ? ':' . intval($parsed_url['port']) : '');
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}