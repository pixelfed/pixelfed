<?php

namespace App\Http\Controllers\Settings;

use App\Models\AccountLog;
use App\Models\UserDevice;
use App\Services\PendingLoginService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

trait SecuritySettings
{
    public function security(Request $request)
    {
        $user = $request->user();

        $activity = AccountLog::whereUserId($user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $devices = UserDevice::whereUserId($user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('settings.security', ['activity' => $activity, 'user' => $user, 'devices' => $devices]);
    }

    public function securityTwoFactorSetup(Request $request)
    {
        $user = $request->user();
        if ($user->{'2fa_enabled'} && $user->{'2fa_secret'}) {
            return redirect(route('settings.security'));
        }
        // $google2fa = new Google2FA();
        $google2fa = app(Google2FA::class);
        $key = $google2fa->generateSecretKey(32);
        $qrcode = $google2fa->getQRCodeUrl(
            config('pixelfed.domain.app'),
            $user->email,
            $key
        );

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(400),
                new SvgImageBackEnd
            )
        );
        $qrcode = $writer->writeString($qrcode);
        // Only the secret is provisioned on GET (the user must see it to add
        // the authenticator). Backup codes are NOT generated or rendered here:
        // they are sensitive recovery data and are created + returned only
        // after the TOTP code is verified, which also avoids regenerating them
        // (and invalidating copied ones) on every page refresh.
        $user->{'2fa_secret'} = $key;
        $user->save();

        return view('settings.security.2fa.setup', ['user' => $user, 'qrcode' => $qrcode]);
    }

    /**
     * @return mixed[]
     */
    protected function generateBackupCodes(): array
    {
        $keys = [];
        for ($i = 0; $i < 11; $i++) {
            $key = Str::random(24);
            $keys[] = $key;
        }

        return $keys;
    }

    public function securityTwoFactorSetupStore(Request $request)
    {
        $user = $request->user();
        if ($user->{'2fa_enabled'} && $user->{'2fa_secret'}) {
            abort(403, 'Two factor auth is already setup.');
        }
        $this->validate($request, [
            'code' => 'required|digits:6',
        ]);
        $code = $request->input('code');
        $google2fa = new Google2FA;
        $verify = $google2fa->verifyKey($user->{'2fa_secret'}, $code);
        if ($verify) {
            // Generate and persist backup codes only now that possession of the
            // authenticator is proven, and return them once so the client can
            // display them. They are not rendered on the setup GET page.
            $backups = $this->generateBackupCodes();
            $user->{'2fa_enabled'} = true;
            $user->{'2fa_backup_codes'} = json_encode($backups);
            $user->{'2fa_setup_at'} = now();
            $user->save();

            return response()->json(['msg' => 'success', 'backup_codes' => $backups]);
        }

        return response()->json(['msg' => 'fail'], 403);
    }

    public function securityTwoFactorEdit(Request $request)
    {
        $user = $request->user();

        if (! $user->{'2fa_enabled'} || ! $user->{'2fa_secret'}) {
            abort(403);
        }

        return view('settings.security.2fa.edit', ['user' => $user]);
    }

    public function securityTwoFactorRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (! $user->{'2fa_enabled'} || ! $user->{'2fa_secret'} || ! $user->{'2fa_backup_codes'}) {
            abort(403);
        }
        $codes = json_decode($user->{'2fa_backup_codes'}, true);

        return view('settings.security.2fa.recovery-codes', ['user' => $user, 'codes' => $codes]);
    }

    public function securityTwoFactorRecoveryCodesRegenerate(Request $request)
    {
        $user = $request->user();

        if (! $user->{'2fa_enabled'} || ! $user->{'2fa_secret'}) {
            abort(403);
        }
        $backups = $this->generateBackupCodes();
        $user->{'2fa_backup_codes'} = json_encode($backups);
        $user->save();

        return redirect(route('settings.security.2fa.recovery'));
    }

    public function securityTwoFactorUpdate(Request $request)
    {
        $user = $request->user();

        if (! $user->{'2fa_enabled'} || ! $user->{'2fa_secret'} || ! $user->{'2fa_backup_codes'}) {
            abort(403);
        }

        $this->validate($request, [
            'action' => 'required|string|max:12',
            'code' => 'required|string|min:6|max:24',
        ]);

        if ($request->action !== 'remove') {
            abort(403);
        }

        // Removing 2FA is a security-critical change: require proof of the second factor (a current TOTP code or an unused backup code).
        if (! PendingLoginService::verifyCode($user, $request->input('code'))) {
            return response()->json(['msg' => 'Invalid 2FA code'], 403);
        }

        $user->{'2fa_enabled'} = false;
        $user->{'2fa_secret'} = null;
        $user->{'2fa_backup_codes'} = null;
        $user->{'2fa_setup_at'} = null;
        $user->save();

        $log = new AccountLog;
        $log->user_id = $user->id;
        $log->item_id = $user->id;
        $log->item_type = 'App\Models\User';
        $log->action = 'account.security.2fa.remove';
        $log->message = 'Two-factor authentication removed';
        $log->link = null;
        $log->ip_address = $request->ip();
        $log->user_agent = $request->userAgent();
        $log->save();

        return response()->json([
            'msg' => 'Successfully removed 2fa device',
        ], 200);
    }
}
