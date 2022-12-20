<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminInvite;
use App\Profile;
use App\User;
use App\Util\Lexer\RestrictedNames;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use App\Services\EmailService;
use App\Http\Controllers\Auth\RegisterController;

class AdminInviteController extends Controller
{
    public function __construct()
    {
        abort_if(!config('instance.admin_invites.enabled'), 404);
    }

    public function index(Request $request, $code)
    {
        if($request->user()) {
            return redirect('/');
        }
        return view('invite.admin_invite', compact('code'));
    }

    public function apiVerifyCheck(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $invite = AdminInvite::whereInviteCode($request->input('token'))->first();
        abort_if(!$invite, 404);
        abort_if($invite->expires_at && $invite->expires_at->lt(now()), 400, 'Invite has expired.');
        abort_if($invite->max_uses && $invite->uses >= $invite->max_uses, 400, 'Maximum invites reached.');
        $res = [
            'message' => $invite->message,
            'max_uses' => $invite->max_uses,
            'sev' => $invite->skip_email_verification
        ];
        return response()->json($res);
    }

    public function apiUsernameCheck(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'username' => 'required'
        ]);

        $invite = AdminInvite::whereInviteCode($request->input('token'))->first();
        abort_if(!$invite, 404);
        abort_if($invite->expires_at && $invite->expires_at->lt(now()), 400, 'Invite has expired.');
        abort_if($invite->max_uses && $invite->uses >= $invite->max_uses, 400, 'Maximum invites reached.');

        $usernameRules = [
            'required',
            'min:2',
            'max:15',
            'unique:users',
            function ($attribute, $value, $fail) {
                $dash = substr_count($value, '-');
                $underscore = substr_count($value, '_');
                $period = substr_count($value, '.');

                if(ends_with($value, ['.php', '.js', '.css'])) {
                    return $fail('Username is invalid.');
                }

                if(($dash + $underscore + $period) > 1) {
                    return $fail('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');
                }

                if (!ctype_alnum($value[0])) {
                    return $fail('Username is invalid. Must start with a letter or number.');
                }

                if (!ctype_alnum($value[strlen($value) - 1])) {
                    return $fail('Username is invalid. Must end with a letter or number.');
                }

                $val = str_replace(['_', '.', '-'], '', $value);
                if(!ctype_alnum($val)) {
                    return $fail('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');
                }

                $restricted = RestrictedNames::get();
                if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
                    return $fail('Username cannot be used.');
                }
            },
        ];

        $rules = ['username' => $usernameRules];
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        return response()->json([]);
    }

    public function apiEmailCheck(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'email' => 'required'
        ]);

        $invite = AdminInvite::whereInviteCode($request->input('token'))->first();
        abort_if(!$invite, 404);
        abort_if($invite->expires_at && $invite->expires_at->lt(now()), 400, 'Invite has expired.');
        abort_if($invite->max_uses && $invite->uses >= $invite->max_uses, 400, 'Maximum invites reached.');

        $emailRules = [
            'required',
            'string',
            'email',
            'max:255',
            'unique:users',
            function ($attribute, $value, $fail) {
                $banned = EmailService::isBanned($value);
                if($banned) {
                    return $fail('Email is invalid.');
                }
            },
        ];

        $rules = ['email' => $emailRules];
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        return response()->json([]);
    }

    public function apiRegister(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'username' => 'required',
            'name' => 'nullable',
            'email' => 'required|email',
            'password' => 'required',
            'password_confirm' => 'required'
        ]);

        $invite = AdminInvite::whereInviteCode($request->input('token'))->firstOrFail();
        abort_if($invite->expires_at && $invite->expires_at->lt(now()), 400, 'Invite expired');
        abort_if($invite->max_uses && $invite->uses >= $invite->max_uses, 400, 'Maximum invites reached.');

        $invite->uses = $invite->uses + 1;

        event(new Registered($user = User::create([
            'name'     => $request->input('name') ?? $request->input('username'),
            'username' => $request->input('username'),
            'email'    => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ])));
        $invite->used_by = array_merge($invite->used_by ?? [], [[
            'user_id' => $user->id,
            'username' => $user->username
        ]]);
        $invite->save();

        if($invite->skip_email_verification) {
            $user->email_verified_at = now();
            $user->save();
        }

        if(Auth::attempt([
            'email' => $request->input('email'),
            'password' => $request->input('password')
        ])) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        } else {
            return response()->json([], 400);
        }
    }
}
