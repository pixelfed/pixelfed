<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $email
 * @property string|null $username
 * @property string|null $password
 * @property string|null $ip_address
 * @property string|null $verify_code
 * @property string|null $reason_to_join
 * @property int|null $invited_by
 * @property int $is_approved
 * @property bool $is_rejected
 * @property bool $is_awaiting_more_info
 * @property bool $user_has_responded
 * @property bool $is_closed
 * @property array<array-key, mixed>|null $autofollow_account_ids
 * @property array<array-key, mixed>|null $admin_notes
 * @property int|null $approved_by_admin_id
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $admin_notified_at
 * @property Carbon|null $action_taken_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereActionTakenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereAdminNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereAdminNotifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereApprovedByAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereAutofollowAccountIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereInvitedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereIsApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereIsAwaitingMoreInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereIsClosed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereIsRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereReasonToJoin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereUserHasResponded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegister whereVerifyCode($value)
 *
 * @mixin \Eloquent
 */
class CuratedRegister extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'autofollow_account_ids' => 'array',
            'admin_notes' => 'array',
            'email_verified_at' => 'datetime',
            'admin_notified_at' => 'datetime',
            'action_taken_at' => 'datetime',
            'user_has_responded' => 'boolean',
            'is_awaiting_more_info' => 'boolean',
            'is_accepted' => 'boolean',
            'is_rejected' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }

    public function adminStatusLabel()
    {
        if ($this->user_has_responded) {
            return '<span class="border border-warning px-3 py-1 rounded text-white font-weight-bold">Awaiting Admin Response</span>';
        }
        if (! $this->email_verified_at) {
            return '<span class="border border-danger px-3 py-1 rounded text-white font-weight-bold">Unverified email</span>';
        }
        if ($this->is_approved) {
            return '<span class="badge badge-success bg-success text-dark">Approved</span>';
        }
        if ($this->is_rejected) {
            return '<span class="badge badge-danger bg-danger text-white">Rejected</span>';
        }
        if ($this->is_awaiting_more_info) {
            return '<span class="border border-info px-3 py-1 rounded text-white font-weight-bold">Awaiting User Response</span>';
        }
        if ($this->is_closed) {
            return '<span class="border border-muted px-3 py-1 rounded text-white font-weight-bold" style="opacity:0.5">Closed</span>';
        }

        return '<span class="border border-success px-3 py-1 rounded text-white font-weight-bold">Open</span>';
    }

    public function emailConfirmUrl()
    {
        return url('/auth/sign_up/confirm?sid='.$this->id.'&code='.$this->verify_code);
    }

    public function emailReplyUrl()
    {
        return url('/auth/sign_up/concierge?sid='.$this->id.'&code='.$this->verify_code.'&sc='.Str::random(8));
    }

    public function adminReviewUrl()
    {
        return url('/i/admin/curated-onboarding/show/'.$this->id);
    }
}
