<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;
use App\Services\AccountService;

class ParentalControls extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'permissions' => 'array',
        'email_sent_at' => 'datetime',
        'email_verified_at' => 'datetime'
    ];

    protected $guarded = [];

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
        if($u = $this->child) {
            if($u->profile_id) {
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
        return url('/settings/parental-controls/manage/' . $this->id);
    }

    public function inviteUrl()
    {
        return url('/auth/pci/' . $this->id . '/' . $this->verify_code);
    }
}
