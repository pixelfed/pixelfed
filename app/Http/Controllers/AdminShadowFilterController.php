<?php

namespace App\Http\Controllers;

use App\Models\AdminShadowFilter;
use App\Models\Profile;
use App\Services\AccountService;
use App\Services\AdminShadowFilterService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AdminShadowFilterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'admin']),
        ];
    }

    public function home(Request $request)
    {
        $filter = $request->input('filter');
        $searchQuery = $request->input('q');
        $filters = AdminShadowFilter::whereHas('profile')
            ->when($filter, function ($q, $filter) {
                if ($filter == 'all') {
                    return $q;
                } elseif ($filter == 'inactive') {
                    return $q->whereActive(false);
                } else {
                    return $q;
                }
            }, function ($q, $filter) {
                return $q->whereActive(true);
            })
            ->when($searchQuery, function ($q, $searchQuery) {
                $ids = Profile::where('username', 'like', '%'.$searchQuery.'%')
                    ->limit(100)
                    ->pluck('id')
                    ->toArray();

                return $q->whereIn('item_type', ['App\Profile', \App\Models\Profile::class])->whereIn('item_id', $ids);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.asf.home', compact('filters'));
    }

    public function create(Request $request)
    {
        return view('admin.asf.create');
    }

    public function edit(Request $request, $id)
    {
        $filter = AdminShadowFilter::findOrFail($id);
        $profile = AccountService::get($filter->item_id);

        return view('admin.asf.edit', compact('filter', 'profile'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
            'active' => 'sometimes',
            'note' => 'sometimes',
            'hide_from_public_feeds' => 'sometimes',
        ]);

        $profile = Profile::whereUsername($request->input('username'))->first();

        if (! $profile) {
            return back()->withErrors(['Invalid account']);
        }

        if ($profile->user && $profile->user->is_admin) {
            return back()->withErrors(['Cannot filter an admin account']);
        }

        $active = $request->has('active') && $request->has('hide_from_public_feeds');

        AdminShadowFilter::updateOrCreate([
            'item_id' => $profile->id,
            'item_type' => get_class($profile),
        ], [
            'is_local' => $profile->domain === null,
            'note' => $request->input('note'),
            'hide_from_public_feeds' => $request->has('hide_from_public_feeds'),
            'admin_id' => $request->user()->profile_id,
            'active' => $active,
        ]);

        AdminShadowFilterService::refresh();

        return redirect('/i/admin/asf/home');
    }

    public function storeEdit(Request $request, $id)
    {
        $this->validate($request, [
            'active' => 'sometimes',
            'note' => 'sometimes',
            'hide_from_public_feeds' => 'sometimes',
        ]);

        $filter = AdminShadowFilter::findOrFail($id);

        $profile = Profile::findOrFail($filter->item_id);

        if ($profile->user && $profile->user->is_admin) {
            return back()->withErrors(['Cannot filter an admin account']);
        }

        $active = $request->has('active');
        $filter->active = $active;
        $filter->hide_from_public_feeds = $request->has('hide_from_public_feeds');
        $filter->note = $request->input('note');
        $filter->save();

        AdminShadowFilterService::refresh();

        return redirect('/i/admin/asf/home');
    }
}
