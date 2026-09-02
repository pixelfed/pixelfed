<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class UserFilter extends Model
{
    public function mutedUserIds($profile_id)
    {
        return $this->whereUserId($profile_id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('mute')
            ->pluck('filterable_id');
    }

    public function blockedUserIds($profile_id)
    {
        return $this->whereUserId($profile_id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('block')
            ->pluck('filterable_id');
    }

    public function instance()
    {
        return $this->belongsTo(Instance::class, 'filterable_id');
    }

    public function user()
    {
        return $this->belongsTo(Profile::class, 'user_id');
    }
}
