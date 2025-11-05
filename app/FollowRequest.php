<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Profile;

class FollowRequest extends Model
{
	protected $fillable = ['follower_id', 'following_id', 'activity', 'handled_at'];

	protected $casts = [
		'activity' => 'array',
	];
	


    public function following()
    {
        return $this->belongsTo(Profile::class, 'following_id', 'id');
    }

    public function permalink($append = null, $namespace = '#accepts')
    {
        $path = $this->target->permalink("{$namespace}/follows/{$this->id}{$append}");
        return url($path);
    }
}
