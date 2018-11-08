<?php

namespace App\Http\Controllers;

use App\Jobs\InboxPipeline\InboxWorker;
use App\Jobs\RemoteFollowPipeline\RemoteFollowPipeline;
use App\Profile;
use App\Transformer\ActivityPub\ProfileOutbox;
use App\Util\Lexer\Nickname;
use App\Util\Webfinger\Webfinger;
use Auth;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use League\Fractal;
use App\Util\ActivityPub\Helpers;

class FederationController extends Controller
{
    public function authCheck()
    {
        if (!Auth::check()) {
            return abort(403);
        }
    }

    public function authorizeFollow(Request $request)
    {
        $this->authCheck();
        $this->validate($request, [
        'acct' => 'required|string|min:3|max:255',
      ]);
        $acct = $request->input('acct');
        $nickname = Nickname::normalizeProfileUrl($acct);

        return view('federation.authorizefollow', compact('acct', 'nickname'));
    }

    public function remoteFollow()
    {
        $this->authCheck();

        return view('federation.remotefollow');
    }

    public function remoteFollowStore(Request $request)
    {
        $this->authCheck();
        $this->validate($request, [
        'url' => 'required|string',
      ]);

        if (config('pixelfed.remote_follow_enabled') !== true) {
            abort(403);
        }

        $follower = Auth::user()->profile;
        $url = $request->input('url');

        RemoteFollowPipeline::dispatch($follower, $url);

        return redirect()->back();
    }

    public function nodeinfoWellKnown()
    {
        $res = [
        'links' => [
          [
            'href' => config('pixelfed.nodeinfo.url'),
            'rel'  => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
          ],
        ],
      ];

        return response()->json($res);
    }

    public function nodeinfo()
    {
        $res = Cache::remember('api:nodeinfo', 60, function () {
            return [
          'metadata' => [
            'nodeName' => config('app.name'),
            'software' => [
              'homepage' => 'https://pixelfed.org',
              'github'   => 'https://github.com/pixelfed',
              'follow'   => 'https://mastodon.social/@pixelfed',
            ],
          ],
          'openRegistrations' => config('pixelfed.open_registration'),
          'protocols'         => [
            'activitypub',
          ],
          'services' => [
            'inbound'  => [],
            'outbound' => [],
          ],
          'software' => [
            'name'    => 'pixelfed',
            'version' => config('pixelfed.version'),
          ],
          'usage' => [
            'localPosts'    => \App\Status::whereLocal(true)->whereHas('media')->count(),
            'localComments' => \App\Status::whereLocal(true)->whereNotNull('in_reply_to_id')->count(),
            'users'         => [
              'total'          => \App\User::count(),
              'activeHalfyear' => \App\AccountLog::select('user_id')->whereAction('auth.login')->where('updated_at', '>',Carbon::now()->subMonths(6)->toDateTimeString())->groupBy('user_id')->get()->count(),
              'activeMonth'    => \App\AccountLog::select('user_id')->whereAction('auth.login')->where('updated_at', '>',Carbon::now()->subMonths(1)->toDateTimeString())->groupBy('user_id')->get()->count(),
            ],
          ],
          'version' => '2.0',
        ];
        });

        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function webfinger(Request $request)
    {
        $this->validate($request, ['resource'=>'required|string|min:3|max:255']);

        $hash = hash('sha256', $request->input('resource'));

        $webfinger = Cache::remember('api:webfinger:'.$hash, 1440, function () use ($request) {
            $resource = $request->input('resource');
            $parsed = Nickname::normalizeProfileUrl($resource);
            $username = $parsed['username'];
            $user = Profile::whereUsername($username)->firstOrFail();

            return (new Webfinger($user))->generate();
        });

        return response()->json($webfinger, 200, [], JSON_PRETTY_PRINT);
    }

    public function hostMeta(Request $request)
    {
        $path = route('well-known.webfinger');
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">
  <Link rel="lrdd" type="application/xrd+xml" template="{$path}?resource={uri}"/>
</XRD>
XML;

        return response($xml)->header('Content-Type', 'application/xrd+xml');
    }

    public function userOutbox(Request $request, $username)
    {
        if (config('pixelfed.activitypub_enabled') == false) {
            abort(403);
        }

        $user = Profile::whereNull('remote_url')->whereUsername($username)->firstOrFail();
        if($user->is_private) {
            return response()->json(['error'=>'403', 'msg' => 'private profile'], 403);
        }
        $timeline = $user->statuses()->whereVisibility('public')->orderBy('created_at', 'desc')->paginate(10);
        $fractal = new Fractal\Manager();
        $resource = new Fractal\Resource\Item($user, new ProfileOutbox());
        $res = $fractal->createData($resource)->toArray();

        return response(json_encode($res['data']))->header('Content-Type', 'application/activity+json');
    }

    public function userInbox(Request $request, $username)
    {
        // if (config('pixelfed.activitypub_enabled') == false || config('pixelfed.ap_inbox') == false) {
        //     abort(403, 'Inbox support disabled');
        // }
        return;
        
        $type = [
            'application/activity+json'
        ];
        if (in_array($request->header('Content-Type'), $type) == false) {
            abort(500, 'Invalid request');
        }
        $profile = Profile::whereUsername($username)->firstOrFail();
        $headers = [
            'date' => $request->header('date'),
            'signature' => $request->header('signature'),
            'digest'   => $request->header('digest'),
            'content-type' => $request->header('content-type'),
            'path'  => $request->getRequestUri(),
            'host'  => $request->getHttpHost()
        ];
        InboxWorker::dispatch($headers, $profile, $request->all());
    }

    public function userFollowing(Request $request, $username)
    {
        if (config('pixelfed.activitypub_enabled') == false) {
            abort(403);
        }
        $profile = Profile::whereNull('remote_url')->whereUsername($username)->firstOrFail();
        $obj = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'       => $request->getUri(),
            'type'     => 'OrderedCollectionPage',
            'totalItems' => $profile->following()->count(),
            'orderedItems' => $profile->following->map(function($f) {
                return $f->permalink();
            })
        ];
        return response()->json($obj); 
    }

    public function userFollowers(Request $request, $username)
    {
        if (config('pixelfed.activitypub_enabled') == false) {
            abort(403);
        }
        $profile = Profile::whereNull('remote_url')->whereUsername($username)->firstOrFail();
        $obj = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'       => $request->getUri(),
            'type'     => 'OrderedCollectionPage',
            'totalItems' => $profile->followers()->count(),
            'orderedItems' => $profile->followers->map(function($f) {
                return $f->permalink();
            })
        ];
        return response()->json($obj); 
    }
}
