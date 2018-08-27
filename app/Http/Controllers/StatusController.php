<?php

namespace App\Http\Controllers;

use Auth, Cache;
use League\Fractal;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;
use App\{Media, Profile, Status, User};
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Transformer\ActivityPub\StatusTransformer;
use App\Jobs\StatusPipeline\{NewStatusPipeline, StatusDelete};

class StatusController extends Controller
{
    public function show(Request $request, $username, int $id)
    {
        $user = Profile::whereUsername($username)->firstOrFail();

        $status = Status::whereProfileId($user->id)
                ->withCount(['likes', 'comments', 'media'])
                ->findOrFail($id);

        if($request->wantsJson() && config('pixelfed.activitypub_enabled')) {
          return $this->showActivityPub($request, $status);
        }

        $template = $this->detectTemplate($status);

        $replies = Status::whereInReplyToId($status->id)->simplePaginate(30);

        return view($template, compact('user', 'status', 'replies'));
    }

    protected function detectTemplate($status)
    {
        $template = Cache::rememberForever('template:status:type:'.$status->id, function () use($status) {
          $template = 'status.show.photo';
          if(!$status->media_path && $status->in_reply_to_id) {
            $template = 'status.reply';
          }
          if($status->media->count() > 1) {
            $template = 'status.show.album';
          }
          if($status->viewType() == 'video') {
            $template = 'status.show.video';
          }
          return $template;
        });
        return $template;
    }

    public function compose()
    {
        $this->authCheck();
        return view('status.compose');
    }

    public function store(Request $request)
    {
        $this->authCheck();
        $user = Auth::user();

        $size = Media::whereUserId($user->id)->sum('size') / 1000;
        $limit = (int) config('pixelfed.max_account_size');
        if($size >= $limit) {
          return redirect()->back()->with('error', 'You have exceeded your storage limit. Please click <a href="#">here</a> for more info.');
        }

        $this->validate($request, [
          'photo.*'   => 'required|mimes:jpeg,png,gif|max:' . config('pixelfed.max_photo_size'),
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
          $hash = \hash_file('sha256', $v);
          $media = new Media;
          $media->status_id = $status->id;
          $media->profile_id = $profile->id;
          $media->user_id = $user->id;
          $media->media_path = $path;
          $media->original_sha256 = $hash;
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

    public function showActivityPub(Request $request, $status)
    {
      $fractal = new Fractal\Manager();
      $resource = new Fractal\Resource\Item($status, new StatusTransformer);
      $res = $fractal->createData($resource)->toArray();
      return response(json_encode($res['data']))->header('Content-Type', 'application/activity+json');
    }

    public function edit(Request $request, $username, $id)
    {
        $this->authCheck();
        $user = Auth::user()->profile;
        $status = Status::whereProfileId($user->id)
                ->with(['media'])
                ->findOrFail($id);
        return view('status.edit', compact('user', 'status'));
    }


    public function editStore(Request $request, $username, $id)
    {
        $this->authCheck();
        $user = Auth::user()->profile;
        $status = Status::whereProfileId($user->id)
                ->with(['media'])
                ->findOrFail($id);

        $this->validate($request, [
          'id' => 'required|integer|min:1',
          'caption' => 'nullable',
          'filter' => 'nullable|alpha_dash|max:30'
        ]);

        $id = $request->input('id');
        $caption = $request->input('caption');
        $filter = $request->input('filter');

        $media = Media::whereProfileId($user->id)
            ->whereStatusId($status->id)
            ->find($id);

        $changed = false;

        if($media->caption != $caption) {
          $media->caption = $caption;
          $changed = true;
        }

        if($media->filter_class != $filter) {
          $media->filter_class = $filter;
          $changed = true;
        }

        if($changed === true) {
          $media->save();
        }
        return response()->json([], 200);
    }

    protected function authCheck()
    {
        if(Auth::check() == false)
        { 
          abort(403); 
        }
    }
}
