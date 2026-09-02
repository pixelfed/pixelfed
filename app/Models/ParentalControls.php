<?php

namespace App\Models;

use App\Services\AccountService;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $parent_id
 * @property int $child_id
 * @property array|null $permissions
 * @property string|null $verify_code
 * @property Carbon|null $email_sent_at
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $parent
 * @property-read User $child
 */
#[Unguarded]
class ParentalControls extends Model
{
    use HasFactory, SoftDeletes;

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
            } else {
                return [];
            }
        } else {
            return [];
        }
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
