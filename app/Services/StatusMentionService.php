<?php

namespace App\Services;

use App\Mention;

class StatusMentionService
{
    public static function get($id)
    {
        return Mention::whereStatusId($id)
            ->get()
            ->map(function ($mention) {
                return AccountService::get($mention->profile_id);
            })->filter(function ($mention) {
                return $mention;
            })
            ->values()
            ->toArray();
    }
}
