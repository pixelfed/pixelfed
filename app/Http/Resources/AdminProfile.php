<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\AccountService;

class AdminProfile extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $res = AccountService::get($this->id, true);
        $res['domain'] = $this->domain;
        $res['status'] = $this->status;
        $res['limits'] = [
            'exist' => $this->cw || $this->unlisted || $this->no_autolink,
            'autocw' => (bool) $this->cw,
            'unlisted' => (bool) $this->unlisted,
            'no_autolink' => (bool) $this->no_autolink,
            'banned' => (bool) $this->status == 'banned'
        ];
        return $res;
    }
}
