<?php

namespace App\Http\Controllers\Settings;

use App\AccountLog;
use App\EmailVerification;
use App\Media;
use App\Profile;
use App\User;
use App\UserFilter;
use App\UserDevice;
use App\Util\Lexer\PrettyNumber;
use Auth;
use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

trait SecuritySettings
{

	public function security()
	{
		$user = Auth::user();

		$activity = AccountLog::whereUserId($user->id)
			->orderBy('created_at', 'desc')
			->limit(20)
			->get();

		$devices = UserDevice::whereUserId($user->id)
			->orderBy('created_at', 'desc')
			->limit(5)
			->get();

		return view('settings.security', compact('activity', 'user', 'devices'));
	}

	public function securityTwoFactorSetup(Request $request)
	{
		$user = Auth::user();
		if($user->{'2fa_enabled'} && $user->{'2fa_secret'}) {
			return redirect(route('account.security'));
		}
		$backups = $this->generateBackupCodes();
		$google2fa = new Google2FA();
		$key = $google2fa->generateSecretKey(32);
		$qrcode = $google2fa->getQRCodeInline(
		    config('pixelfed.domain.app'),
		    $user->email,
		    $key,
		    500
		);
		$user->{'2fa_secret'} = $key;
		$user->{'2fa_backup_codes'} = json_encode($backups);
		$user->save();
		return view('settings.security.2fa.setup', compact('user', 'qrcode', 'backups'));
	}

	protected function generateBackupCodes(): array
	{
		$keys = [];
		for ($i=0; $i < 11; $i++) { 
			$key = str_random(24);
			$keys[] = $key;
		}
		return $keys;
	}

	public function securityTwoFactorSetupStore(Request $request)
	{
		$user = Auth::user();
		if($user->{'2fa_enabled'} && $user->{'2fa_secret'}) {
			abort(403, 'Two factor auth is already setup.');
		}
		$this->validate($request, [
			'code' => 'required|integer'
		]);
		$code = $request->input('code');
		$google2fa = new Google2FA();
		$verify = $google2fa->verifyKey($user->{'2fa_secret'}, $code);
		if($verify) {
			$user->{'2fa_enabled'} = true;
			$user->{'2fa_setup_at'} = Carbon::now();
			$user->save();
			return response()->json(['msg'=>'success']);
		} else {
			return response()->json(['msg'=>'fail'], 403);
		}
	}

	public function securityTwoFactorEdit(Request $request)
	{
		$user = Auth::user();

		if(!$user->{'2fa_enabled'} || !$user->{'2fa_secret'}) {
			abort(403);
		}

		return view('settings.security.2fa.edit', compact('user'));
	}

	public function securityTwoFactorRecoveryCodes(Request $request)
	{
		$user = Auth::user();

		if(!$user->{'2fa_enabled'} || !$user->{'2fa_secret'} || !$user->{'2fa_backup_codes'}) {
			abort(403);
		}
		$codes = json_decode($user->{'2fa_backup_codes'}, true);
		return view('settings.security.2fa.recovery-codes', compact('user', 'codes'));
	}

	public function securityTwoFactorRecoveryCodesRegenerate(Request $request)
	{
		$user = Auth::user();

		if(!$user->{'2fa_enabled'} || !$user->{'2fa_secret'}) {
			abort(403);
		}
		$backups = $this->generateBackupCodes();
		$user->{'2fa_backup_codes'} = json_encode($backups);
		$user->save();
		return redirect(route('settings.security.2fa.recovery'));
	}

	public function securityTwoFactorUpdate(Request $request)
	{
		$user = Auth::user();

		if(!$user->{'2fa_enabled'} || !$user->{'2fa_secret'} || !$user->{'2fa_backup_codes'}) {
			abort(403);
		}

		$this->validate($request, [
			'action'	=> 'required|string|max:12'
		]);
		
		if($request->action !== 'remove') {
			abort(403);
		}

		$user->{'2fa_enabled'} = false;
		$user->{'2fa_secret'} = null;
		$user->{'2fa_backup_codes'} = null;
		$user->{'2fa_setup_at'} = null;
		$user->save();

		return response()->json([
			'msg' => 'Successfully removed 2fa device'
		], 200);
	}
}