<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $domain
 * @property int|null $active_deliver
 * @property string|null $url
 * @property string|null $name
 * @property string|null $admin_url
 * @property string|null $limit_reason
 * @property int $unlisted
 * @property int $auto_cw
 * @property int $banned
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $software
 * @property int|null $user_count
 * @property int|null $status_count
 * @property Carbon|null $last_crawled_at
 * @property Carbon|null $actors_last_synced_at
 * @property array<array-key, mixed>|null $notes
 * @property int $manually_added
 * @property string|null $base_domain
 * @property int|null $ban_subdomains
 * @property string|null $ip_address
 * @property int $list_limitation
 * @property int|null $valid_nodeinfo
 * @property Carbon|null $nodeinfo_last_fetched
 * @property int $delivery_timeout
 * @property Carbon|null $delivery_next_after
 * @property string|null $shared_inbox
 * @property int|null $allowlisted
 * @property int $delivery_failures
 * @property-read Collection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read Collection<int, Profile> $profiles
 * @property-read int|null $profiles_count
 * @property-read Collection<int, Report> $reported
 * @property-read int|null $reported_count
 * @property-read Collection<int, Report> $reports
 * @property-read int|null $reports_count
 * @property-read Collection<int, Status> $statuses
 * @property-read int|null $statuses_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance moderated()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereActiveDeliver($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereActorsLastSyncedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereAdminUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereAllowlisted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereAutoCw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereBanSubdomains($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereBanned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereBaseDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereDeliveryFailures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereDeliveryNextAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereDeliveryTimeout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereLastCrawledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereLimitReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereListLimitation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereManuallyAdded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereNodeinfoLastFetched($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereSharedInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereSoftware($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereStatusCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereUnlisted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereUserCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Instance whereValidNodeinfo($value)
 *
 * @mixin \Eloquent
 */
class Instance extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_crawled_at' => 'datetime',
            'actors_last_synced_at' => 'datetime',
            'notes' => 'array',
            'nodeinfo_last_fetched' => 'datetime',
            'delivery_next_after' => 'datetime',
        ];
    }

    // To get all moderated instances, we need to search where (banned OR unlisted)
    public function scopeModerated($query): void
    {
        $query->where(function ($query) {
            $query->where('banned', true)->orWhere('unlisted', true);
        });
    }

    public function profiles()
    {
        return $this->hasMany(Profile::class, 'domain', 'domain');
    }

    public function statuses()
    {
        return $this->hasManyThrough(
            Status::class,
            Profile::class,
            'domain',
            'profile_id',
            'domain',
            'id'
        );
    }

    public function reported()
    {
        return $this->hasManyThrough(
            Report::class,
            Profile::class,
            'domain',
            'reported_profile_id',
            'domain',
            'id'
        );
    }

    public function reports()
    {
        return $this->hasManyThrough(
            Report::class,
            Profile::class,
            'domain',
            'profile_id',
            'domain',
            'id'
        );
    }

    public function media()
    {
        return $this->hasManyThrough(
            Media::class,
            Profile::class,
            'domain',
            'profile_id',
            'domain',
            'id'
        );
    }

    public function getUrl()
    {
        return url("/i/admin/instances/show/{$this->id}");
    }
}
