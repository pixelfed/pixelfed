<?php

namespace App\Models;

use App\Services\AccountService;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class AdminShadowFilter extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function account()
    {
        if (in_array($this->item_type, ['App\Profile', \App\Models\Profile::class])) {
            return AccountService::get($this->item_id, true);
        }
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'item_id');
    }
}
