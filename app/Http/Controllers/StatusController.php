<?php

namespace App\Http\Controllers;

use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Jobs\SharePipeline\SharePipeline;
use App\Media;
use App\Profile;
use App\Status;
use App\Transformer\ActivityPub\StatusTransformer;
use App\Transformer\ActivityPub\Verb\Note;
use App\User;
use Auth;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use App\Util\Media\Filter;

class StatusController extends Controller
{
    public function show(Request $request, $username, int $id)
    {
        $user = Profile::whereNull('domain')->whereUsername($username)->firstOrFail();

        if($user->status != null) {
            return ProfileController::accountCheck($user);
        }

        $status = Status::whereProfileId($user->id)
                ->whereNotIn('visibility',['draft','direct'])
                ->findOrFail($id);

        if($status->uri) {
            $url = $status->uri;
            if(ends_with($url, '/activity')) {
                $url = str_replace('/activity', '', $url);
            }
            return redirect($url);
        }

        if($status->visibility == 'private' || $user->is_private) {
            if(!Auth::check()) {
                abort(403);
            }
            $pid = Auth::user()->profile;
            if($user->followedBy($pid) == false && $user->id !== $pid->id) {
                abort(403);
            }
        }

        if ($request->wantsJson() && config('pixelfed.activitypub_enabled')) {
            return $this->showActivityPub($request, $status);
        }

        $template = $status->in_reply_to_id ? 'status.reply' : 'status.show';
        return view($template, compact('user', 'status'));
    }

    public function showObject(Request $request, $username, int $id)
    {
        $user = Profile::whereNull('domain')->whereUsername($username)->firstOrFail();

        if($user->status != null) {
            return ProfileController::accountCheck($user);
        }

        $status = Status::whereProfileId($user->id)
                ->whereNotIn('visibility',['draft','direct'])
                ->findOrFail($id);

        if($status->uri) {
            $url = $status->uri;
            if(ends_with($url, '/activity')) {
                $url = str_replace('/activity', '', $url);
            }
            return redirect($url);
        }

        if($status->visibility == 'private' || $user->is_private) {
            if(!Auth::check()) {
                abort(403);
            }
            $pid = Auth::user()->profile;
            if($user->followedBy($pid) == false && $user->id !== $pid->id) {
                abort(403);
            }
        }

        return $this->showActivityPub($request, $status);
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
        if ($size >= $limit) {
            return redirect()->back()->with('error', 'You have exceeded your storage limit. Please click <a href="#">here</a> for more info.');
        }

        $this->validate($request, [
          'photo.*'      => 'required|mimetypes:' . config('pixelfed.media_types').'|max:' . config('pixelfed.max_photo_size'),
          'caption'      => 'nullable|string|max:'.config('pixelfed.max_caption_length'),
          'cw'           => 'nullable|string',
          'filter_class' => 'nullable|alpha_dash|max:30',
          'filter_name'  => 'nullable|string',
          'visibility'   => 'required|string|min:5|max:10',
        ]);

        if (count($request->file('photo')) > config('pixelfed.max_album_length')) {
            return redirect()->back()->with('error', 'Too many files, max limit per post: '.config('pixelfed.max_album_length'));
        }
        $cw = $request->filled('cw') && $request->cw == 'on' ? true : false;
        $monthHash = hash('sha1', date('Y').date('m'));
        $userHash = hash('sha1', $user->id.(string) $user->created_at);
        $profile = $user->profile;
        $visibility = $this->validateVisibility($request->visibility);

        $cw = $profile->cw == true ? true : $cw;
        $visibility = $profile->unlisted == true && $visibility == 'public' ? 'unlisted' : $visibility;

        $status = new Status();
        $status->profile_id = $profile->id;
        $status->caption = strip_tags($request->caption);
        $status->is_nsfw = $cw;

        // TODO: remove deprecated visibility in favor of scope
        $status->visibility = $visibility;
        $status->scope = $visibility;

        $status->save();

        $photos = $request->file('photo');
        $order = 1;
        $mimes = [];
        $medias = 0;

        foreach ($photos as $k => $v) {

            $allowedMimes = explode(',', config('pixelfed.media_types'));
            if(in_array($v->getMimeType(), $allowedMimes) == false) {
                continue;
            }
            $filter_class = $request->input('filter_class');
            $filter_name = $request->input('filter_name');

            $storagePath = "public/m/{$monthHash}/{$userHash}";
            $path = $v->store($storagePath);
            $hash = \hash_file('sha256', $v);
            $media = new Media();
            $media->status_id = $status->id;
            $media->profile_id = $profile->id;
            $media->user_id = $user->id;
            $media->media_path = $path;
            $media->original_sha256 = $hash;
            $media->size = $v->getSize();
            $media->mime = $v->getMimeType();
            
            $media->filter_class = in_array($filter_class, Filter::classes()) ? $filter_class : null;
            $media->filter_name = in_array($filter_name, Filter::names()) ? $filter_name : null;
            $media->order = $order;
            $media->save();
            array_push($mimes, $media->mime);
            ImageOptimize::dispatch($media);
            $order++;
            $medias++;
        }

        if($medias == 0) {
            $status->delete();
            return;
        }
        $status->type = (new self)::mimeTypeCheck($mimes);
        $status->save();

        NewStatusPipeline::dispatch($status);

        // TODO: Send to subscribers

        return redirect($status->url());
    }

