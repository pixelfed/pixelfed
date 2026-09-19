<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $register_id
 * @property int|null $admin_id
 * @property int|null $reply_to_id
 * @property string|null $secret_code
 * @property string|null $type
 * @property string|null $title
 * @property string|null $link
 * @property string|null $message
 * @property array<array-key, mixed>|null $metadata
 * @property int $from_admin
 * @property int $from_user
 * @property int $admin_only_view
 * @property int $action_required
 * @property Carbon|null $admin_notified_at
 * @property Carbon|null $action_taken_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CuratedRegister|null $application
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereActionRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereActionTakenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereAdminNotifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereAdminOnlyView($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereFromAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereFromUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereRegisterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereReplyToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereSecretCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterActivity whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CuratedRegisterActivity extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'admin_notified_at' => 'datetime',
            'action_taken_at' => 'datetime',
        ];
    }

    public function application()
    {
        return $this->belongsTo(CuratedRegister::class, 'register_id');
    }

    public function emailReplyUrl()
    {
        return url('/auth/sign_up/concierge?sid='.$this->register_id.'&id='.$this->id.'&code='.$this->secret_code);
    }

    public function adminReviewUrl()
    {
        $url = '/i/admin/curated-onboarding/show/'.$this->register_id.'/?ah='.$this->id;
        if ($this->reply_to_id) {
            $url .= '&rtid='.$this->reply_to_id;
        }

        return url($url);
    }
}
