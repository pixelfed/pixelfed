<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminInviteEmail;
use App\Models\AdminInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminUserInviteController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
        $this->middleware('dangerzone');
        $this->middleware('twofactor');
    }

    public function index()
    {
        $invites = AdminInvite::orderByDesc('created_at')->simplePaginate(25);

        return view('admin.users.invites.home', ['invites' => $invites]);
    }

    public function create()
    {
        return view('admin.users.invites.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'message' => 'nullable|string|max:1000',
            'email' => 'nullable|email:rfc,dns',
            'max_uses' => 'required|integer|min:0',
            'expires_in' => 'required|integer|min:0',
            'skip_email_verification' => 'sometimes|boolean',
        ]);

        $invite = AdminInvite::create([
            'name' => $request->input('name') ?? 'Untitled Invite',
            'description' => $request->input('description'),
            'message' => $request->input('message'),
            'max_uses' => $request->integer('max_uses'),
            'skip_email_verification' => $request->boolean('skip_email_verification'),
            'expires_at' => $request->integer('expires_in') > 0
                ? now()->addDays($request->integer('expires_in'))
                : null,
            'admin_user_id' => $request->user()->id,
        ]);

        if ($request->input('email') !== null) {
            Mail::to($request->input('email'))->queue(new AdminInviteEmail($invite));
        }

        return redirect(route('admin.users.invites.index'))
            ->with('status', 'Invite created <a href="'.$invite->url().'" class="text-white" style="text-decoration: underline;">'.$invite->url().'</a>.');
    }

    public function expire(AdminInvite $invite)
    {
        $invite->max_uses = 1;
        $invite->expires_at = now()->subHours(2);
        $invite->save();

        return ['status' => 200, 'message' => 'Successfully expired invite!'];
    }
}
