<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class ProfileMigration extends Model
{
    use HasFactory;

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function target()
    {
        return $this->belongsTo(Profile::class, 'target_profile_id');
    }
}
