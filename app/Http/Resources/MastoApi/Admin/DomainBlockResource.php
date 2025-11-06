<?php

namespace App\Http\Resources\MastoApi\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomainBlockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $severity = 'noop';
        if ($this->resource->banned) {
            $severity = 'suspend';
        } else if ($this->resource->unlisted) {
            $severity = 'silence';
        }

        return [
            'id' => $this->resource->id,
            'domain' => $this->resource->domain,
            // This property is coming in Mastodon 4.3, although it'll only be
            // useful if Pixelfed supports obfuscating domains:
            'digest' => hash('sha256', $this->resource->domain),
            'severity' => $severity,
            // Using the updated_at value as this is going to be the closest to
            // when the domain was banned
            'created_at' => $this->resource->updated_at,
            // We don't have data for these fields
            'reject_media' => false,
            'reject_reports' => false,
            'private_comment' => $this->resource->notes ? join('; ', $this->resource->notes) : null,
            'public_comment' => $this->resource->limit_reason,
            'obfuscate' => false
        ];
    }
}
