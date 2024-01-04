<?php

namespace App\Util\Webfinger;

class Webfinger
{
    protected $user;
    protected $subject;
    protected $aliases;
    protected $links;

    public function __construct($user)
    {
        $avatar = $user ? $user->avatarUrl() : url('/storage/avatars/default.jpg');
        $avatarPath = parse_url($avatar, PHP_URL_PATH);
        $extension = pathinfo($avatarPath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg',
        ];
        $avatarType = $mimeTypes[$extension] ?? 'application/octet-stream';

        $this->subject = 'acct:'.$user->username.'@'.parse_url(config('app.url'), PHP_URL_HOST);
        $this->aliases = [
            $user->url(),
            $user->permalink(),
        ];
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
            [
                'rel' => 'http://webfinger.net/rel/avatar',
                'type' => $avatarType,
                'href' => $avatar,
            ],
        ];
    }

    public function generate()
    {
        return [
            'subject' => $this->subject,
            'aliases' => $this->aliases,
            'links'   => $this->links,
        ];
    }
}
