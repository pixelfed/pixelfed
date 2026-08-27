<?php

namespace App\Http\Controllers;

use App\Mail\UserInviteMail;
use App\Services\EmailService;
use App\User;
use App\UserInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserInviteController extends Controller
{
    public function create(Request $request)
    {
        abort_if(! config('pixelfed.user_invites.enabled'), 404);
        abort_unless($request->user() !== null, 403);

        return view('settings.invites.create');
    }

    public function show(Request $request)
    {
        abort_if(! config('pixelfed.user_invites.enabled'), 404);
        abort_unless($request->user() !== null, 403);
        $invites = UserInvite::whereUserId(Auth::id())->paginate(10);
        $limit = config('pixelfed.user_invites.limit.total');
        $used = UserInvite::whereUserId(Auth::id())->count();

        return view('settings.invites.home', compact('invites', 'limit', 'used'));
    }

    public function store(Request $request)
    {
        abort_if(! config('pixelfed.user_invites.enabled'), 404);
        abort_unless($request->user() !== null, 403);
        $this->validate($request, [
            'email' => 'required|email|unique:users|unique:user_invites',
            'message' => 'nullable|string|max:500',
            'tos' => 'required|accepted',
        ]);

        $email = $request->input('email');

        $userCount = UserInvite::whereUserId(Auth::id())->count();
        $userLimit = config('pixelfed.user_invites.limit.total');

        abort_if($userCount >= $userLimit, 400);
        abort_if(EmailService::isBanned($email), 400);
        abort_if(User::whereEmail($email)->exists(), 400);

        $invite = new UserInvite;
        $invite->user_id = Auth::id();
        $invite->profile_id = $request->user()->profile_id;
        $invite->email = $email;
        $invite->message = $request->input('message');
        $invite->key = Str::random(random_int(6, 9)).'_'.Str::random(random_int(14, 20)).'_'.Str::random(random_int(32, 64));
        $invite->token = Str::random(random_int(32, 69));
        $invite->save();

        // Mail::to($email)->send(new UserInviteMail($invite));

        return redirect(route('settings.invites'));
    }

    public function redeem(Request $request, $key, $token)
    {
        abort_if(! config('pixelfed.user_invites.enabled'), 404);
        // if($request->user()) {
        //  return redirect('/');
        // }
        $invite = UserInvite::where('key', $key)
            ->where('token', $token)
            ->first();

        return view('invite.landing', compact('invite'));
        // return response()->json([
        //  'key' => $key,
        //  'token' => $token,
        //  'invite' => $invite->url()
        // ], 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    public function redeemVerify(Request $request, $key, $token)
    {
        abort_if(! config('pixelfed.user_invites.enabled'), 404);
        // if($request->user()) {
        //  return redirect('/');
        // }

        $this->validate($request, [
            // 'email' => 'required|email|exists:user_invites,email',
            'email' => 'required|email',
        ]);

        $invite = UserInvite::where('key', $key)
            ->where('token', $token)
            ->where('email', $request->input('email'))
            ->firstOrFail();

        session([
            'invite_verified' => true,
            'invite_id' => $invite->id,
        ]);

        return redirect('/i/invite/verified');
    }

    public function verified(Request $request)
    {
        abort_if(! config('pixelfed.user_invites.enabled'), 404);
        // if($request->user()) {
        //  return redirect('/');
        // }
        abort_if(! $request->session()->has('invite_verified'), 404);
        $invite = UserInvite::find($request->session()->get('invite_id'));

        return view('invite.verified', compact('invite'));
    }
}
