<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Newsroom extends Model
{
    protected $table = 'newsroom';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function permalink()
    {
        $year = $this->published_at->year;
        $month = $this->published_at->format('m');
        $slug = $this->slug;

        return url("/site/newsroom/{$year}/{$month}/{$slug}");
    }

    public function editUrl()
    {
        return url("/i/admin/newsroom/edit/{$this->id}");
    }
}
