<?php

namespace App\Models;

use App\Profile;
use App\Services\AccountService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminShadowFilter extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function account()
    {
        if ($this->item_type === \App\Profile::class) {
            return AccountService::get($this->item_id, true);
        }

    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'item_id');
    }
}
