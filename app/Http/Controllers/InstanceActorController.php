<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InstanceActor;
use Cache;

class InstanceActorController extends Controller
{
	public function profile()
	{
		$res = Cache::rememberForever(InstanceActor::PROFILE_KEY, function() {
			$res = (new InstanceActor())->first()->getActor();
			return json_encode($res, JSON_UNESCAPED_SLASHES);
		});
		return response($res)->header('Content-Type', 'application/activity+json');
	}

	public function inbox()
	{
		return;
	}

	public function outbox()
	{
		$res = json_encode([
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => config('app.url') . '/i/actor/outbox',
			'type' => 'OrderedCollection',
			'totalItems' => 0,
			'first' => config('app.url') . '/i/actor/outbox?page=true',
			'last' =>  config('app.url') . '/i/actor/outbox?min_id=0page=true'
		], JSON_UNESCAPED_SLASHES);
		return response($res)->header('Content-Type', 'application/activity+json');
	}
}
