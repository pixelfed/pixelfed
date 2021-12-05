<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Mention;
use Illuminate\Support\Str;

class StatusMentionService
{
	public static function get($id)
	{
		return Mention::whereStatusId($id)
			->get()
			->map(function($mention) {
				return AccountService::get($mention->profile_id);
			})->filter(function($mention) {
				return $mention;
			})
			->values()
			->toArray();
	}
}
