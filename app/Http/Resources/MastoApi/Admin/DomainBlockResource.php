<?php

namespace App\Http\Resources\MastoApi\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $domain
 * @property bool $banned
 * @property bool $unlisted
 * @property \Illuminate\Support\Carbon $updated_at
 * @property array|null $notes
 * @property string|null $limit_reason
 */
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
        if ($this->banned) {
            $severity = 'suspend';
        } else if ($this->unlisted) {
            $severity = 'silence';
        }

        return [
            'id' => $this->id,
            'domain' => $this->domain,
            // This property is coming in Mastodon 4.3, although it'll only be
            // useful if Pixelfed supports obfuscating domains:
            'digest' => hash('sha256', $this->domain),
            'severity' => $severity,
            // Using the updated_at value as this is going to be the closest to
            // when the domain was banned
            'created_at' => $this->updated_at,
            // We don't have data for these fields
            'reject_media' => false,
            'reject_reports' => false,
            'private_comment' => $this->notes ? join('; ', $this->notes) : null,
            'public_comment' => $this->limit_reason,
            'obfuscate' => false
        ];
    }
}
