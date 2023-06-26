<?php

namespace App\Util\ActivityPub;

use Cache, Log;
use App\Models\InstanceActor;
use App\Profile;
use \DateTime;

class HttpSignature {

  /*
   * source: https://github.com/aaronpk/Nautilus/blob/master/app/ActivityPub/HTTPSignature.php
   * thanks aaronpk!
   */

  public static function sign(Profile $profile, $url, $body = false, $addlHeaders = []) {
    if($body) {
      $digest = self::_digest($body);
    }
    $user = $profile;
    $headers = self::_headersToSign($url, $body ? $digest : false);
    $headers = array_merge($headers, $addlHeaders);
    $stringToSign = self::_headersToSigningString($headers);
    $signedHeaders = implode(' ', array_map('strtolower', array_keys($headers)));
    $key = openssl_pkey_get_private($user->private_key);
    openssl_sign($stringToSign, $signature, $key, OPENSSL_ALGO_SHA256);
    $signature = base64_encode($signature);
    $signatureHeader = 'keyId="'.$user->keyId().'",headers="'.$signedHeaders.'",algorithm="rsa-sha256",signature="'.$signature.'"';
    unset($headers['(request-target)']);
    $headers['Signature'] = $signatureHeader;

    return self::_headersToCurlArray($headers);
  }

  public static function instanceActorSign($url, $body = false, $addlHeaders = [], $method = 'post')
  {
    $keyId = config('app.url') . '/i/actor#main-key';
    $privateKey = Cache::rememberForever(InstanceActor::PKI_PRIVATE, function() {
      return InstanceActor::first()->private_key;
    });
    if($body) {
      $digest = self::_digest($body);
    }
    $headers = self::_headersToSign($url, $body ? $digest : false, $method);
    $headers = array_merge($headers, $addlHeaders);
    $stringToSign = self::_headersToSigningString($headers);
    $signedHeaders = implode(' ', array_map('strtolower', array_keys($headers)));
    $key = openssl_pkey_get_private($privateKey);
    openssl_sign($stringToSign, $signature, $key, OPENSSL_ALGO_SHA256);
    $signature = base64_encode($signature);
    $signatureHeader = 'keyId="'.$keyId.'",headers="'.$signedHeaders.'",algorithm="rsa-sha256",signature="'.$signature.'"';
    unset($headers['(request-target)']);
    $headers['Signature'] = $signatureHeader;

    return $headers;
  }

  public static function parseSignatureHeader($signature) {
    $parts = explode(',', $signature);
    $signatureData = [];

    foreach($parts as $part) {
      if(preg_match('/(.+)="(.+)"/', $part, $match)) {
        $signatureData[$match[1]] = $match[2];
      }
    }

    if(!isset($signatureData['keyId'])) {
      return [
        'error' => 'No keyId was found in the signature header. Found: '.implode(', ', array_keys($signatureData))
      ];
    }

    if(!filter_var($signatureData['keyId'], FILTER_VALIDATE_URL)) {
      return [
        'error' => 'keyId is not a URL: '.$signatureData['keyId']
      ];
    }

    if(!isset($signatureData['headers']) || !isset($signatureData['signature'])) {
      return [
        'error' => 'Signature is missing headers or signature parts'
      ];
    }

    return $signatureData;
  }

  public static function verify($publicKey, $signatureData, $inputHeaders, $path, $body) {
    $digest = 'SHA-256='.base64_encode(hash('sha256', $body, true));
    $headersToSign = [];
    foreach(explode(' ',$signatureData['headers']) as $h) {
      if($h == '(request-target)') {
        $headersToSign[$h] = 'post '.$path;
      } elseif($h == 'digest') {
        $headersToSign[$h] = $digest;
      } elseif(isset($inputHeaders[$h][0])) {
        $headersToSign[$h] = $inputHeaders[$h][0];
      }
    }
    $signingString = self::_headersToSigningString($headersToSign);

    $verified = openssl_verify($signingString, base64_decode($signatureData['signature']), $publicKey, OPENSSL_ALGO_SHA256);

    return [$verified, $signingString];
  }

  private static function _headersToSigningString($headers) {
    return implode("\n", array_map(function($k, $v){
             return strtolower($k).': '.$v;
           }, array_keys($headers), $headers));
  }

  private static function _headersToCurlArray($headers) {
    return array_map(function($k, $v){
             return "$k: $v";
           }, array_keys($headers), $headers);
  }

  private static function _digest($body) {
    if(is_array($body)) {
      $body = json_encode($body);
    }
    return base64_encode(hash('sha256', $body, true));
  }

  protected static function _headersToSign($url, $digest = false, $method = 'post') {
    $date = new DateTime('UTC');

    if(!in_array($method, ['post', 'get'])) {
        throw new \Exception('Invalid method used to sign headers in HttpSignature');
    }
    $headers = [
      '(request-target)' => $method . ' '.parse_url($url, PHP_URL_PATH),
      'Host' => parse_url($url, PHP_URL_HOST),
      'Date' => $date->format('D, d M Y H:i:s \G\M\T'),
    ];

    if($digest) {
      $headers['Digest'] = 'SHA-256='.$digest;
    }

    return $headers;
  }

}
