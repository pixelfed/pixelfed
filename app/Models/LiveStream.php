<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $profile_id
 * @property string|null $stream_id
 * @property string|null $stream_key
 * @property string|null $visibility
 * @property string|null $name
 * @property string|null $description
 * @property string|null $thumbnail_path
 * @property string|null $settings
 * @property int $live_chat
 * @property string|null $mod_ids
 * @property int|null $discoverable
 * @property string|null $live_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereDiscoverable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereLiveAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereLiveChat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereModIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereStreamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereStreamKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereThumbnailPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStream whereVisibility($value)
 *
 * @mixin \Eloquent
 */
class LiveStream extends Model
{
    use HasFactory;

    public function getHlsUrl()
    {
        $path = Storage::url("live-hls/{$this->stream_id}/index.m3u8");

        return url($path);
    }

    public function getStreamServer(): string
    {
        $proto = 'rtmp://';
        $host = config('livestreaming.server.host');
        $port = ':'.config('livestreaming.server.port');
        $path = '/'.config('livestreaming.server.path');

        return $proto.$host.$port.$path;
    }

    public function getStreamKeyUrl(): string
    {
        $path = $this->getStreamServer().'?';
        $query = http_build_query([
            'name' => $this->stream_key,
        ]);

        return $path.$query;
    }

    public function getStreamRtmpUrl(): string
    {
        return $this->getStreamServer().'/'.$this->stream_id;
    }
}
