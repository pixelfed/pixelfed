<?php

namespace App\Http\Controllers;

use Auth, Cache;
use App\Profile;
use Carbon\Carbon;
use League\Fractal;
use Illuminate\Http\Request;
use App\Util\Lexer\Nickname;
use App\Util\Webfinger\Webfinger;
use App\Transformer\ActivityPub\{
  ProfileOutbox, 
  ProfileTransformer
};
use App\Jobs\RemoteFollowPipeline\RemoteFollowPipeline;
use App\Jobs\InboxPipeline\InboxWorker;

class FederationController extends Controller
{
    public function authCheck()
    {
      if(!Auth::check()) { 
        return abort(403);
      }
    }

    public function authorizeFollow(Request $request)
    {
      $this->authCheck();
      $this->validate($request, [
        'acct' => 'required|string|min:3|max:255'
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
        'url' => 'required|string'
      ]);

      if(config('pixelfed.remote_follow_enabled') !== true) {
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
            'rel'  => 'http://nodeinfo.diaspora.software/ns/schema/2.0'
          ]
        ]
      ];
      return response()->json($res);
    }

    public function nodeinfo()
    {
      $res = Cache::remember('api:nodeinfo', 60, function() {
        return [
          'metadata' => [
            'nodeName' => config('app.name'),
            'software' => [
              'homepage' => 'https://pixelfed.org',
              'github' => 'https://github.com/pixelfed',
              'follow' => 'https://mastodon.social/@pixelfed'
            ],
          ],
          'openRegistrations' => config('pixelfed.open_registration'),
          'protocols' => [
            'activitypub'
          ],
          'services' => [
            'inbound' => [],
            'outbound' => []
          ],
          'software' => [
            'name' => 'pixelfed',
            'version' => config('pixelfed.version')
          ],
          'usage' => [
            'localPosts' => \App\Status::whereLocal(true)->whereHas('media')->count(),
            'localComments' => \App\Status::whereLocal(true)->whereNotNull('in_reply_to_id')->count(),
            'users' => [
              'total' => \App\User::count(),
              'activeHalfyear' => \App\User::where('updated_at', '>', Carbon::now()->subMonths(6)->toDateTimeString())->count(),
              'activeMonth' => \App\User::where('updated_at', '>', Carbon::now()->subMonths(1)->toDateTimeString())->count(),
            ]
          ],
          'version' => '2.0'
        ];
      });
      return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }


    public function webfinger(Request $request)
    {
      $this->validate($request, ['resource'=>'required|string|min:3|max:255']);
      
      $hash = hash('sha512', $request->input('resource'));

      $webfinger = Cache::remember('api:webfinger:'.$hash, 1440, function() use($request) {
        $resource = $request->input('resource');
        $parsed = Nickname::normalizeProfileUrl($resource);
        $username = $parsed['username'];
        $user = Profile::whereUsername($username)->firstOrFail();
        return (new Webfinger($user))->generate();
      });
      return response()->json($webfinger, 200, [], JSON_PRETTY_PRINT);
    }

    public function userOutbox(Request $request, $username)
    {
      if(config('pixelfed.activitypub_enabled') == false) {
        abort(403);
      }
      
      $user = Profile::whereNull('remote_url')->whereUsername($username)->firstOrFail();
      $timeline = $user->statuses()->orderBy('created_at','desc')->paginate(10);
      $fractal = new Fractal\Manager();
      $resource = new Fractal\Resource\Item($user, new ProfileOutbox);
      $res = $fractal->createData($resource)->toArray();
      return response()->json($res['data']);
    }

    public function userInbox(Request $request, $username)
    {
      if(config('pixelfed.activitypub_enabled') == false) {
        abort(403);
      }
      $mimes = [
        'application/activity+json', 
        'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'
      ];
      if(!in_array($request->header('Content-Type'), $mimes)) {
        abort(500, 'Invalid request');
      }
      $profile = Profile::whereUsername($username)->firstOrFail();
      InboxWorker::dispatch($request, $profile, $request->all());
    }

}
