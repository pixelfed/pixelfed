<?php

namespace App\Services\Account;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\RemoteAuthInstance;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class RemoteAuthService
{
    const CACHE_KEY = 'pf:services:remoteauth:';

    public static function getConfig()
    {
        return json_encode([
            'default_only' => config('remote-auth.mastodon.domains.only_default'),
            'custom_only' => config('remote-auth.mastodon.domains.only_custom'),
        ]);
    }

    public static function getMastodonClient($domain)
    {
        if(RemoteAuthInstance::whereDomain($domain)->exists()) {
            return RemoteAuthInstance::whereDomain($domain)->first();
        }

        try {
            $url = 'https://' . $domain . '/api/v1/apps';
            $res = Http::asForm()->throw()->timeout(10)->post($url, [
                'client_name' => config('pixelfed.domain.app', 'pixelfed'),
                'redirect_uris' => url('/auth/mastodon/callback'),
                'scopes' => 'read',
                'website' => 'https://pixelfed.org'
            ]);

            if(!$res->ok()) {
                return false;
            }
        } catch (RequestException $e) {
            return false;
        } catch (ConnectionException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        $body = $res->json();

        if(!$body || !isset($body['client_id'])) {
            return false;
        }

        $raw = RemoteAuthInstance::updateOrCreate([
            'domain' => $domain
        ], [
            'client_id' => $body['client_id'],
            'client_secret' => $body['client_secret'],
            'redirect_uri' => $body['redirect_uri'],
        ]);

        return $raw;
    }

    public static function getToken($domain, $code)
    {
        $raw = RemoteAuthInstance::whereDomain($domain)->first();
        if(!$raw || !$raw->active || $raw->banned) {
            return false;
        }

        $url = 'https://' . $domain . '/oauth/token';
        $res = Http::asForm()->post($url, [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $raw->client_id,
            'client_secret' => $raw->client_secret,
            'redirect_uri' => $raw->redirect_uri,
            'scope' => 'read'
        ]);

        return $res;
    }

    public static function getVerifyCredentials($domain, $code)
    {
        $raw = RemoteAuthInstance::whereDomain($domain)->first();
        if(!$raw || !$raw->active || $raw->banned) {
            return false;
        }

        $url = 'https://' . $domain . '/api/v1/accounts/verify_credentials';

        $res = Http::withToken($code)->get($url);

        return $res->json();
    }

    public static function getFollowing($domain, $code, $id)
    {
        $raw = RemoteAuthInstance::whereDomain($domain)->first();
        if(!$raw || !$raw->active || $raw->banned) {
            return false;
        }

        $url = 'https://' . $domain . '/api/v1/accounts/' . $id . '/following?limit=80';
        $key = self::CACHE_KEY . 'get-following:code:' . substr($code, 0, 16) . substr($code, -5) . ':domain:' . $domain. ':id:' .$id;

        return Cache::remember($key, 3600, function() use($url, $code) {
            $res = Http::withToken($code)->get($url);
            return $res->json();
        });
    }

    public static function isDomainCompatible($domain = false)
    {
        if(!$domain) {
            return false;
        }

        return Cache::remember(self::CACHE_KEY . 'domain-compatible:' . $domain, 14400, function() use($domain) {
            try {
                $res = Http::timeout(20)->retry(3, 750)->get('https://beagle.pixelfed.net/api/v1/raa/domain?domain=' . $domain);
                if(!$res->ok()) {
                    return false;
                }
            } catch (RequestException $e) {
                return false;
            } catch (ConnectionException $e) {
                return false;
            } catch (Exception $e) {
                return false;
            }
            $json = $res->json();

            if(!in_array('compatible', $json)) {
                return false;
            }

            return $res['compatible'];
        });
    }

    public static function lookupWebfingerUses($wf)
    {
        try {
            $res = Http::timeout(20)->retry(3, 750)->get('https://beagle.pixelfed.net/api/v1/raa/lookup?webfinger=' . $wf);
            if(!$res->ok()) {
                return false;
            }
        } catch (RequestException $e) {
            return false;
        } catch (ConnectionException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
        $json = $res->json();
        if(!$json || !isset($json['count'])) {
            return false;
        }

        return $json['count'];
    }

    public static function submitToBeagle($ow, $ou, $dw, $du)
    {
        try {
            $url = 'https://beagle.pixelfed.net/api/v1/raa/submit';
            $res = Http::throw()->timeout(10)->get($url, [
                'ow' => $ow,
                'ou' => $ou,
                'dw' => $dw,
                'du' => $du,
            ]);

            if(!$res->ok()) {
                return;
            }
        } catch (RequestException $e) {
            return;
        } catch (ConnectionException $e) {
            return;
        } catch (Exception $e) {
            return;
        }

        return;
    }
}
