<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use App\Util\Lexer\Bearcap;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $profile_id
 * @property string|null $path
 * @property string|null $bearcap_token
 * @property array|null $story
 * @property int $view_count
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile $profile
 */
#[Table(incrementing: false)]
#[Unguarded]
#[Visible('id')]
#[Hidden('json')]
class Story extends Model
{
    use HasSnowflakePrimary;

    public const MAX_PER_DAY = 20;

    protected function casts(): array
    {
        return [
            'story' => 'json',
            'expires_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    public function profile(): BelongsTo
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
