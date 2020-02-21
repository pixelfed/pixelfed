<?php

namespace App\Services;

use App\ModLog;
use App\User;

class ModLogService {

	protected $log;

	public function __construct()
	{
		$this->log = new \StdClass;
	}

	public static function boot()
	{
		return new self;
	}

	public function user(User $user)
	{
		$this->log->user = $user;
		return $this;
	}

	public function objectUid($val = null)
	{
		$this->log->object_uid = $val;
		return $this;
	}

	public function objectId($val = null)
	{
		$this->log->object_id = $val;
		return $this;
	}

	public function objectType($val = null)
	{
		$this->log->object_type = $val;
		return $this;
	}

	public function action($val = null)
	{
		$this->log->action = $val;
		return $this;
	}

	public function message($val = null)
	{
		$this->log->message = $val;
		return $this;
	}

	public function metadata(array $val = null)
	{
		$this->log->metadata = json_encode($val);
		return $this;
	}

	public function accessLevel($val = null)
	{
		if(!in_array($val, ['admin', 'mod'])) {
			return $this;
		}
		$this->log->access_level = $val;
		return $this;
	}

	public function save($res = false)
	{
		$log = $this->log;
		if(!isset($log->user)) {
			throw new \Exception('Invalid ModLog attribute.');
		}

		$ml = new ModLog();
		$ml->user_id = $log->user->id;
		$ml->user_username = $log->user->username;
		$ml->object_uid = $log->object_uid ?? null;
		$ml->object_id = $log->object_id ?? null;
		$ml->object_type = $log->object_type ?? null;
		$ml->action = $log->action ?? null;
		$ml->message = $log->message ?? null;
		$ml->metadata = $log->metadata ?? null;
		$ml->access_level = $log->access_level ?? 'admin';
		$ml->save();

		if($res == true) {
			return $ml;
		} else {
			return;
		}
	}
}