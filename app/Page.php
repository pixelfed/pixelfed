<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    const SLUG_ROOT = [
        'site',
        'page'
    ];

    protected $fillable = ['slug'];

    public function url()
    {
        return url($this->slug);
    }

    public function editUrl()
    {
        return url("/i/admin/settings/pages/edit?page=".urlencode($this->slug));
    }
}
