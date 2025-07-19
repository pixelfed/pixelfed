<?php

namespace App\Services;

class ProfileService
{
    public static function get($id, $softFail = false)
    {
        return AccountService::get($id, $softFail);
    }

    public static function del($id)
    {
        return AccountService::del($id);
    }
}
