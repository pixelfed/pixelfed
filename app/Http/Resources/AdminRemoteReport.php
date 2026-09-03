<?php

namespace App\Http\Resources;

use App\Services\AccountService;
use App\Services\StatusService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uri
 * @property int $account_id
 * @property array|null $status_ids
 * @property string|null $comment
 * @property array|null $report_meta
 * @property Carbon|null $created_at
 * @property Carbon|null $action_taken_at
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
        $instance = parse_url($this->uri, PHP_URL_HOST);
        $statuses = [];
        if ($this->status_ids && count($this->status_ids)) {
            foreach ($this->status_ids as $sid) {
                $s = StatusService::get($sid, false);
                if ($s && $s['in_reply_to_id'] != null) {
                    $parent = StatusService::get($s['in_reply_to_id'], false);
                    if ($parent) {
                        $s['parent'] = $parent;
                    }
                }
                if ($s) {
                    $statuses[] = $s;
                }
            }
        }
        $res = [
            'id' => $this->id,
            'instance' => $instance,
            'reported' => AccountService::get($this->account_id, true),
            'status_ids' => $this->status_ids,
            'statuses' => $statuses,
            'message' => $this->comment,
            'report_meta' => $this->report_meta,
            'created_at' => $this->created_at?->format('c'),
            'action_taken_at' => $this->action_taken_at?->format('c'),
        ];

        return $res;
    }
}
