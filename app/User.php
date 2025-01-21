<?php

namespace App;

use App\Services\AvatarService;
use App\Util\RateLimit\User as UserRateLimit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable, SoftDeletes, UserRateLimit;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
            'email_verified_at' => 'datetime',
            '2fa_setup_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'app_register_ip',
        'email_verified_at',
        'last_active_at',
        'register_source',
        'expo_token',
        'notify_enabled',
        'notify_like',
        'notify_follow',
        'notify_mention',
        'notify_comment',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'email', 'password', 'is_admin', 'remember_token',
        'email_verified_at', '2fa_enabled', '2fa_secret',
        '2fa_backup_codes', '2fa_setup_at', 'deleted_at',
        'updated_at',
    ];

    public function profile()
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

    public function receivesBroadcastNotificationsOn()
    {
        return 'App.User.'.$this->id;
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function storageUsedKey()
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
