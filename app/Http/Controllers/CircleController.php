<?php

namespace App\Http\Controllers;

use App\Models\Circle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Validation\Rule;

#[Middleware('auth')]
class CircleController extends Controller
{
    public function home(Request $request): View
    {
        $circles = Circle::whereProfileId($request->user()->profile->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('account.circles.home', compact('circles'));
    }

    public function create(Request $request): View
    {
        return view('account.circles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required|string|min:1',
            'description' => 'nullable|string|max:255',
            'scope' => [
                'required',
                'string',
                Rule::in([
                    'public',
                    'private',
                    'unlisted',
                    'exclusive',
                ]),
            ],
        ]);

        $circle = Circle::firstOrCreate([
            'profile_id' => $request->user()->profile->id,
            'name' => $request->input('name'),
        ], [
            'description' => $request->input('description'),
            'scope' => $request->input('scope'),
            'active' => false,
        ]);

        return redirect(route('account.circles'));
    }

    public function show(Request $request, $id): View
    {
        $circle = Circle::findOrFail($id);

        return view('account.circles.show', compact('circle'));
    }
}
