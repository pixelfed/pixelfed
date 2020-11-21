<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Pixelfed\Snowflake\HasSnowflakePrimary;

class Story extends Model
{
    use HasSnowflakePrimary;

    public const MAX_PER_DAY = 20;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['published_at', 'expires_at'];

    protected $fillable = ['profile_id'];

	protected $visible = ['id'];

	protected $hidden = ['json'];

	public function profile()
	{
		return $this->belongsTo(Profile::class);
	}

	public function views()
	{
		return $this->hasMany(StoryView::class);
	}

	public function seen($pid = false)
	{
		return StoryView::whereStoryId($this->id)
			->whereProfileId(Auth::user()->profile->id)
			->exists();
	}

	public function permalink()
	{
		return url("/story/$this->id");
	}
}
