<?php

namespace App\Http\Resources;

use App\Services\AccountService;
use Illuminate\Http\Resources\Json\JsonResource;

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
        $account = AccountService::get($this->resource->profile_id, true);

        $res = [
            'id' => (string) $this->resource->id,
            'profile_id' => (string) $this->resource->profile_id,
            'name' => $this->resource->name,
            'username' => $this->resource->username,
            'is_admin' => (bool) $this->resource->is_admin,
            'email' => $this->resource->email,
            'email_verified_at' => $this->resource->email_verified_at,
            'two_factor_enabled' => (bool) $this->resource->{'2fa_enabled'},
            'register_source' => $this->resource->register_source,
            'app_register_ip' => $this->resource->app_register_ip,
            'has_interstitial' => (bool) $this->resource->has_interstitial,
            'last_active_at' => $this->resource->last_active_at,
            'created_at' => $this->resource->created_at,
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
