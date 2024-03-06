<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Profile;

class ProfileMigration extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function target()
    {
        return $this->belongsTo(Profile::class, 'target_profile_id');
    }
}