    public function delete(Request $request)
    {
        $this->authCheck();

        $this->validate($request, [
          'item'  => 'required|integer|min:1',
        ]);

        $status = Status::findOrFail($request->input('item'));

        if ($status->profile_id === Auth::user()->profile->id || Auth::user()->is_admin == true) {
            StatusDelete::dispatch($status);
        }
        if($request->wantsJson()) {
            return response()->json(['Status successfully deleted.']);
        } else {
            return redirect(Auth::user()->url());
        }
    }

    public function storeShare(Request $request)
    {
        $this->authCheck();
        
        $this->validate($request, [
          'item'    => 'required|integer',
        ]);

        $profile = Auth::user()->profile;
        $status = Status::withCount('shares')->findOrFail($request->input('item'));

        Cache::forget('transform:status:'.$status->url());

        $count = $status->shares_count;

        $exists = Status::whereProfileId(Auth::user()->profile->id)
                  ->whereReblogOfId($status->id)
                  ->count();
        if ($exists !== 0) {
            $shares = Status::whereProfileId(Auth::user()->profile->id)
                  ->whereReblogOfId($status->id)
                  ->get();
            foreach ($shares as $share) {
                $share->delete();
                $count--;
            }
        } else {
            $share = new Status();
            $share->profile_id = $profile->id;
            $share->reblog_of_id = $status->id;
            $share->in_reply_to_profile_id = $status->profile_id;
            $share->save();
            $count++;
            SharePipeline::dispatch($share);
        }

        if ($request->ajax()) {
            $response = ['code' => 200, 'msg' => 'Share saved', 'count' => $count];
        } else {
            $response = redirect($status->url());
        }

        return $response;
    }

    public function showActivityPub(Request $request, $status)
    {
        $fractal = new Fractal\Manager();
        $resource = new Fractal\Resource\Item($status, new Note());
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
          'id'      => 'required|integer|min:1',
          'caption' => 'nullable',
          'filter'  => 'nullable|alpha_dash|max:30',
        ]);

        $id = $request->input('id');
        $caption = $request->input('caption');
        $filter = $request->input('filter');

        $media = Media::whereProfileId($user->id)
            ->whereStatusId($status->id)
            ->find($id);

        $changed = false;

        if ($media->caption != $caption) {
            $media->caption = $caption;
            $changed = true;
        }

        if ($media->filter_class != $filter) {
            $media->filter_class = $filter;
            $changed = true;
        }

        if ($changed === true) {
            $media->save();
        }

        return response()->json([], 200);
    }

    protected function authCheck()
    {
        if (Auth::check() == false) {
            abort(403);
        }
    }

    protected function validateVisibility($visibility)
    {
        $allowed = ['public', 'unlisted', 'private'];
        return in_array($visibility, $allowed) ? $visibility : 'public';
    }

    public static function mimeTypeCheck($mimes)
    {
        $allowed = explode(',', config('pixelfed.media_types'));
        $count = count($mimes);
        $photos = 0;
        $videos = 0;
        foreach($mimes as $mime) {
            if(in_array($mime, $allowed) == false && $mime !== 'video/mp4') {
                continue;
            }
            if(str_contains($mime, 'image/')) {
                $photos++;
            }
            if(str_contains($mime, 'video/')) {
                $videos++;
            }
        }
        if($photos == 1 && $videos == 0) {
            return 'photo';
        }
        if($videos == 1 && $photos == 0) {
            return 'video';
        }
        if($photos > 1 && $videos == 0) {
            return 'photo:album';
        }
        if($videos > 1 && $photos == 0) {
            return 'video:album';
        }
        if($photos >= 1 && $videos >= 1) {
            return 'photo:video:album';
        }
    }
}
