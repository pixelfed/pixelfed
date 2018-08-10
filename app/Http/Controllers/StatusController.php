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
        $replies = Status::whereInReplyToId($status->id)->simplePaginate(30);
        return view('status.show', compact('user', 'status', 'replies'));
    }

    public function compose()
    {
        if(Auth::check() == false)
        { 
          abort(403); 
        }
        return view('status.compose');
    }

    public function store(Request $request)
    {
        if(Auth::check() == false)
        { 
          abort(403); 
        }

        $user = Auth::user();

        $size = Media::whereUserId($user->id)->sum('size') / 1000;
        $limit = (int) config('pixelfed.max_account_size');
        if($size >= $limit) {
          return redirect()->back()->with('error', 'You have exceeded your storage limit. Please click <a href="#">here</a> for more info.');
        }

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

    public function storeShare(Request $request)
    {
        $this->validate($request, [
          'item'    => 'required|integer',
        ]);

        $profile = Auth::user()->profile;
        $status = Status::withCount('shares')->findOrFail($request->input('item'));

        $count = $status->shares_count;

        $exists = Status::whereProfileId(Auth::user()->profile->id)
                  ->whereReblogOfId($status->id)
                  ->count();
        if($exists !== 0) {
          $shares = Status::whereProfileId(Auth::user()->profile->id)
                  ->whereReblogOfId($status->id)
                  ->get();
          foreach($shares as $share) {
            $share->delete();
            $count--;
          }
        } else {
          $share = new Status;
          $share->profile_id = $profile->id;
          $share->reblog_of_id = $status->id;
          $share->save();
          $count++;
        }

        if($request->ajax()) {
          $response = ['code' => 200, 'msg' => 'Share saved', 'count' => $count];
        } else {
          $response = redirect($status->url());
        }

        return $response;
    }
}
