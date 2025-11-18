<?php

namespace App\Http\Resources;

use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $profile_id
 * @property \Illuminate\Support\Carbon $created_at
 */
class StoryView extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $res = AccountService::get($this->profile_id, true);
        $res['viewed_at'] = $this->created_at->format('c');

        return $res;
    }
}
