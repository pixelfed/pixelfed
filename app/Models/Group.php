<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use App\Services\GroupService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property int|null $profile_id
 * @property string|null $status
 * @property string|null $name
 * @property string|null $description
 * @property string|null $rules
 * @property int $local
 * @property string|null $remote_url
 * @property string|null $inbox_url
 * @property int $is_private
 * @property int $local_only
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $member_count
 * @property int $recommended
 * @property int $discoverable
 * @property int $activitypub
 * @property int $is_nsfw
 * @property int $dms
 * @property int $autospam
 * @property int $verified
 * @property string|null $last_active_at
 * @property Carbon|null $deleted_at
 * @property-read Profile|null $admin
 * @property-read Collection<int, GroupMember> $members
 * @property-read int|null $members_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereActivitypub($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereAutospam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereDiscoverable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereDms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereInboxUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereIsNsfw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereIsPrivate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereLastActiveAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereLocalOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereMemberCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereRecommended($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereRemoteUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereRules($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Group extends Model
{
    use HasFactory, HasSnowflakePrimary, SoftDeletes;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
        ];
    }

    public function url()
    {
        return url("/groups/{$this->id}");
    }

    public function permalink($suffix = null)
    {
        if (! $this->local) {
            return $this->remote_url;
        }

        return $this->url().$suffix;
    }

    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function admin()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function isMember($id = false)
    {
        $id = $id ?? request()->user()->profile_id;

        // return $this->members()->whereProfileId($id)->whereJoinRequest(false)->exists();
        return GroupService::isMember($this->id, $id);
    }

    public function getMembershipType(): string
    {
        return $this->is_private ? 'private' : ($this->local ? 'local' : 'all');
    }

    public function selfRole($id = false)
    {
        $id = $id ?? request()->user()->profile_id;

        return optional($this->members()->whereProfileId($id)->first())->role ?? null;
    }
}
