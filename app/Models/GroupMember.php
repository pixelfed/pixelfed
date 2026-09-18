<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\Group|null $group
 */
class GroupMember extends Model
{
    use HasFactory;

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
