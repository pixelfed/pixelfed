<?php

namespace App\Http\Resources;

use App\Services\AccountService;
use App\Services\StatusService;
use App\Story;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property int $profile_id
 * @property string|null $type
 * @property int $object_id
 * @property string $object_type
 * @property int $reported_profile_id
 * @property string|null $message
 * @property \Illuminate\Support\Carbon|null $admin_seen
 * @property \Illuminate\Support\Carbon $created_at
 */
class AdminReport extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $res = [
            'id' => $this->id,
            'reporter' => AccountService::get($this->profile_id, true),
            'type' => $this->type,
            'object_id' => (string) $this->object_id,
            'object_type' => $this->object_type,
            'reported' => AccountService::get($this->reported_profile_id, true),
            'status' => null,
            'reporter_message' => $this->message,
            'admin_seen_at' => $this->admin_seen,
            'created_at' => $this->created_at,
        ];

        if ($this->object_id && $this->object_type === 'App\Status') {
            $res['status'] = StatusService::get($this->object_id, false);
        }

        if ($this->object_id && $this->object_type === 'App\Story') {
            $story = Story::find($this->object_id);
            if ($story) {
                $res['story'] = $story->toAdminEntity();
            }
        }

        return $res;
    }
}
