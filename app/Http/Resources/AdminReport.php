<?php

namespace App\Http\Resources;

use App\Report;
use App\Services\AccountService;
use App\Services\StatusService;
use App\Story;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Report $resource
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
            'id' => $this->resource->id,
            'reporter' => AccountService::get($this->resource->profile_id, true),
            'type' => $this->resource->type,
            'object_id' => (string) $this->resource->object_id,
            'object_type' => $this->resource->object_type,
            'reported' => AccountService::get($this->resource->reported_profile_id, true),
            'status' => null,
            'reporter_message' => $this->resource->message,
            'admin_seen_at' => $this->resource->admin_seen,
            'created_at' => $this->resource->created_at,
        ];

        if ($this->resource->object_id && $this->resource->object_type === 'App\Status') {
            $res['status'] = StatusService::get($this->resource->object_id, false);
        }

        if ($this->resource->object_id && $this->resource->object_type === 'App\Story') {
            $story = Story::find($this->resource->object_id);
            if ($story) {
                $res['story'] = $story->toAdminEntity();
            }
        }

        return $res;
    }
}
