<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use App\Profile;
use App\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupPost extends Model
{
    use HasFactory, HasSnowflakePrimary;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $fillable = [
        'remote_url',
        'group_id',
        'profile_id',
        'type',
        'caption',
        'visibility',
        'is_nsfw',
    ];

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
