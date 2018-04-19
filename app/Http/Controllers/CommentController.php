<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\{Comment, Profile, Status};
use Vinkla\Hashids\Facades\Hashids;

class CommentController extends Controller
{
    public function store(Request $request)
    {
      if(Auth::check() === false) { abort(403); }
      $this->validate($request, [
        'item'    => 'required|alpha_num',
        'comment' => 'required|string|max:500'
      ]);

      try {
        $statusId = Hashids::decode($request->item)[0];
      } catch (Exception $e) {
        abort(500);
      }

      $user = Auth::user();
      $profile = $user->profile;
      $status = Status::findOrFail($statusId);

      $comment = new Comment;
      $comment->profile_id = $profile->id;
      $comment->user_id = $user->id;
      $comment->status_id = $status->id;
      $comment->comment = e($request->comment);
      $comment->rendered = e($request->comment);
      $comment->is_remote = false;
      $comment->entities = null;
      $comment->save();

      return redirect($status->url());
    }
}
