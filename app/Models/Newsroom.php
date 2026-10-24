<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $header_photo_url
 * @property string|null $title
 * @property string|null $slug
 * @property string $category
 * @property string|null $summary
 * @property string|null $body
 * @property string|null $body_rendered
 * @property string|null $link
 * @property int $force_modal
 * @property int $show_timeline
 * @property int $show_link
 * @property int $auth_only
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereAuthOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereBodyRendered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereForceModal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereHeaderPhotoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereShowLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereShowTimeline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsroom whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Newsroom extends Model
{
    protected $table = 'newsroom';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function permalink()
    {
        $year = $this->published_at->year;
        $month = $this->published_at->format('m');
        $slug = $this->slug;

        return url("/site/newsroom/{$year}/{$month}/{$slug}");
    }

    public function editUrl()
    {
        return url("/i/admin/newsroom/edit/{$this->id}");
    }
}
