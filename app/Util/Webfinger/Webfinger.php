<?php

namespace App\Util\Webfinger;

use App\User;
use App\Util\Lexer\Nickname;

class Webfinger {

  public $user;
  public $subject;
  public $aliases;
  public $links;

  public function __construct($user)
  {
    $this->user = $user;
    $this->subject = '';
    $this->aliases = array();
    $this->links = array();
  }

  public function setSubject()
  {
    $host = parse_url(config('app.url'), PHP_URL_HOST);
    $username = $this->user->username;

    $this->subject = 'acct:'.$username.'@'.$host;
    return $this;
  }

  public function generateAliases()
  {
    $host = parse_url(config('app.url'), PHP_URL_HOST);
    $username = $this->user->username;
    $url = $this->user->url();

    $this->aliases = [
      'acct:'.$username.'@'.$host,
      $url
    ];
    return $this;
  }

  public function generateLinks()
  {
    $user = $this->user;

    $this->links = [
      [
        'rel' => 'http://webfinger.net/rel/profile-page',
        'type' => 'text/html',
        'href' => $user->url()
      ],
      [
        'rel' => 'http://schemas.google.com/g/2010#updates-from',
        'type' => 'application/atom+xml',
        'href' => url("/users/{$user->username}.atom")
      ],
      [
        'rel' => 'self',
        'type' => 'application/activity+json',
        'href' => $user->permalink()
      ],
      [
        'rel' => 'magic-public-key',
        'href' => null//$user->public_key
      ],
      [
        'rel' => 'salmon',
        'href' => $user->permalink('/salmon')
      ],
      [
        'rel' => 'http://ostatus.org/schema/1.0/subscribe',
        'href' => url('/main/ostatussub?profile={uri}')
      ]
    ];
    return $this;
  }

  public function generate()
  {
    $this->setSubject();
    $this->generateAliases();
    $this->generateLinks();

    return [
      'subject' => $this->subject,
      'aliases' => $this->aliases,
      'links'   => $this->links
    ];
  }



}