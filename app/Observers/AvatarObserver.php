<?php

namespace App\Observers;

use App\Avatar;
use App\Services\AccountService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;
}
