<?php

namespace App\Models;

use App\Profile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        return '/group/'.$this->group_id.'/c/'.$this->id;
    }
}
