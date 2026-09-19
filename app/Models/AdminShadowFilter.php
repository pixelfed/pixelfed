<?php

namespace App\Models;

use App\Services\AccountService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $admin_id
 * @property string $item_type
 * @property int $item_id
 * @property int $is_local
 * @property string|null $note
 * @property int $active
 * @property string|null $history
 * @property string|null $ruleset
 * @property int $prevent_ap_fanout
 * @property int $prevent_new_dms
 * @property int $ignore_reports
 * @property int $ignore_mentions
 * @property int $ignore_links
 * @property int $ignore_hashtags
 * @property int $hide_from_public_feeds
 * @property int $hide_from_tag_feeds
 * @property int $hide_embeds
 * @property int $hide_from_story_carousel
 * @property int $hide_from_search_autocomplete
 * @property int $hide_from_search
 * @property int $requires_login
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereHideEmbeds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereHideFromPublicFeeds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereHideFromSearch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereHideFromSearchAutocomplete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereHideFromStoryCarousel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereHideFromTagFeeds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereHistory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereIgnoreHashtags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereIgnoreLinks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereIgnoreMentions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereIgnoreReports($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereIsLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter wherePreventApFanout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter wherePreventNewDms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereRequiresLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereRuleset($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminShadowFilter whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AdminShadowFilter extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function account()
    {
        if (in_array($this->item_type, ['App\Profile', Profile::class])) {
            return AccountService::get($this->item_id, true);
        }
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'item_id');
    }
}
