<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\AccountService;
use App\Services\StatusService;

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

    	if($this->object_id && $this->object_type === 'App\Status') {
    		$res['status'] = StatusService::get($this->object_id, false);
    	}

        return $res;
    }
}
