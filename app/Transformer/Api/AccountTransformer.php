<?php

namespace App\Transformer\Api;

use App\Profile;
use App\Services\AccountService;
use App\Services\PronounService;
use App\User;
use App\UserSetting;
use Cache;
use League\Fractal;

class AccountTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = [
        // 'relationship',
    ];
}
