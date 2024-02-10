<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;

class NodeinfoService
{
    public static function get($domain)
    {
    	$version = config('pixelfed.version');
		$appUrl = config('app.url');
		$headers = [
			'Accept'     => 'application/json',
			'User-Agent' => "(Pixelfed/{$version}; +{$appUrl})",
		];

        $url = 'https://' . $domain;
        $wk = $url . '/.well-known/nodeinfo';

        try {
            $res = Http::withOptions([
                'allow_redirects' => false,
            ])
            ->withHeaders($headers)
            ->timeout(5)
            ->get($wk);
        } catch (RequestException $e) {
            return false;
        } catch (ConnectionException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        if(!$res) {
            return false;
        }

        $json = $res->json();

        if( !isset($json['links'])) {
            return false;
        }

        if(is_array($json['links'])) {
            if(isset($json['links']['href'])) {
                $href = $json['links']['href'];
            } else {
                $href = $json['links'][0]['href'];
            }
        } else {
            return false;
        }

        $domain = parse_url($url, PHP_URL_HOST);
        $hrefDomain = parse_url($href, PHP_URL_HOST);

        if($domain !== $hrefDomain) {
            return 60;
        }

        try {
            $res = Http::withOptions([
                'allow_redirects' => false,
            ])
            ->withHeaders($headers)
            ->timeout(5)
            ->get($href);
        } catch (RequestException $e) {
            return false;
        } catch (ConnectionException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
        return $res->json();
    }
}
