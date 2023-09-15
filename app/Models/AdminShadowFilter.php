<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\AccountService;

class AdminShadowFilter extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function account()
    {
        if($this->item_type === 'App\Profile') {
            return AccountService::get($this->item_id, true);
        }

        return;
    }
}
