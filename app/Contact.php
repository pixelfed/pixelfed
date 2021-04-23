<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function adminUrl()
    {
    	return url('/i/admin/messages/show/' . $this->id);
    }
}
