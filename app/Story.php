<?php

namespace App;

use App\Util\Lexer\Bearcap;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Storage;

/**
 * @property int $id
 * @property int $profile_id
 * @property string|null $path
 * @property string|null $bearcap_token
 * @property array|null $story
 * @property int $view_count
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Profile $profile
 */
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
        'story' => 'json',
        'expires_at' => 'datetime',
        'view_count' => 'integer',
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

    public function toAdminEntity()
    {
        return [
            'id' => $this->id,
            'profile_id' => $this->profile_id,
            'media_src' => $this->mediaUrl(),
            'url' => $this->url(),
            'type' => $this->type,
            'duration' => $this->duration,
            'mime' => $this->mime,
            'size' => $this->size,
            'local' => $this->local,
        ];
    }
}
