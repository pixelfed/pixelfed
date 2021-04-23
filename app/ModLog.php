<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModLog extends Model
{
	protected $visible = ['id'];

	protected $fillable = ['*'];

	public function admin()
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	public function actionToText()
	{
		$msg = 'Unknown action';

		switch ($this->action) {
			case 'admin.user.mail':
				$msg = "Sent Message";
				break;

			case 'admin.user.action.cw.warn':
				$msg = "Sent CW reminder";
				break;

			case 'admin.user.edit':
				$msg = "Changed Profile";
				break;

			case 'admin.user.moderate':
				$msg = "Moderation";
				break;

			case 'admin.user.delete':
				$msg = "Deleted Account";
				break;
			
			default:
				$msg = 'Unknown action';
				break;
		}

		return $msg;
	}
}
