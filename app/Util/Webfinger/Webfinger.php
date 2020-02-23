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
		];
	}

	public function generate(): array
	{
		return [
			'subject' => $this->subject,
			'aliases' => $this->aliases,
			'links'   => $this->links,
		];
	}
}
