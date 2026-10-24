<?php

namespace App\Models;

use App\Services\AccountService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $parent_id
 * @property int|null $child_id
 * @property string|null $email
 * @property string|null $verify_code
 * @property Carbon|null $email_sent_at
 * @property Carbon|null $email_verified_at
 * @property array<array-key, mixed>|null $permissions
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $child
 * @property-read User|null $parent
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls whereChildId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls whereEmailSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls whereVerifyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ParentalControls withoutTrashed()
 *
 * @mixin \Eloquent
 */
class ParentalControls extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'email_sent_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function child()
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function childAccount()
    {
        if ($u = $this->child) {
            if ($u->profile_id) {
                return AccountService::get($u->profile_id, true);
            }

            return [];
        }

        return [];
    }

    public function manageUrl()
    {
        return url('/settings/parental-controls/manage/'.$this->id);
    }

    public function inviteUrl()
    {
        return url('/auth/pci/'.$this->id.'/'.$this->verify_code);
    }
}
