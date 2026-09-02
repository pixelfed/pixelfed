<?php

namespace App\Http\Controllers;

use App\Models\AccountInterstitial;
use App\Models\Status;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
class AccountInterstitialController extends Controller
{
    public function get(Request $request): RedirectResponse|View
    {
        $interstitial = $request->user()
            ->interstitials()
            ->whereNull('read_at')
            ->first();

        if (! $interstitial) {
            $user = $request->user();
            $user->has_interstitial = false;
            $user->save();

            return redirect('/');
        }

        $meta = json_decode($interstitial->meta);
        $view = $interstitial->view;

        return view($view, compact('interstitial', 'meta'));
    }

    public function read(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'id' => 'required',
            'type' => 'required|in:post.cw,post.removed,post.unlist,post.autospam',
            'action' => 'required|in:appeal,confirm',
            'appeal_message' => 'nullable|max:500',
        ]);

        $redirect = '/';

        $id = decrypt($request->input('id'));
        $action = $request->input('action');
        $user = $request->user();

        $ai = AccountInterstitial::whereUserId($user->id)
            ->whereType($request->input('type'))
            ->findOrFail($id);

        if ($action == 'appeal') {
            $ai->appeal_requested_at = now();
            $ai->appeal_message = $request->input('appeal_message');
        }

        $ai->read_at = now();
        $ai->save();

        $more = AccountInterstitial::whereUserId($user->id)
            ->whereNull('read_at')
            ->exists();

        if (! $more) {
            $user->has_interstitial = false;
            $user->save();
        }

        if (in_array($ai->type, ['post.cw', 'post.unlist'])) {
            $redirect = Status::findOrFail($ai->item_id)->url();
        }

        return redirect($redirect);
    }
}
