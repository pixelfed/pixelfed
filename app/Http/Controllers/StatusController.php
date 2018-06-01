<?php

namespace App\Http\Controllers;

use Auth, Cache;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use Illuminate\Http\Request;
use App\{Media, Profile, Status, User};
use Vinkla\Hashids\Facades\Hashids;

class StatusController extends Controller
{
    public function show(Request $request, $username, int $id)
    {
      /*
       * Load all required data
       */
      $user = Profile::whereUsername($username)->with('avatar')->firstOrFail();
      $status = Status::whereProfileId($user->id)->with(['firstMedia', 'comments', 'comments.profile'])->findOrFail($id);

      // avoid calling the same profile in the blade template
      $status->profile = $user;

      // load a count non-relationship; this seems to be the most sane solution.
      $status->likesCount;
      /*
       * End load all required data
       */

      if(!$status->media_path && $status->in_reply_to_id) {
        return view('status.reply', compact('user', 'status'));
      }
      return view('status.show', compact('user', 'status'));
    }

    public function store(Request $request)
    {
      if(Auth::check() == false)
      {
        abort(403);
      }

      $user = Auth::user();

      $this->validate($request, [
        'photo'   => 'required|image|max:15000',
        'caption' => 'string|max:150'
      ]);

      $monthHash = hash('sha1', date('Y') . date('m'));
      $userHash = hash('sha1', $user->id . (string) $user->created_at);
      $storagePath = "public/m/{$monthHash}/{$userHash}";
      $path = $request->photo->store($storagePath);
      $profile = $user->profile;

      $status = new Status;
      $status->profile_id = $profile->id;
      $status->caption = $request->caption;
      $status->save();

      $media = new Media;
      $media->status_id = $status->id;
      $media->profile_id = $profile->id;
      $media->user_id = $user->id;
      $media->media_path = $path;
      $media->size = $request->file('photo')->getClientSize();
      $media->mime = $request->file('photo')->getClientMimeType();
      $media->save();
      NewStatusPipeline::dispatch($status, $media);

      // TODO: Parse Caption
      // TODO: Send to subscribers

      return redirect($status->url());
    }
}
