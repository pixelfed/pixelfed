<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(incrementing: false)]
#[Unguarded]
class GroupPost extends Model
{
    use HasFactory, HasSnowflakePrimary;

    public function mediaPath()
    {
        return 'public/g/_v1/'.$this->group_id.'/'.$this->id;
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function url()
    {
        return '/groups/'.$this->group_id.'/p/'.$this->id;
    }
}
