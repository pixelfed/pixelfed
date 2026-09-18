<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read bool $recentlyCreated computed/dynamic attribute
 */
class Avatar extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
            'last_fetched_at' => 'datetime',
            'last_processed_at' => 'datetime',
        ];
    }

    protected $visible = [
        'id',
        'profile_id',
        'media_path',
        'size',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
