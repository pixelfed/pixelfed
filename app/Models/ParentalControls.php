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

    public function child()
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function manageUrl()
    {
        return url('/settings/parental-controls/manage/' . $this->id);
    }
}
