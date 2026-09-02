<?php

namespace App\Http\Controllers;

use App\Models\MediaTag;
use App\Models\Notification;
use App\Services\MediaTagService;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
class MediaTagController extends Controller
{
    public function usernameLookup(Request $request): void
    {
        abort(404);
    }

    public function untagProfile(Request $request): array
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'status_id' => 'required',
            'profile_id' => 'required',
        ]);

        $user = $request->user();
        $status_id = $request->input('status_id');
        $profile_id = (int) $request->input('profile_id');

        abort_if((int) $user->profile_id !== $profile_id, 400);

        $tag = MediaTag::whereStatusId($status_id)
            ->whereProfileId($profile_id)
            ->first();

        if (! $tag) {
            return [];
        }
        Notification::whereItemType(MediaTag::class)
            ->whereItemId($tag->id)
            ->whereProfileId($profile_id)
            ->whereAction('tagged')
            ->delete();

        MediaTagService::untag($status_id, $profile_id);

        return [200];

    }
}
