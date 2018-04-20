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
        'rel' => 'http://gmpg.org/xfn/11',
        'type' => 'text/html',
        'href' => $user->url()
      ],
      [
        'rel' => 'describedby',
        'type' => 'application\/rdf+xml',
        'href' => $user->url('/foaf')
      ],
      [
        'rel' => 'http://apinamespace.org/atom',
        'type' => 'application/atomsvc+xml',
        'href' => url('/api/statusnet/app/service/admin.xml')
      ],
      [
        'rel' => 'http://apinamespace.org/twitter',
        'href' => url('/api/')
      ],
      [
        'rel' => 'http://specs.openid.net/auth/2.0/provider',
        'href' => $user->url()
      ],
      [
        'rel' => 'http://schemas.google.com/g/2010#updates-from',
        'type' => 'application/atom+xml',
        'href' => url("/api/statuses/user_timeline/{$user->id}.atom")
      ],
      [
        'rel' => 'magic-public-key',
        'href' => ''
      ],
      [
        'rel' => 'salmon',
        'href' => $user->url('/salmon')
      ],
      [
        'rel' => 'http://salmon-protocol.org/ns/salmon-replies',
        'href' => $user->url('/salmon')
      ],
      [
        'rel' => 'http://salmon-protocol.org/ns/salmon-mention',
        'href' => $user->url('/salmon')
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