<?php

namespace App\Http\Resources;

use App\Services\AccountService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property int $profile_id
 * @property string|null $name
 * @property string $username
 * @property bool $is_admin
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property bool $2fa_enabled
 * @property string|null $register_source
 * @property string|null $app_register_ip
 * @property bool $has_interstitial
 * @property \Illuminate\Support\Carbon|null $last_active_at
 * @property \Illuminate\Support\Carbon $created_at
 */
class AdminUser extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $account = AccountService::get($this->profile_id, true);

        $res = [
            'id' => (string) $this->id,
            'profile_id' => (string) $this->profile_id,
            'name' => $this->name,
            'username' => $this->username,
            'is_admin' => (bool) $this->is_admin,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'two_factor_enabled' => (bool) $this->{'2fa_enabled'},
            'register_source' => $this->register_source,
            'app_register_ip' => $this->app_register_ip,
            'has_interstitial' => (bool) $this->has_interstitial,
            'last_active_at' => $this->last_active_at,
            'created_at' => $this->created_at,
        ];

        if ($account) {
            $res['avatar'] = $account['avatar'];
            $res['bio'] = $account['note_text'];
            $res['statuses_count'] = (int) $account['statuses_count'];
            $res['following_count'] = (int) $account['following_count'];
            $res['followers_count'] = (int) $account['followers_count'];
            $res['is_private'] = (bool) $account['locked'];
        }

        return $res;
    }
}
