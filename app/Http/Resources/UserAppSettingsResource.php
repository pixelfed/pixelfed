<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAppSettingsResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->profile_id,
            'username' => $request->user()->username,
            'updated_at' => str_replace('+00:00', 'Z', $this->updated_at->format(DATE_RFC3339_EXTENDED)),
            'common' => $this->common,
        ];
    }
}
