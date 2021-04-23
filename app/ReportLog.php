<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportLog extends Model
{
	public function profile()
	{
		return $this->belongsTo(Profile::class);
	}
}
