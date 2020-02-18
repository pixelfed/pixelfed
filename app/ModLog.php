<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModLog extends Model
{
	protected $visible = ['id'];

	public function admin()
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	public function actionToText()
	{
		$msg = 'Unknown action';

		switch ($this->action) {
			case 'admin.user.message':
				$msg = "Sent Email Message";
				break;

			case 'admin.user.action.cw.warn':
				$msg = "Sent CW reminder";
				break;
			
			default:
				$msg = 'Unknown action';
				break;
		}

		return $msg;
	}
}
