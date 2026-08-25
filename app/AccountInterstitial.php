<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountInterstitial extends Model
{
    public const JSON_MESSAGE = 'Please use web browser to proceed.';

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'appeal_requested_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        if ($this->item_type != \App\Status::class) {
            return;
        }

        return $this->hasOne(Status::class, 'id', 'item_id');
    }
}
