<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{Profile, Instance, Status, User};
use Cache;
use App\Services\StatusService;

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
				'created_at' 	  => str_replace('+00:00', 'Z', $admin->created_at->format(DATE_RFC3339_EXTENDED)),
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
			'title' => config_cache('app.name'),
			'description' => '',
			'version' => config('pixelfed.version'),
			'urls' => [],
			'stats' => [
				'user_count' => User::count(),
				'status_count' => StatusService::totalLocalStatuses(),
				'domain_count' => Instance::count()
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
