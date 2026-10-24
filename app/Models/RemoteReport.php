<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property array<array-key, mixed>|null $status_ids
 * @property string|null $comment
 * @property int|null $account_id
 * @property string|null $uri
 * @property int|null $instance_id
 * @property string|null $action_taken_at
 * @property array<array-key, mixed>|null $report_meta
 * @property array<array-key, mixed>|null $action_taken_meta
 * @property int|null $action_taken_by_account_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereActionTakenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereActionTakenByAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereActionTakenMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereReportMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereStatusIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteReport whereUri($value)
 *
 * @mixin \Eloquent
 */
class RemoteReport extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status_ids' => 'array',
            'action_taken_meta' => 'array',
            'report_meta' => 'array',
        ];
    }
}
