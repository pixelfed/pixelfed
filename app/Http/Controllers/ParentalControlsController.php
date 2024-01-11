<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ParentalControls;
use App\Models\UserRoles;
use App\Profile;
use App\User;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use App\Services\UserRoleService;
use App\Jobs\ParentalControlsPipeline\DispatchChildInvitePipeline;

class ParentalControlsController extends Controller
{
    public function authPreflight($request, $maxUserCheck = false, $authCheck = true)
    {
        if($authCheck) {
            abort_unless($request->user(), 404);
            abort_unless($request->user()->has_roles === 0, 404);
        }
        abort_unless(config('instance.parental_controls.enabled'), 404);
        if(config_cache('pixelfed.open_registration') == false) {
            abort_if(config('instance.parental_controls.limits.respect_open_registration'), 404);
        }
        if($maxUserCheck == true) {
            $hasLimit = config('pixelfed.enforce_max_users');
            if($hasLimit) {
                $count = User::where(function($q){ return $q->whereNull('status')->orWhereNotIn('status', ['deleted','delete']); })->count();
                $limit = (int) config('pixelfed.max_users');

                abort_if($limit && $limit <= $count, 404);
            }
        }
    }

    public function index(Request $request)
    {
        $this->authPreflight($request);
        $children = ParentalControls::whereParentId($request->user()->id)->latest()->paginate(5);
        return view('settings.parental-controls.index', compact('children'));
    }

    public function add(Request $request)
    {
        $this->authPreflight($request, true);
        return view('settings.parental-controls.add');
    }

    public function view(Request $request, $id)
    {
        $this->authPreflight($request);
        $uid = $request->user()->id;
        $pc = ParentalControls::whereParentId($uid)->findOrFail($id);
        return view('settings.parental-controls.manage', compact('pc'));
    }

    public function update(Request $request, $id)
    {
        $this->authPreflight($request);
        $uid = $request->user()->id;
        $ff = $this->requestFormFields($request);
        $pc = ParentalControls::whereParentId($uid)->findOrFail($id);
        $pc->permissions = $ff;
        $pc->save();

        $roles = UserRoleService::mapActions($pc->child_id, $ff);
        if(isset($roles['account-force-private'])) {
            $c = Profile::whereUserId($pc->child_id)->first();
            $c->is_private = $roles['account-force-private'];
            $c->save();
        }
        UserRoles::whereUserId($pc->child_id)->update(['roles' => $roles]);
        return redirect($pc->manageUrl() . '?permissions');
    }

    public function store(Request $request)
    {
        $this->authPreflight($request, true);
        $this->validate($request, [
            'email' => 'required|email|unique:parental_controls,email|unique:users,email',
        ]);

        $state = $this->requestFormFields($request);

        $pc = new ParentalControls;
        $pc->parent_id = $request->user()->id;
        $pc->email = $request->input('email');
        $pc->verify_code = str_random(32);
        $pc->permissions = $state;
        $pc->save();

        DispatchChildInvitePipeline::dispatch($pc);
        return redirect($pc->manageUrl());
    }

    public function inviteRegister(Request $request, $id, $code)
    {
        if($request->user()) {
            $title = 'You cannot complete this action on this device.';
            $body = 'Please log out or use a different device or browser to complete the invitation registration.';
            return view('errors.custom', compact('title', 'body'));
        }

        $this->authPreflight($request, true, false);

        $pc = ParentalControls::whereRaw('verify_code = BINARY ?', $code)->whereNull(['email_verified_at', 'child_id'])->findOrFail($id);
        abort_unless(User::whereId($pc->parent_id)->exists(), 404);
        return view('settings.parental-controls.invite-register-form', compact('pc'));
    }

    public function inviteRegisterStore(Request $request, $id, $code)
    {
        if($request->user()) {
            $title = 'You cannot complete this action on this device.';
            $body = 'Please log out or use a different device or browser to complete the invitation registration.';
            return view('errors.custom', compact('title', 'body'));
        }

        $this->authPreflight($request, true, false);

        $pc = ParentalControls::whereRaw('verify_code = BINARY ?', $code)->whereNull('email_verified_at')->findOrFail($id);

        $fields = $request->all();
        $fields['email'] = $pc->email;
        $defaults = UserRoleService::defaultRoles();
        $validator = (new RegisterController)->validator($fields);
        $valid = $validator->validate();
        abort_if(!$valid, 404);
        event(new Registered($user = (new RegisterController)->create($fields)));
        sleep(5);
        $user->has_roles = true;
        $user->parent_id = $pc->parent_id;
        if(config('instance.parental_controls.limits.auto_verify_email')) {
            $user->email_verified_at = now();
            $user->save();
            sleep(3);
        } else {
            $user->save();
            sleep(3);
        }
        $ur = UserRoles::updateOrCreate([
            'user_id' => $user->id,
        ],[
            'roles' => UserRoleService::mapInvite($user->id, $pc->permissions)
        ]);
        $pc->email_verified_at = now();
        $pc->child_id = $user->id;
        $pc->save();
        sleep(2);
        Auth::guard()->login($user);

        return redirect('/i/web');
    }

    public function cancelInvite(Request $request, $id)
    {
        $this->authPreflight($request);
        $pc = ParentalControls::whereParentId($request->user()->id)
            ->whereNull(['email_verified_at', 'child_id'])
            ->findOrFail($id);

        return view('settings.parental-controls.delete-invite', compact('pc'));
    }

    public function cancelInviteHandle(Request $request, $id)
    {
        $this->authPreflight($request);
        $pc = ParentalControls::whereParentId($request->user()->id)
            ->whereNull(['email_verified_at', 'child_id'])
            ->findOrFail($id);

        $pc->delete();

        return redirect('/settings/parental-controls');
    }

    public function stopManaging(Request $request, $id)
    {
        $this->authPreflight($request);
        $pc = ParentalControls::whereParentId($request->user()->id)
            ->whereNotNull(['email_verified_at', 'child_id'])
            ->findOrFail($id);

        return view('settings.parental-controls.stop-managing', compact('pc'));
    }

    public function stopManagingHandle(Request $request, $id)
    {
        $this->authPreflight($request);
        $pc = ParentalControls::whereParentId($request->user()->id)
            ->whereNotNull(['email_verified_at', 'child_id'])
            ->findOrFail($id);
        $pc->child()->update([
            'has_roles' => false,
            'parent_id' => null,
        ]);
        $pc->delete();

        return redirect('/settings/parental-controls');
    }

    protected function requestFormFields($request)
    {
        $state = [];
        $fields = [
            'post',
            'comment',
            'like',
            'share',
            'follow',
            'bookmark',
            'story',
            'collection',
            'discovery_feeds',
            'dms',
            'federation',
            'hide_network',
            'private',
            'hide_cw'
        ];

        foreach ($fields as $field) {
            $state[$field] = $request->input($field) == 'on';
        }

        return $state;
    }
}
