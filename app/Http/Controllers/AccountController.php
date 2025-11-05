<?php

namespace App\Http\Controllers;

use App\EmailVerification;
use App\Follower;
use App\FollowRequest;
use App\Jobs\FollowPipeline\FollowAcceptPipeline;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Jobs\FollowPipeline\FollowRejectPipeline;
use App\Mail\ConfirmEmail;
use App\Notification;
use App\Profile;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\NotificationService;
use App\Services\RelationshipService;
use App\Services\UserFilterService;
use App\Transformer\Api\Mastodon\v1\AccountTransformer;
use App\User;
use App\UserFilter;
use Auth;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use Mail;
use PragmaRX\Google2FA\Google2FA;

class AccountController extends Controller
{
    protected $filters = [
        'user.mute',
        'user.block',
    ];

    const FILTER_LIMIT_MUTE_TEXT = 'You cannot mute more than ';

    const FILTER_LIMIT_BLOCK_TEXT = 'You cannot block more than ';

    protected function twoFactorBackupCheck($request, $code, User $user)
    {
        $backupCodes = $user->{'2fa_backup_codes'};
        if ($backupCodes) {
            $codes = json_decode($backupCodes, true);
            foreach ($codes as $c) {
                if (hash_equals($c, $code)) {
                    $codes = array_flatten(array_diff($codes, [$code]));
                    $user->{'2fa_backup_codes'} = json_encode($codes);
                    $user->save();
                    $request->session()->push('2fa.session.active', true);

                    return true;
                }
            }

            return false;
        } else {
            return false;
        }
    }
}
