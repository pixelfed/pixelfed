<?php

namespace App\Http\Resources;

use App\Services\StatusService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportStatus extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return StatusService::get($this->status_id, false);
    }
}
