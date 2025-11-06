<?php

namespace App\Http\Resources;

use App\RemoteReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Instance;
use App\Services\AccountService;
use App\Services\StatusService;

/**
 * @property RemoteReport $resource
 */
class AdminRemoteReport extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $instance = parse_url($this->resource->uri, PHP_URL_HOST);
        $statuses = [];
        if($this->resource->status_ids && count($this->resource->status_ids)) {
            foreach($this->resource->status_ids as $sid) {
                $s = StatusService::get($sid, false);
                if($s && $s['in_reply_to_id'] != null) {
                    $parent = StatusService::get($s['in_reply_to_id'], false);
                    if($parent) {
                        $s['parent'] = $parent;
                    }
                }
                if($s) {
                    $statuses[] = $s;
                }
            }
        }
        $res = [
            'id' => $this->resource->id,
            'instance' => $instance,
            'reported' => AccountService::get($this->resource->account_id, true),
            'status_ids' => $this->resource->status_ids,
            'statuses' => $statuses,
            'message' => $this->resource->comment,
            'report_meta' => $this->resource->report_meta,
            'created_at' => optional($this->resource->created_at)->format('c'),
            'action_taken_at' => optional($this->resource->action_taken_at)->format('c'),
        ];
        return $res;
    }
}
