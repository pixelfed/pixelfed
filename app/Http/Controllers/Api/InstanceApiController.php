<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{Profile, Status, User};
use Cache;

class InstanceApiController extends Controller {

	protected function getData()
	{
		$contact = Cache::remember('api:v1:instance:contact', now()->addMinutes(1440), function() {
			$admin = User::whereIsAdmin(true)->first()->profile;
			return [
				'id' 			  => $admin->id,
				'username' 		  => $admin->username,
				'acct'			  => $admin->username,
				'display_name' 	  => e($admin->name),
				'locked' 		  => (bool) $admin->is_private,
				'bot'			  => false,
				'created_at' 	  => $admin->created_at->format('c'),
				'note' 			  => e($admin->bio),
				'url' 			  => $admin->url(),
				'avatar' 		  => $admin->avatarUrl(),
				'avatar_static'   => $admin->avatarUrl(),
				'header'          => null,
				'header_static'   => null,
				'moved'           => null,
				'fields'          => null,
				'bot'             => null,
			];
		});

		$res = [
			'uri' => config('pixelfed.domain.app'),
			'title' => config('app.name'),
			'description' => '',
			'version' => config('pixelfed.version'),
			'urls' => [],
			'stats' => [
				'user_count' => User::count(),
				'status_count' => Status::whereNull('uri')->count(),
				'domain_count' => Profile::whereNotNull('domain')
					->groupBy('domain')
					->pluck('domain')
					->count()
			],
			'thumbnail' => '',
			'languages' => [],
			'contact_account' => $contact
		];
		return $res;
	}

	public function instance()
	{
		$res = Cache::remember('api:v1:instance', now()->addMinutes(60), function() {
			return json_encode($this->getData());
		});

		return response($res)->header('Content-Type', 'application/json');
	}

}