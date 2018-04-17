<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\{Media, Status, User};

class StatusController extends Controller
{
    public function store(Request $request)
    {
      if(Auth::check() == false)
      { 
        abort(403); 
      }

      $user = Auth::user();

      $this->validate($request, [
        'photo'   => 'required|image|max:8000',
        'caption' => 'string|max:150'
      ]);
      $monthHash = hash('sha1',date('Y').date('m'));
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
      
      return [$media, $request->all()];
    }
}
