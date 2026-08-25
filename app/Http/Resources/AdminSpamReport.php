<?php

namespace App\Http\Resources;

use App\Services\StatusService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $type
 * @property int|null $item_id
 * @property string|null $item_type
 * @property Carbon|null $read_at
 * @property Carbon $created_at
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

        if ($this->item_id && $this->item_type === 'App\Status') {
            $res['status'] = StatusService::get($this->item_id, false);
        }

        return $res;
    }
}
