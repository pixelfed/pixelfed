<?php

namespace App\Http\Controllers;

use Auth, Cache;
use App\Jobs\StatusPipeline\{NewStatusPipeline, StatusDelete};
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use Illuminate\Http\Request;
use App\{Media, Profile, Status, User};
use Vinkla\Hashids\Facades\Hashids;

class StatusController extends Controller
{
    public function show(Request $request, $username, int $id)
    {
      $user = Profile::whereUsername($username)->firstOrFail();
      $status = Status::whereProfileId($user->id)
              ->withCount(['likes', 'comments', 'media'])
              ->findOrFail($id);
      if(!$status->media_path && $status->in_reply_to_id) {
        return redirect($status->url());
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
        'photo.*'   => 'required|mimes:jpeg,png,bmp,gif|max:' . config('pixelfed.max_photo_size'),
        'caption' => 'string|max:' . config('pixelfed.max_caption_length'),
        'cw'      => 'nullable|string',
        'filter_class' => 'nullable|string',
        'filter_name' => 'nullable|string',
      ]);

      if(count($request->file('photo')) > config('pixelfed.max_album_length')) {
        return redirect()->back()->with('error', 'Too many files, max limit per post: ' . config('pixelfed.max_album_length'));
      }

      $cw = $request->filled('cw') && $request->cw == 'on' ? true : false;
      $monthHash = hash('sha1', date('Y') . date('m'));
      $userHash = hash('sha1', $user->id . (string) $user->created_at);
      $profile = $user->profile;

      $status = new Status;
      $status->profile_id = $profile->id;
      $status->caption = strip_tags($request->caption);
      $status->is_nsfw = $cw;

      $status->save();

      $photos = $request->file('photo');
      $order = 1;
      foreach ($photos as $k => $v) {
        $storagePath = "public/m/{$monthHash}/{$userHash}";
        $path = $v->store($storagePath);
        $media = new Media;
        $media->status_id = $status->id;
        $media->profile_id = $profile->id;
        $media->user_id = $user->id;
        $media->media_path = $path;
        $media->size = $v->getClientSize();
        $media->mime = $v->getClientMimeType();
        $media->filter_class = $request->input('filter_class');
        $media->filter_name = $request->input('filter_name');
        $media->order = $order;
        $media->save();
        ImageOptimize::dispatch($media);
        $order++;
      }

      NewStatusPipeline::dispatch($status);

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
