<?php

namespace App\Http\Controllers;

use App\Jobs\InboxPipeline\{
    DeleteWorker,
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
use App\Services\InstanceService;
use App\Services\AccountService;

class FederationController extends Controller
{
    public function nodeinfoWellKnown()
    {
        abort_if(!config('federation.nodeinfo.enabled'), 404);
        return response()->json(Nodeinfo::wellKnown(), 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Access-Control-Allow-Origin','*');
    }

    public function nodeinfo()
    {
        abort_if(!config('federation.nodeinfo.enabled'), 404);
        return response()->json(Nodeinfo::get(), 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Access-Control-Allow-Origin','*');
    }

    public function webfinger(Request $request)
    {
        if (!config('federation.webfinger.enabled') ||
            !$request->has('resource') ||
            !$request->filled('resource')
        ) {
            return response('', 400);
        }

        $resource = $request->input('resource');
        $domain = config('pixelfed.domain.app');

        if(config('federation.activitypub.sharedInbox') &&
            $resource == 'acct:' . $domain . '@' . $domain) {
            $res = [
                'subject' => 'acct:' . $domain . '@' . $domain,
                'aliases' => [
                    'https://' . $domain . '/i/actor'
                ],
                'links' => [
                    [
                        'rel' => 'http://webfinger.net/rel/profile-page',
                        'type' => 'text/html',
                        'href' => 'https://' . $domain . '/site/kb/instance-actor'
                    ],
                    [
                        'rel' => 'self',
                        'type' => 'application/activity+json',
                        'href' => 'https://' . $domain . '/i/actor'
                    ]
                ]
            ];
            return response()->json($res, 200, [], JSON_UNESCAPED_SLASHES);
        }
        $hash = hash('sha256', $resource);
        $key = 'federation:webfinger:sha256:' . $hash;
        if($cached = Cache::get($key)) {
            return response()->json($cached, 200, [], JSON_UNESCAPED_SLASHES);
        }
        if(strpos($resource, $domain) == false) {
            return response('', 400);
        }
        $parsed = Nickname::normalizeProfileUrl($resource);
        if(empty($parsed) || $parsed['domain'] !== $domain) {
            return response('', 400);
        }
        $username = $parsed['username'];
        $profile = Profile::whereNull('domain')->whereUsername($username)->first();
        if(!$profile || $profile->status !== null) {
            return response('', 400);
        }
        $webfinger = (new Webfinger($profile))->generate();
        Cache::put($key, $webfinger, 1209600);

        return response()->json($webfinger, 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Access-Control-Allow-Origin','*');
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
        abort_if(!config_cache('federation.activitypub.enabled'), 404);

        if(!$request->wantsJson()) {
            return redirect('/' . $username);
        }

        $id = AccountService::usernameToId($username);
        abort_if(!$id, 404);
        $account = AccountService::get($id);
        abort_if(!$account || !isset($account['statuses_count']), 404);
        $res = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://' . config('pixelfed.domain.app') . '/users/' . $username . '/outbox',
            'type' => 'OrderedCollection',
            'totalItems' => $account['statuses_count'] ?? 0,
        ];

        return response(json_encode($res, JSON_UNESCAPED_SLASHES))->header('Content-Type', 'application/activity+json');
    }

    public function userInbox(Request $request, $username)
    {
        abort_if(!config_cache('federation.activitypub.enabled'), 404);
        abort_if(!config('federation.activitypub.inbox'), 404);

        $headers = $request->headers->all();
        $payload = $request->getContent();
        if(!$payload || empty($payload)) {
            return;
        }
        $obj = json_decode($payload, true, 8);
        if(!isset($obj['id'])) {
            return;
        }
        $domain = parse_url($obj['id'], PHP_URL_HOST);
        if(in_array($domain, InstanceService::getBannedDomains())) {
            return;
        }

        if(isset($obj['type']) && $obj['type'] === 'Delete') {
            if(isset($obj['object']) && isset($obj['object']['type']) && isset($obj['object']['id'])) {
                if($obj['object']['type'] === 'Person') {
                    if(Profile::whereRemoteUrl($obj['object']['id'])->exists()) {
                        dispatch(new DeleteWorker($headers, $payload))->onQueue('inbox');
                        return;
                    }
                }

                if($obj['object']['type'] === 'Tombstone') {
                    if(Status::whereObjectUrl($obj['object']['id'])->exists()) {
                        dispatch(new DeleteWorker($headers, $payload))->onQueue('delete');
                        return;
                    }
                }

                if($obj['object']['type'] === 'Story') {
                    dispatch(new DeleteWorker($headers, $payload))->onQueue('story');
                    return;
                }
            }
            return;
        } else if( isset($obj['type']) && in_array($obj['type'], ['Follow', 'Accept'])) {
            dispatch(new InboxValidator($username, $headers, $payload))->onQueue('follow');
        } else {
            dispatch(new InboxValidator($username, $headers, $payload))->onQueue('high');
        }
        return;
    }

    public function sharedInbox(Request $request)
    {
        abort_if(!config_cache('federation.activitypub.enabled'), 404);
        abort_if(!config('federation.activitypub.sharedInbox'), 404);

        $headers = $request->headers->all();
        $payload = $request->getContent();

        if(!$payload || empty($payload)) {
            return;
        }

        $obj = json_decode($payload, true, 8);
        if(!isset($obj['id'])) {
            return;
        }

        $domain = parse_url($obj['id'], PHP_URL_HOST);
        if(in_array($domain, InstanceService::getBannedDomains())) {
            return;
        }

        if(isset($obj['type']) && $obj['type'] === 'Delete') {
            if(isset($obj['object']) && isset($obj['object']['type']) && isset($obj['object']['id'])) {
                if($obj['object']['type'] === 'Person') {
                    if(Profile::whereRemoteUrl($obj['object']['id'])->exists()) {
                        dispatch(new DeleteWorker($headers, $payload))->onQueue('inbox');
                        return;
                    }
                }

                if($obj['object']['type'] === 'Tombstone') {
                    if(Status::whereObjectUrl($obj['object']['id'])->exists()) {
                        dispatch(new DeleteWorker($headers, $payload))->onQueue('delete');
                        return;
                    }
                }

                if($obj['object']['type'] === 'Story') {
                    dispatch(new DeleteWorker($headers, $payload))->onQueue('story');
                    return;
                }
            }
            return;
        } else if( isset($obj['type']) && in_array($obj['type'], ['Follow', 'Accept'])) {
            dispatch(new InboxWorker($headers, $payload))->onQueue('follow');
        } else {
            dispatch(new InboxWorker($headers, $payload))->onQueue('shared');
        }
        return;
    }

    public function userFollowing(Request $request, $username)
    {
        abort_if(!config_cache('federation.activitypub.enabled'), 404);

        $id = AccountService::usernameToId($username);
        abort_if(!$id, 404);
        $account = AccountService::get($id);
        abort_if(!$account || !isset($account['following_count']), 404);
        $obj = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'       => $request->getUri(),
            'type'     => 'OrderedCollection',
            'totalItems' => $account['following_count'] ?? 0,
        ];
        return response()->json($obj);
    }

    public function userFollowers(Request $request, $username)
    {
        abort_if(!config_cache('federation.activitypub.enabled'), 404);
        $id = AccountService::usernameToId($username);
        abort_if(!$id, 404);
        $account = AccountService::get($id);
        abort_if(!$account || !isset($account['followers_count']), 404);
        $obj = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'       => $request->getUri(),
            'type'     => 'OrderedCollection',
            'totalItems' => $account['followers_count'] ?? 0,
        ];
        return response()->json($obj);
    }
}
