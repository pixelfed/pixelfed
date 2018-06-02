<?php

namespace App\Http\Controllers;

use Auth, Cache;
use App\Jobs\StatusPipeline\{NewStatusPipeline, StatusDelete};
use Illuminate\Http\Request;
use App\{Media, Profile, Status, User};
use Vinkla\Hashids\Facades\Hashids;

class StatusController extends Controller
{
    public function show(Request $request, $username, int $id)
    {
      $user = Profile::whereUsername($username)->firstOrFail();
      $status = Status::whereProfileId($user->id)->findOrFail($id);
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
        'photo'       => 'required|image|max:15000',
        'caption'     => 'string|max:150',
        'description' => 'nullable|string|max:420'
      ]);

      $monthHash = hash('sha1', date('Y') . date('m'));
      $userHash = hash('sha1', $user->id . (string) $user->created_at);
      $storagePath = "public/m/{$monthHash}/{$userHash}";
      $path = $request->photo->store($storagePath);
      $profile = $user->profile;

      $status = new Status;
      $status->profile_id = $profile->id;
      $status->caption = $request->caption;
      $status->description = $request->description;
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

    public function delete(Request $request)
    {
      if(!Auth::check()) {
        abort(403);
      }

      $this->validate($request, [
        'type'  => 'required|string',
        'item'  => 'required|integer|min:1'
      ]);

      $status = Status::findOrFail($request->input('item'));

      if($status->profile_id === Auth::user()->profile->id || Auth::user()->is_admin == true) {
        StatusDelete::dispatch($status);
      }

      return redirect(Auth::user()->url());
    }
}
