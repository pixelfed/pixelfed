<?php

namespace App\Services;

class ProfileService
{
	public static function get($id)
	{
		return AccountService::get($id);
	}

	public static function del($id)
	{
		return AccountService::del($id);
	}
}
