<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Profile;

class GroupComment extends Model
{
    use HasFactory;

    public $guarded = [];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function url()
    {
        return '/group/' . $this->group_id . '/c/' . $this->id;
    }
}
