<?php

namespace App\Http\Controllers;

use App\AccountInterstitial;
use App\Jobs\SharePipeline\SharePipeline;
use App\Jobs\SharePipeline\UndoSharePipeline;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Profile;
use App\Services\AccountService;
use App\Services\HashidService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Status;
use App\StatusView;
use App\Transformer\ActivityPub\Verb\Note;
use App\Transformer\ActivityPub\Verb\Question;
use App\Util\Media\License;
use Auth;
use Cache;
use DB;
use Illuminate\Http\Request;
use League\Fractal;

class StatusController extends Controller
{


    public function delete(Request $request)
    {
        $this->authCheck();

        $this->validate($request, [
            'item' => 'required|integer|min:1',
        ]);

        $status = Status::findOrFail($request->input('item'));

        $user = Auth::user();

        if ($status->profile_id != $user->profile->id &&
            $user->is_admin == true &&
            $status->uri == null
        ) {
            $media = $status->media;

            $ai = new AccountInterstitial;
            $ai->user_id = $status->profile->user_id;
            $ai->type = 'post.removed';
            $ai->view = 'account.moderation.post.removed';
            $ai->item_type = 'App\Status';
            $ai->item_id = $status->id;
            $ai->has_media = (bool) $media->count();
            $ai->blurhash = $media->count() ? $media->first()->blurhash : null;
            $ai->meta = json_encode([
                'caption' => $status->caption,
                'created_at' => $status->created_at,
                'type' => $status->type,
                'url' => $status->url(),
                'is_nsfw' => $status->is_nsfw,
                'scope' => $status->scope,
                'reblog' => $status->reblog_of_id,
                'likes_count' => $status->likes_count,
                'reblogs_count' => $status->reblogs_count,
            ]);
            $ai->save();

            $u = $status->profile->user;
            $u->has_interstitial = true;
            $u->save();
        }

        if ($status->in_reply_to_id) {
            $parent = Status::find($status->in_reply_to_id);
            if ($parent && ($parent->profile_id == $user->profile_id) || ($status->profile_id == $user->profile_id) || $user->is_admin) {
                Cache::forget('_api:statuses:recent_9:'.$status->profile_id);
                Cache::forget('profile:status_count:'.$status->profile_id);
                Cache::forget('profile:embed:'.$status->profile_id);
                StatusService::del($status->id, true);
                Cache::forget('profile:status_count:'.$status->profile_id);
                $status->uri ? RemoteStatusDelete::dispatch($status) : StatusDelete::dispatch($status);
            }
        } elseif ($status->profile_id == $user->profile_id || $user->is_admin == true) {
            Cache::forget('_api:statuses:recent_9:'.$status->profile_id);
            Cache::forget('profile:status_count:'.$status->profile_id);
            Cache::forget('profile:embed:'.$status->profile_id);
            StatusService::del($status->id, true);
            Cache::forget('profile:status_count:'.$status->profile_id);
            $status->uri ? RemoteStatusDelete::dispatch($status) : StatusDelete::dispatch($status);
        }

        if ($request->wantsJson()) {
            return response()->json(['Status successfully deleted.']);
        } else {
            return redirect($user->url());
        }
    }

    public function showActivityPub(Request $request, $status)
    {
        $key = 'pf:status:ap:v1:sid:'.$status['id'];

        return Cache::remember($key, 3600, function () use ($status) {
            $status = Status::findOrFail($status['id']);
            $object = $status->type == 'poll' ? new Question : new Note;
            $fractal = new Fractal\Manager;
            $resource = new Fractal\Resource\Item($status, $object);
            $res = $fractal->createData($resource)->toArray();

            return response()->json($res['data'], 200, ['Content-Type' => 'application/activity+json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        });
    }

    protected function authCheck()
    {
        if (Auth::check() == false) {
            abort(403);
        }
    }

    public static function mimeTypeCheck($mimes)
    {
        $allowed = explode(',', config_cache('pixelfed.media_types'));
        if (! isset($allowed['image/jpg'])) {
            $allowed[] = 'image/jpg';
        }
        $count = count($mimes);
        $photos = 0;
        $videos = 0;
        foreach ($mimes as $mime) {
            if (in_array($mime, $allowed) == false && $mime !== 'video/mp4') {
                continue;
            }
            if (str_contains($mime, 'image/')) {
                $photos++;
            }
            if (str_contains($mime, 'video/')) {
                $videos++;
            }
        }
        if ($photos == 1 && $videos == 0) {
            return 'photo';
        }
        if ($videos == 1 && $photos == 0) {
            return 'video';
        }
        if ($photos > 1 && $videos == 0) {
            return 'photo:album';
        }
        if ($videos > 1 && $photos == 0) {
            return 'video:album';
        }
        if ($photos >= 1 && $videos >= 1) {
            return 'photo:video:album';
        }

        return 'text';
    }
}
