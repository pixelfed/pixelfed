<?php

namespace App\Http\Controllers;

use App\Jobs\InboxPipeline\InboxWorker;
use App\Jobs\RemoteFollowPipeline\RemoteFollowPipeline;
use App\{
    AccountLog,
    Like,
    Profile,
    Status
};
use App\Transformer\ActivityPub\ProfileOutbox;
use App\Util\Lexer\Nickname;
use App\Util\Webfinger\Webfinger;
use Auth;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use League\Fractal;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use \Zttp\Zttp;

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
        $res = Cache::remember('api:nodeinfo', now()->addMinutes(15), function () {
            $git = Cache::remember('api:nodeinfo:git', now()->addHours(1), function() {
                $hash = exec('git rev-parse HEAD');
                $gitUrl = 'https://github.com/pixelfed/pixelfed/commit/' . $hash;
                return [
                    'commit_hash' => $hash,
                    'url'  => $gitUrl
                ];
            });
            $activeHalfYear = Cache::remember('api:nodeinfo:ahy', now()->addHours(12), function() {
                $count = collect([]);
                // $likes = Like::select('profile_id')->where('created_at', '>', now()->subMonths(6)->toDateTimeString())->groupBy('profile_id')->pluck('profile_id')->toArray();
                // $count = $count->merge($likes);
                $statuses = Status::select('profile_id')->whereLocal(true)->where('created_at', '>', now()->subMonths(6)->toDateTimeString())->groupBy('profile_id')->pluck('profile_id')->toArray();
                $count = $count->merge($statuses);
                $profiles = Profile::select('id')->whereNull('domain')->where('created_at', '>', now()->subMonths(6)->toDateTimeString())->groupBy('id')->pluck('id')->toArray();
                $count = $count->merge($profiles);
                return $count->unique()->count();
            });
            $activeMonth = Cache::remember('api:nodeinfo:am', now()->addHours(12), function() {
                $count = collect([]);
                // $likes = Like::select('profile_id')->where('created_at', '>', now()->subMonths(1)->toDateTimeString())->groupBy('profile_id')->pluck('profile_id')->toArray();
                // $count = $count->merge($likes);
                $statuses = Status::select('profile_id')->whereLocal(true)->where('created_at', '>', now()->subMonths(1)->toDateTimeString())->groupBy('profile_id')->pluck('profile_id')->toArray();
                $count = $count->merge($statuses);
                $profiles = Profile::select('id')->whereNull('domain')->where('created_at', '>', now()->subMonths(1)->toDateTimeString())->groupBy('id')->pluck('id')->toArray();
                $count = $count->merge($profiles);
                return $count->unique()->count();
            });
            return [
                'metadata' => [
                    'nodeName' => config('app.name'),
                    'software' => [
                        'homepage'  => 'https://pixelfed.org',
                        'repo'      => 'https://github.com/pixelfed/pixelfed',
                        'git'       => $git
                    ],
                    'captcha' => (bool) config('pixelfed.recaptcha'),
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
                    'name'          => 'pixelfed',
                    'version'       => config('pixelfed.version'),
                ],
                'usage' => [
                    'localPosts'    => \App\Status::whereLocal(true)->whereHas('media')->count(),
                    'localComments' => \App\Status::whereLocal(true)->whereNotNull('in_reply_to_id')->count(),
                    'users'         => [
                        'total'          => \App\Profile::whereNull('status')->whereNull('domain')->count(),
                        'activeHalfyear' => $activeHalfYear,
                        'activeMonth'    => $activeMonth,
                    ],
                ],
                'version' => '2.0',
            ];
        });

        return response()->json($res, 200, [
            'Access-Control-Allow-Origin' => '*'
        ]);
    }

    public function webfinger(Request $request)
    {
        $this->validate($request, ['resource'=>'required|string|min:3|max:255']);

        $resource = $request->input('resource');
        $hash = hash('sha256', $resource);
        $parsed = Nickname::normalizeProfileUrl($resource);
        $username = $parsed['username'];
        $profile = Profile::whereUsername($username)->firstOrFail();
        if($profile->status != null) {
            return ProfileController::accountCheck($profile);
        }
        $webfinger = (new Webfinger($profile))->generate();

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

        $profile = Profile::whereNull('remote_url')->whereUsername($username)->firstOrFail();
        if($profile->status != null) {
            return ProfileController::accountCheck($profile);
        }
        if($profile->is_private) {
            return response()->json(['error'=>'403', 'msg' => 'private profile'], 403);
        }
        $timeline = $profile->statuses()->whereVisibility('public')->orderBy('created_at', 'desc')->paginate(10);
        $fractal = new Fractal\Manager();
        $resource = new Fractal\Resource\Item($profile, new ProfileOutbox());
        $res = $fractal->createData($resource)->toArray();

        return response(json_encode($res['data']))->header('Content-Type', 'application/activity+json');
    }

    public function userInbox(Request $request, $username)
    {
        if (config('pixelfed.activitypub_enabled') == false) {
            abort(403);
        }

        $profile = Profile::whereNull('domain')->whereUsername($username)->firstOrFail();
        if($profile->status != null) {
            return ProfileController::accountCheck($profile);
        }
        $body = $request->getContent();
        $bodyDecoded = json_decode($body, true, 8);
        if($this->verifySignature($request, $profile) == true) {
            InboxWorker::dispatch($request->headers->all(), $profile, $bodyDecoded);
        } else if($this->blindKeyRotation($request, $profile) == true) {
            InboxWorker::dispatch($request->headers->all(), $profile, $bodyDecoded);
        } else {
            abort(400, 'Bad Signature');
        }
        return;
    }

    protected function verifySignature(Request $request, Profile $profile)
    {
        $body = $request->getContent();
        $bodyDecoded = json_decode($body, true, 8);
        $signature = $request->header('signature');
        $date = $request->header('date');
        if(!$signature) {
            abort(400, 'Missing signature header');
        }
        if(!$date) {
            abort(400, 'Missing date header');
        }
        if(!now()->parse($date)->gt(now()->subDays(1)) || !now()->parse($date)->lt(now()->addDays(1))) {
            abort(400, 'Invalid date');
        }
        $signatureData = HttpSignature::parseSignatureHeader($signature);
        $keyId = Helpers::validateUrl($signatureData['keyId']);
        $id = Helpers::validateUrl($bodyDecoded['id']);
        $keyDomain = parse_url($keyId, PHP_URL_HOST);
        $idDomain = parse_url($id, PHP_URL_HOST);
        if(isset($bodyDecoded['object']) 
            && is_array($bodyDecoded['object'])
            && isset($bodyDecoded['object']['attributedTo'])
        ) {
            if(parse_url($bodyDecoded['object']['attributedTo'], PHP_URL_HOST) !== $keyDomain) {
                abort(400, 'Invalid request');
            }
        }
        if(!$keyDomain || !$idDomain || $keyDomain !== $idDomain) {
            abort(400, 'Invalid request');
        }
        $actor = Profile::whereKeyId($keyId)->first();
        if(!$actor) {
            $actor = Helpers::profileFirstOrNew($bodyDecoded['actor']);
        }
        if(!$actor) {
            return false;
        }
        $pkey = openssl_pkey_get_public($actor->public_key);
        $inboxPath = "/users/{$profile->username}/inbox";
        list($verified, $headers) = HTTPSignature::verify($pkey, $signatureData, $request->headers->all(), $inboxPath, $body);
        if($verified == 1) { 
            return true;
        } else {
            return false;
        }
    }

    protected function blindKeyRotation(Request $request, Profile $profile)
    {
        $signature = $request->header('signature');
        $date = $request->header('date');
        if(!$signature) {
            abort(400, 'Missing signature header');
        }
        if(!$date) {
            abort(400, 'Missing date header');
        }
        if(!now()->parse($date)->gt(now()->subDays(1)) || !now()->parse($date)->lt(now()->addDays(1))) {
            abort(400, 'Invalid date');
        }
        $signatureData = HttpSignature::parseSignatureHeader($signature);
        $keyId = Helpers::validateUrl($signatureData['keyId']);
        $actor = Profile::whereKeyId($keyId)->whereNotNull('remote_url')->firstOrFail();
        $res = Zttp::timeout(5)->withHeaders([
          'Accept'     => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
          'User-Agent' => 'PixelFedBot v0.1 - https://pixelfed.org',
        ])->get($actor->remote_url);
        $res = json_decode($res->body(), true, 8);
        if($res['publicKey']['id'] !== $actor->key_id) {
            return false;
        }
        $actor->public_key = $res['publicKey']['publicKeyPem'];
        $actor->save();
        return $this->verifySignature($request, $profile);
    }

    public function userFollowing(Request $request, $username)
    {
        if (config('pixelfed.activitypub_enabled') == false) {
            abort(403);
        }
        $profile = Profile::whereNull('remote_url')
            ->whereUsername($username)
            ->whereIsPrivate(false)
            ->firstOrFail();
        if($profile->status != null) {
            return ProfileController::accountCheck($profile);
        }
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
        $profile = Profile::whereNull('remote_url')
            ->whereUsername($username)
            ->whereIsPrivate(false)
            ->firstOrFail();
        if($profile->status != null) {
            return ProfileController::accountCheck($profile);
        }
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
