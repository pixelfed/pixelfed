<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Profile;
use Auth;

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
}
