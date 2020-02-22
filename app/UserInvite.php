<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserInvite extends Model
{
    public function url()
    {
        $path = '/i/invite/code';
        $url = url($path, [$this->key, $this->token]);
        return $url;
    }
}
