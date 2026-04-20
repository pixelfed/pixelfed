<?php

namespace App\Services;

use App\Status;

class StatusLabelService
{
    public static function get(Status $status)
    {
        return [
            'covid' => false,
        ];
    }
}
