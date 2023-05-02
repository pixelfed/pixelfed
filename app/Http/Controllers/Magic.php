<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class Magic extends Controller
{

    /**
     * @brief Generates a random string.
     *
     * @param number $size
     *
     * @return string
     */
    private function random_string($size = 64) {
        // generate a bit of entropy and run it through the whirlpool
        $s = hash('whirlpool', (string) rand() . uniqid(rand(),true) . (string) rand(), false);

        return(substr($s, 0, $size));
    }

    private function generate_digest_header($body, $alg = 'sha256') {

        $digest = base64_encode(hash($alg, $body, true));
        switch ($alg) {
        case 'sha512':
            return 'SHA-512=' . $digest;
        case 'sha256':
        default:
        return 'SHA-256=' . $digest;
        break;
        }
    }

    // From Lib\Crypto.php
    private function crypto_sign($data, $key, $alg = 'sha256') {

        if (!$key) {
            \Log::info('Magic:crypto_sign: key is empty! FAIL');
            return false;
        }

        $sig = '';
        openssl_sign($data, $sig, $key, $alg);
        return $sig;

    }

    private function sign($head, $prvkey, $alg = 'sha256') {

        $ret = [];

        $headers = '';
        $fields  = '';

        if ($head) {
            foreach ($head as $k => $v) {
                $k = strtolower($k);
                $v = (($v) ? trim($v) : '');

                $headers .= $k . ': ' . $v . "\n";
                if ($fields)
                    $fields .= ' ';

                $fields .= $k;
            }
            // strip the trailing linefeed
            $headers = rtrim($headers, "\n");
        }

        $sig = base64_encode(self::crypto_sign($headers, $prvkey, $alg));

        $ret['headers']   = $fields;
        $ret['signature'] = $sig;

        return $ret;
    }

    // From \Lib\Crypto.php
    private function crypto_encapsulate($data, $pubkey, $alg) {

        if (!($alg && $pubkey)) {
            return $data;
        }

        $alg_base = $alg;
        $padding  = OPENSSL_PKCS1_PADDING;

        $exts = explode('.', $alg);
        if (count($exts) > 1) {
            switch ($exts[1]) {
            case 'oaep':
                $padding = OPENSSL_PKCS1_OAEP_PADDING;
                break;
            default:
                break;
            }
            $alg_base = $exts[0];
        }

        $method = null;

        foreach (self::$openssl_algorithms as $ossl) {
            if ($ossl[0] === $alg_base) {
                $method = $ossl;
                break;
            }
        }

        if ($method) {
            $result = ['encrypted' => true];

            $key = openssl_random_pseudo_bytes(256);
            $iv  = openssl_random_pseudo_bytes(256);

            $key1 = substr($key, 0, $method[2]);
            $iv1  = substr($iv, 0, $method[3]);

            $result['data'] = base64url_encode(openssl_encrypt($data, $method[1], $key1, OPENSSL_RAW_DATA, $iv1), true);

            openssl_public_encrypt($key, $k, $pubkey, $padding);
            openssl_public_encrypt($iv, $i, $pubkey, $padding);

            $result['alg'] = $alg;
            $result['key'] = base64url_encode($k, true);
            $result['iv']  = base64url_encode($i, true);
            return $result;

        }
        else {
            \Log::info('crypto_encapsulate FAIL: Method not supported!');
            return false;
        }
    }

    private function create_sig($head, $prvkey, $keyid = EMPTY_STR, $auth = false, $alg = 'sha256', $encryption = false) {

        $return_headers = [];

        if ($alg === 'sha256') {
            $algorithm = 'rsa-sha256';
        }
        if ($alg === 'sha512') {
            $algorithm = 'rsa-sha512';
        }

        $x = self::sign($head, $prvkey, $alg);

        $headerval = 'keyId="' . $keyid . '",algorithm="' . $algorithm . '",headers="' . $x['headers'] . '",signature="' . $x['signature'] . '"';

        if ($encryption) {
            $x = self::crypto_encapsulate($headerval, $encryption['key'], $encryption['algorithm']);
            if (is_array($x)) {
                $headerval = 'iv="' . $x['iv'] . '",key="' . $x['key'] . '",alg="' . $x['alg'] . '",data="' . $x['data'] . '"';
            }
        }

        if ($head) {
            foreach ($head as $k => $v) {
                // strip the request-target virtual header from the output headers
                if ($k === '(request-target)') {
                    continue;
                }
                $return_headers[$k] = $v;
            }
        }
        if ($auth) {
            $return_headers['Authorization'] = 'Signature ' . $headerval;
        } else {
            $return_headers['Signature'] = $headerval;
        }

        return $return_headers;
    }

    private function base64url_decode($s, $strict = false) {
        if(is_array($s)) {
            \Log::info('base64url_decode: illegal input: ' . print_r(debug_backtrace(), true));
            return $s;
        }
        return base64_decode(strtr($s,'-_','+/'), $strict);
    }

    // From include/network.php
    /*
     *
     * Takes the output of parse_url and builds a URL from it
     *
     */

    private function unparse_url($parsed_url) {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    // From include/zid.php
    /**
     * @brief Remove parameters from query string.
     *
     * @param string $s
     *   The query string
     * @param array $p
     *   $p array of parameters to remove
     * @return string
     */
    private function drop_query_params($s, $p) {
        $parsed = parse_url($s);
        $query = '';
        $query_args = null;

        if(isset($parsed['query'])) {
            parse_str($parsed['query'], $query_args);
        }

        if(is_array($query_args)) {
            foreach($query_args as $k => $v) {
                if(in_array($k, $p))
                    continue;
                $query .= (($query) ? '&' : '') . urlencode($k) . '=' . urlencode($v);
            }
        }

        unset($parsed['query']);

        if($query) {
            $parsed['query'] = $query;
        }

        return self::unparse_url($parsed);
    }

    private function strip_query_param($s, $param) {
        return self::drop_query_params($s, [$param]);
    }


    private function strip_zids($s) {
        return self::drop_query_params($s, ['zid']);
    }


    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $dest = $request->get('dest');
        $bdest = $request->get('bdest');
        $owa = $request->get('owa');

        // bdest is preferred over dest as it is hex-encoded and can survive url rewrite and argument parsing
        if ($bdest) {
            $dest = hex2bin($bdest);
        }
        \Log::info('Magic: Dest = ' . print_r($dest, true));

        $parsed = parse_url($dest);
        if (!$parsed['path']) {
            \Log::info('Could not parse!');
            abort(404);
        }
        $basepath = $parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
        $owapath = $basepath . '/owa';

        $user = Auth::user();
        if ($user) {
            $profile = $user->profile;
            if ($profile) {
                if ($owa) { 
                    \Log::info('Magic: OpenWebAuth detected!');

                    $dest = self::strip_zids($dest);
                    $dest = self::strip_query_param($dest,'f');

                    // We now post to the OWA endpoint. This improves security by providing a signed digest
                    $data = json_encode([ 'OpenWebAuth' => self::random_string() ]);

                    $headers = [];
                    $headers['Accept'] = 'application/json';
                    $headers['Content-Type'] = 'application/json';
                    $headers['X-Open-Web-Auth'] = self::random_string();
                    $headers['Digest'] = self::generate_digest_header($data);
                    $headers['Host'] = $parsed['host'];
                    $headers['(request-target)'] = 'post ' . '/owa';
                    \Log::info('raw headers = ' . print_r($headers, true));

                    $prvkey = $profile->private_key;
                    $url = $user->url();
                    \Log::info('Magic: user URL: ' . print_r($url, true));

                    $headers = self::create_sig($headers, $prvkey, $url, true, 'sha512');
                    \Log::info('Magic: Sending out request to ' . print_r($owapath, true));
                    $client = new Client();
                    $res = $client->post($owapath, 
                        [ 
                            'body' => $data,
                            'headers' => $headers
                        ]);

                    $statusCode = $res->getStatusCode();
                    \Log::info('Magic: Status code = ' . print_r($statusCode, true));
                    if ($statusCode == 200) {
                        $body = $res->getBody();
                        $j = json_decode($body,true);
                        if ($j['success'] && $j['encrypted_token']) {
                            \Log::info('Magic: Encrypted token found');
                            // decrypt the token using our private key
                            $token = '';
                            openssl_private_decrypt(self::base64url_decode($j['encrypted_token']), $token, $prvkey);
                            $x = strpbrk($dest,'?&');
                            // redirect using the encrypted token which will be exchanged for an authenticated session
                            $args = (($x) ? '&owt=' . $token : '?f=&owt=' . $token);

                            \Log::info('Magic: Success - Redirecting to ' . print_r($dest . $args, true));

                            return redirect()->away($dest . $args, 302, $headers);
                        }
                    }
                }
            }
        } else {
            \Log::info('Magic: user not found as authenticated');
        }

        \Log::info('Magic: Fallthrough - Redirecting to ' . print_r($dest, true));
        return redirect()->away($dest);
    }
}
