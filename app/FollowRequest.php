<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $follower_id
 * @property int $following_id
 * @property array|null $activity
 * @property Carbon|null $handled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile $target
 * @property-read Profile $actor
 * @property-read Profile $follower
 * @property-read Profile $following
 */
class FollowRequest extends Model
{
    protected $fillable = ['follower_id', 'following_id', 'activity', 'handled_at'];

    protected function casts(): array
    {
        return [
            'activity' => 'array',
        ];
    }

    public function actor()
    {
        return $this->belongsTo(Profile::class, 'follower_id', 'id');
    }

    public function follower()
    {
        return $this->belongsTo(Profile::class, 'follower_id', 'id');
    }

    public function following()
    {
        return $this->belongsTo(Profile::class, 'following_id', 'id');
    }

    public function target()
    {
        return $this->belongsTo(Profile::class, 'following_id', 'id');
    }

    public function permalink($append = null, $namespace = '#accepts')
    {
        $path = $this->target->permalink("{$namespace}/follows/{$this->id}{$append}");

        return url($path);
    }
}
