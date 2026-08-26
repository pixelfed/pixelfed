<?php

namespace App\Http\Resources;

use App\Services\StatusService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $type
 * @property int|null $item_id
 * @property string|null $item_type
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon $created_at
 */
class AdminSpamReport extends JsonResource
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
            'type' => $this->type,
            'status' => null,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];

        if ($this->item_id && $this->item_type === \App\Status::class) {
            $res['status'] = StatusService::get($this->item_id, false);
        }

        return $res;
    }
}
