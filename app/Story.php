<?php

namespace App;

use App\Util\Lexer\Bearcap;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Storage;

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

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $fillable = ['profile_id', 'view_count'];

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
        $username = $this->profile->username;

        return url("/stories/{$username}/{$this->id}/activity");
    }

    public function url()
    {
        $username = $this->profile->username;

        return url("/stories/{$username}/{$this->id}");
    }

    public function mediaUrl()
    {
        return url(Storage::url($this->path));
    }

    public function bearcapUrl()
    {
        return Bearcap::encode($this->url(), $this->bearcap_token);
    }

    public function scopeToAudience($scope)
    {
        $res = [];

        switch ($scope) {
            case 'to':
                $res = [
                    $this->profile->permalink('/followers'),
                ];
                break;

            default:
                $res = [];
                break;
        }

        return $res;
    }
}
