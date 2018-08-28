<?php

namespace App\Util\Webfinger;

class Webfinger
{
    public $user;
    public $subject;
    public $aliases;
    public $links;

    public function __construct($user)
    {
        $this->user = $user;
        $this->subject = '';
        $this->aliases = [];
        $this->links = [];
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
        $this->aliases = [
      $this->user->url(),
      $this->user->permalink(),
    ];

        return $this;
    }

    public function generateLinks()
    {
        $user = $this->user;

        $this->links = [
      [
        'rel'  => 'http://webfinger.net/rel/profile-page',
        'type' => 'text/html',
        'href' => $user->url(),
      ],
      [
        'rel'  => 'http://schemas.google.com/g/2010#updates-from',
        'type' => 'application/atom+xml',
        'href' => $user->permalink('.atom'),
      ],
      [
        'rel'  => 'self',
        'type' => 'application/activity+json',
        'href' => $user->permalink(),
      ],
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
      'links'   => $this->links,
    ];
    }
}
