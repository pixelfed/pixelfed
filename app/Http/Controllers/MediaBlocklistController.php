<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\MediaBlocklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MediaBlocklistController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            'admin',
        ];
    }

    public function add(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'hash' => 'required|string|size:64',
            'name' => 'nullable|string',
            'description' => 'nullable|string|max:500',
        ]);

        $hash = $request->input('hash');
        abort_if(preg_match('/^([a-f0-9]{64})$/', $hash) !== 1, 400);

        $name = $request->input('name');
        $description = $request->input('description');

        $mb = new MediaBlocklist;
        $mb->sha256 = $hash;
        $mb->name = $name;
        $mb->description = $description;
        $mb->save();

        return redirect('/i/admin/media?layout=banned');
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        $media = MediaBlocklist::findOrFail($request->input('id'));
        $media->delete();

        return redirect('/i/admin/media?layout=banned');
    }
}
