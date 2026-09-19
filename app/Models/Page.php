<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $root
 * @property string|null $slug
 * @property string|null $title
 * @property int|null $category_id
 * @property string|null $content
 * @property string $template
 * @property int $active
 * @property int $cached
 * @property string|null $active_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereActiveUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereCached($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereRoot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Page extends Model
{
    const SLUG_ROOT = [
        'site',
        'page',
    ];

    protected $guarded = [];

    public function url()
    {
        return url($this->slug);
    }

    public function editUrl()
    {
        return url('/i/admin/settings/pages/edit?page='.urlencode($this->slug));
    }
}
