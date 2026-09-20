<?php

namespace App\Models;

use App\Services\AvatarService;
use App\Util\RateLimit\User as UserRateLimit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Passport\Client;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use NotificationChannels\WebPush\PushSubscription;

/**
 * @property int $id
 * @property int|null $profile_id
 * @property string|null $name
 * @property string|null $username
 * @property string $email
 * @property string|null $status
 * @property string|null $language
 * @property string $password
 * @property string|null $remember_token
 * @property bool $is_admin
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $delete_after
 * @property int $has_interstitial
 * @property string|null $guid
 * @property string|null $domain
 * @property string|null $register_source
 * @property string|null $app_register_token
 * @property string|null $app_register_ip
 * @property int $has_roles
 * @property int|null $parent_id
 * @property int|null $role_id
 * @property string|null $expo_token
 * @property int $storage_used
 * @property Carbon|null $storage_used_updated_at
 * @property int $notify_enabled
 * @property-read Collection<int, AccountLog> $accountLog
 * @property-read int|null $account_log_count
 * @property-read Collection<int, Client> $clients
 * @property-read int|null $clients_count
 * @property-read Collection<int, UserDevice> $devices
 * @property-read int|null $devices_count
 * @property-read Collection<int, UserFilter> $filters
 * @property-read int|null $filters_count
 * @property-read mixed $max_collections_per_day
 * @property-read mixed $max_collections_per_hour
 * @property-read mixed $max_collections_per_month
 * @property-read mixed $max_comments_per_day
 * @property-read mixed $max_comments_per_hour
 * @property-read mixed $max_compose_media_updates_per_day
 * @property-read mixed $max_compose_media_updates_per_hour
 * @property-read mixed $max_compose_media_updates_per_month
 * @property-read mixed $max_hashtag_follows_per_day
 * @property-read mixed $max_hashtag_follows_per_hour
 * @property-read mixed $max_instance_bans_per_day
 * @property-read int $max_likes_per_day
 * @property-read int $max_likes_per_hour
 * @property-read mixed $max_post_edits_per_day
 * @property-read mixed $max_post_edits_per_hour
 * @property-read mixed $max_posts_per_day
 * @property-read mixed $max_posts_per_hour
 * @property-read mixed $max_shares_per_day
 * @property-read mixed $max_shares_per_hour
 * @property-read mixed $max_stories_per_day
 * @property-read mixed $max_stories_per_hour
 * @property-read mixed $max_story_delete_per_day
 * @property-read mixed $max_user_bans_per_day
 * @property-read Collection<int, AccountInterstitial> $interstitials
 * @property-read int|null $interstitials_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Client> $oauthApps
 * @property-read int|null $oauth_apps_count
 * @property-read Profile|null $profile
 * @property-read Collection<int, PushSubscription> $pushSubscriptions
 * @property-read int|null $push_subscriptions_count
 * @property-read UserSetting|null $settings
 * @property-read Collection<int, Status> $statuses
 * @property-read int|null $statuses_count
 * @property-read Collection<int, OAuthToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User where2faBackupCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User where2faEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User where2faSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User where2faSetupAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAppRegisterIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAppRegisterToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeleteAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereExpoToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHasInterstitial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHasRoles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastActiveAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNotifyEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRegisterSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStorageUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStorageUsedUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable, SoftDeletes, UserRateLimit;

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'deleted_at' => 'datetime',
            'email_verified_at' => 'datetime',
            '2fa_setup_at' => 'datetime',
            'last_active_at' => 'datetime',
            'storage_used_updated_at' => 'datetime',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'email',
        'password',
        'is_admin',
        'remember_token',
        'email_verified_at',
        '2fa_enabled',
        '2fa_secret',
        '2fa_backup_codes',
        '2fa_setup_at',
        'deleted_at',
        'updated_at',
    ];

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function url()
    {
        return url(config('app.url').'/'.$this->username);
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function statuses()
    {
        return $this->hasManyThrough(
            Status::class,
            Profile::class
        );
    }

    public function filters()
    {
        return $this->hasMany(UserFilter::class, 'user_id', 'profile_id');
    }

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'App.User.'.$this->id;
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function storageUsedKey(): string
    {
        return 'profile:storage:used:'.$this->id;
    }

    public function accountLog()
    {
        return $this->hasMany(AccountLog::class);
    }

    public function interstitials()
    {
        return $this->hasMany(AccountInterstitial::class);
    }

    public function avatarUrl()
    {
        if (! $this->profile_id || $this->status) {
            return config('app.url').'/storage/avatars/default.jpg';
        }

        return AvatarService::get($this->profile_id);
    }

    public function routeNotificationForExpo()
    {
        return $this->expo_token;
    }
}
