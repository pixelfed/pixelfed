<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class Page extends Model
{
    const SLUG_ROOT = [
        'site',
        'page',
    ];

    public function url()
    {
        return url($this->slug);
    }

    public function editUrl()
    {
        return url('/i/admin/settings/pages/edit?page='.urlencode($this->slug));
    }
}
