<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth, Hashids;
use App\{Like, Profile, Status, User};

class LikeController extends Controller
{
    public function store(Request $request)
    {
      if(Auth::check() === false) { abort(403); }
      $this->validate($request, [
        'item'    => 'required|integer',
      ]);

      $statusId = $request->item;

      $user = Auth::user();
      $profile = $user->profile;
      $status = Status::findOrFail($statusId);

      if($status->likes()->whereProfileId($profile->id)->count() !== 0) {
        $like = Like::whereProfileId($profile->id)->whereStatusId($status->id)->firstOrFail();
        $like->delete();
        return redirect()->back();
      }

      $like = new Like;
      $like->profile_id = $profile->id;
      $like->status_id = $status->id;
      $like->save();

      return redirect($status->url());
    }
}
