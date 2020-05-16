<?php

namespace App\Http\Controllers;

use App\Jobs\InboxPipeline\{
    InboxWorker,
    InboxValidator
};
use App\Jobs\RemoteFollowPipeline\RemoteFollowPipeline;
use App\{
    AccountLog,
    Like,
    Profile,
    Status,
    User
};
use App\Util\Lexer\Nickname;
use App\Util\Webfinger\Webfinger;
use Auth;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use League\Fractal;
use App\Util\Site\Nodeinfo;
use App\Util\ActivityPub\{
    Helpers,
    HttpSignature,
    Outbox
};
use Zttp\Zttp;

class FederationController extends Controller
{
    public function nodeinfoWellKnown()
    {
        abort_if(!config('federation.nodeinfo.enabled'), 404);
        return response()->json(Nodeinfo::wellKnown());
    }

    public function nodeinfo()
    {
        abort_if(!config('federation.nodeinfo.enabled'), 404);
        return response()->json(Nodeinfo::get())
            ->header('Access-Control-Allow-Origin','*');
    }

    public function webfinger(Request $request)
    {
        abort_if(!config('federation.webfinger.enabled'), 400);

        abort_if(!$request->filled('resource'), 400);

        $resource = $request->input('resource');
        $parsed = Nickname::normalizeProfileUrl($resource);
        if($parsed['domain'] !== config('pixelfed.domain.app')) {
            abort(400);
        }
        $username = $parsed['username'];
        $profile = Profile::whereNull('domain')->whereUsername($username)->firstOrFail();
        if($profile->status != null) {
            return ProfileController::accountCheck($profile);
        }
        $webfinger = (new Webfinger($profile))->generate();

        return response()->json($webfinger, 200, [], JSON_PRETTY_PRINT);
    }

    public function hostMeta(Request $request)
    {
        abort_if(!config('federation.webfinger.enabled'), 404);

        $path = route('well-known.webfinger');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0"><Link rel="lrdd" type="application/xrd+xml" template="'.$path.'?resource={uri}"/></XRD>';

        return response($xml)->header('Content-Type', 'application/xrd+xml');
    }

    public function userOutbox(Request $request, $username)
    {
        abort_if(!config('federation.activitypub.enabled'), 404);
        abort_if(!config('federation.activitypub.outbox'), 404);

        $profile = Profile::whereNull('domain')
            ->whereNull('status')
            ->whereIsPrivate(false)
            ->whereUsername($username)
            ->firstOrFail();

        $key = 'ap:outbox:latest_10:pid:' . $profile->id;
        $ttl = now()->addMinutes(15);
        $res = Cache::remember($key, $ttl, function() use($profile) {
            return Outbox::get($profile);
        });

        return response(json_encode($res, JSON_UNESCAPED_SLASHES))->header('Content-Type', 'application/activity+json');
    }

    public function userInbox(Request $request, $username)
    {
        abort_if(!config('federation.activitypub.enabled'), 404);
        abort_if(!config('federation.activitypub.inbox'), 404);

        // $headers = $request->headers->all();
        // $payload = $request->getContent();
        // InboxValidator::dispatch($username, $headers, $payload);
        $profile = Profile::whereNull('domain')->whereUsername($username)->firstOrFail();
        if($profile->status != null) {
            return ProfileController::accountCheck($profile);
        }
        $body = $request->getContent();
        $bodyDecoded = json_decode($body, true, 12);
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
        $digest = $request->header('digest');
        if(!$digest) {
            abort(400, 'Missing digest header');
        }
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
        if($keyDomain == config('pixelfed.domain.app') || $idDomain == config('pixelfed.domain.app')) {
            return false;
        }
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
        list($verified, $headers) = HttpSignature::verify($pkey, $signatureData, $request->headers->all(), $inboxPath, $body);
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
          'User-Agent' => 'PixelfedBot v0.1 - https://pixelfed.org',
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
        abort_if(!config('federation.activitypub.enabled'), 404);

        $profile = Profile::whereNull('remote_url')
            ->whereUsername($username)
            ->whereIsPrivate(false)
            ->firstOrFail();
            
        if($profile->status != null) {
            abort(404);
        }

        $obj = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'       => $request->getUri(),
            'type'     => 'OrderedCollectionPage',
            'totalItems' => 0,
            'orderedItems' => []
        ];
        return response()->json($obj); 
    }

    public function userFollowers(Request $request, $username)
    {
        abort_if(!config('federation.activitypub.enabled'), 404);

        $profile = Profile::whereNull('remote_url')
            ->whereUsername($username)
            ->whereIsPrivate(false)
            ->firstOrFail();

        if($profile->status != null) {
            abort(404);
        }

        $obj = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'       => $request->getUri(),
            'type'     => 'OrderedCollectionPage',
            'totalItems' => 0,
            'orderedItems' => []
        ];

        return response()->json($obj); 
    }
}
