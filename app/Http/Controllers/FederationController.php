<?php

namespace App\Http\Controllers;

use Auth;
use App\Profile;
use League\Fractal;
use Illuminate\Http\Request;
use App\Util\Lexer\Nickname;
use App\Util\Webfinger\Webfinger;
use App\Transformer\ActivityPub\{
  ProfileOutbox, 
  ProfileTransformer
};
use App\Jobs\RemoteFollowPipeline\RemoteFollowPipeline;

class FederationController extends Controller
{
    public function authCheck()
    {
      if(!Auth::check()) { 
        abort(403); 
      }
      return;
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
      $res =  [
        'metadata' => [
          'nodeName' => config('app.name'),
          'software' => [
            'homepage' => 'https://pixelfed.org',
            'github' => 'https://github.com/pixelfed',
            'follow' => 'https://mastodon.social/@pixelfed'
          ],
          /*
          TODO: Custom Features for Trending
          'customFeatures' => [
            'trending' => [
              'description' => 'Trending API for federated discovery',
              'api' => [
                'url' => null,
                'docs' => null
              ],
            ],
          ],
          */
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
          'name' => 'PixelFed',
          'version' => config('pixelfed.version')
        ],
        'usage' => [
          'localPosts' => \App\Status::whereLocal(true)->count(),
          'users' => [
            'total' => \App\User::count()
          ]
        ],
        'version' => '2.0'
      ];

      return response()->json($res);
    }


    public function webfinger(Request $request)
    {
      $this->validate($request, ['resource'=>'required']);
      $resource = $request->input('resource');
      $parsed = Nickname::normalizeProfileUrl($resource);
      $username = $parsed['username'];
      $user = Profile::whereUsername($username)->firstOrFail();
      $webfinger = (new Webfinger($user))->generate();
      return response()->json($webfinger);
    }

    public function userOutbox(Request $request, $username)
    {
      $user = Profile::whereNull('remote_url')->whereUsername($username)->firstOrFail();

      $timeline = $user->statuses()->orderBy('created_at','desc')->paginate(10);
      $fractal = new Fractal\Manager();
      $resource = new Fractal\Resource\Item($user, new ProfileOutbox);
      $res = $fractal->createData($resource)->toArray();
      return response()->json($res['data']);
    }

}
