<?php

namespace App\Http\Controllers;

use App\Jobs\InboxPipeline\DeleteWorker;
use App\Jobs\InboxPipeline\InboxValidator;
use App\Jobs\InboxPipeline\InboxWorker;
use App\Models\FeatureAuthorization;
use App\Models\Profile;
use App\Models\QuoteAuthorization;
use App\Models\Status;
use App\Services\AccountService;
use App\Services\ActivityPubSignedFetchService;
use App\Services\FeaturedCollectionService;
use App\Services\FollowersSyncService;
use App\Services\InstanceService;
use App\Services\QuoteService;
use App\Util\Lexer\Nickname;
use App\Util\Site\Nodeinfo;
use App\Util\Webfinger\Webfinger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class FederationController extends Controller
{
    public function nodeinfoWellKnown(): JsonResponse
    {
        abort_if(! config('federation.nodeinfo.enabled'), 404);

        return response()->json(Nodeinfo::wellKnown(), 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function nodeinfo(): JsonResponse
    {
        abort_if(! config('federation.nodeinfo.enabled'), 404);

        return response()->json(Nodeinfo::get(), 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function webfinger(Request $request): JsonResponse|Response
    {
        if (
            ! config('federation.webfinger.enabled') ||
            ! $request->has('resource') ||
            ! $request->filled('resource')
        ) {
            return response('', 400);
        }

        $resource = $request->input('resource');
        $domain = config('pixelfed.domain.app');

        // Instance Actor
        if (
            config('federation.activitypub.sharedInbox') &&
            $resource == 'acct:'.$domain.'@'.$domain
        ) {
            $res = [
                'subject' => 'acct:'.$domain.'@'.$domain,
                'aliases' => [
                    'https://'.$domain.'/i/actor',
                ],
                'links' => [
                    [
                        'rel' => 'http://webfinger.net/rel/profile-page',
                        'type' => 'text/html',
                        'href' => 'https://'.$domain.'/site/kb/instance-actor',
                    ],
                    [
                        'rel' => 'self',
                        'type' => 'application/activity+json',
                        'href' => 'https://'.$domain.'/i/actor',
                    ],
                    [
                        'rel' => 'http://ostatus.org/schema/1.0/subscribe',
                        'template' => 'https://'.$domain.'/authorize_interaction?uri={uri}',
                    ],
                ],
            ];

            return response()->json($res, 200, [], JSON_UNESCAPED_SLASHES);
        }

        if (str_starts_with($resource, 'https://')) {
            if (str_starts_with($resource, 'https://'.$domain.'/users/')) {
                $username = str_replace('https://'.$domain.'/users/', '', $resource);
                if (strlen($username) > 30) {
                    return response('', 400);
                }
                $stripped = str_replace(['_', '.', '-'], '', $username);
                if (! ctype_alnum($stripped)) {
                    return response('', 400);
                }
                $key = 'federation:webfinger:sha256:url-username:'.$username;
                if ($cached = Cache::get($key)) {
                    return response()->json($cached, 200, [], JSON_UNESCAPED_SLASHES);
                }
                $profile = Profile::whereUsername($username)->first();
                if (! $profile || $profile->status !== null || $profile->domain) {
                    return response('', 400);
                }
                $webfinger = (new Webfinger($profile))->generate();
                Cache::put($key, $webfinger, 1209600);

                return response()->json($webfinger, 200, [], JSON_UNESCAPED_SLASHES)
                    ->header('Access-Control-Allow-Origin', '*');
            }

            return response('', 400);
        }
        $hash = hash('sha256', $resource);
        $key = 'federation:webfinger:sha256:'.$hash;
        if ($cached = Cache::get($key)) {
            return response()->json($cached, 200, [], JSON_UNESCAPED_SLASHES);
        }
        if (! str_contains($resource, $domain)) {
            return response('', 400);
        }
        $parsed = Nickname::normalizeProfileUrl($resource);
        if (empty($parsed) || $parsed['domain'] !== $domain) {
            return response('', 400);
        }
        $username = $parsed['username'];
        $profile = Profile::whereUsername($username)->first();
        if (! $profile || $profile->status !== null || $profile->domain) {
            return response('', 400);
        }
        $webfinger = (new Webfinger($profile))->generate();
        Cache::put($key, $webfinger, 1209600);

        return response()->json($webfinger, 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function hostMeta(Request $request): Response
    {
        abort_if(! config('federation.webfinger.enabled'), 404);

        $path = route('well-known.webfinger');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0"><Link rel="lrdd" type="application/xrd+xml" template="'.$path.'?resource={uri}"/></XRD>';

        return response($xml)->header('Content-Type', 'application/xrd+xml');
    }

    public function userOutbox(Request $request, $username)
    {
        abort_if(! (bool) config_cache('federation.activitypub.enabled'), 404);

        if (! $request->wantsJson()) {
            return redirect('/'.$username);
        }

        $id = AccountService::usernameToId($username);
        abort_if(! $id, 404);
        $account = AccountService::get($id);
        abort_if(! $account || ! isset($account['statuses_count']), 404);
        $res = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://'.config('pixelfed.domain.app').'/users/'.$username.'/outbox',
            'type' => 'OrderedCollection',
            'totalItems' => $account['statuses_count'] ?? 0,
        ];

        return response(json_encode($res, JSON_UNESCAPED_SLASHES))->header('Content-Type', 'application/activity+json');
    }

    public function userInbox(Request $request, $username): void
    {
        abort_if(! (bool) config_cache('federation.activitypub.enabled'), 404);
        abort_if(! config('federation.activitypub.inbox'), 404);

        $headers = $request->headers->all();
        $payload = $request->getContent();
        if (! $payload || empty($payload)) {
            return;
        }
        $obj = json_decode($payload, true, 8);
        if (! isset($obj['id'])) {
            return;
        }
        $domain = parse_url($obj['id'], PHP_URL_HOST);
        if (in_array($domain, InstanceService::getBannedDomains())) {
            return;
        }
        if (isset($obj['type']) && $obj['type'] === 'Delete') {
            if (isset($obj['object']) && isset($obj['object']['type']) && isset($obj['object']['id'])) {
                if ($obj['object']['type'] === 'Person') {
                    if (Profile::whereRemoteUrl($obj['object']['id'])->exists()) {
                        dispatch(new DeleteWorker($headers, $payload))->onQueue('inbox');

                        return;
                    }
                }

                if ($obj['object']['type'] === 'Tombstone') {
                    if (Status::whereObjectUrl($obj['object']['id'])->exists()) {
                        dispatch(new DeleteWorker($headers, $payload))->onQueue('delete');

                        return;
                    }
                }

                if ($obj['object']['type'] === 'Story') {
                    dispatch(new DeleteWorker($headers, $payload))->onQueue('story');

                    return;
                }
            }

            return;
        }

        if (isset($obj['type']) && in_array($obj['type'], ['Follow', 'Accept'])) {
            dispatch(new InboxValidator($username, $headers, $payload))->onQueue('follow');
        } else {
            dispatch(new InboxValidator($username, $headers, $payload))->onQueue('high');
        }
    }

    public function sharedInbox(Request $request): void
    {
        abort_if(! (bool) config_cache('federation.activitypub.enabled'), 404);
        abort_if(! config('federation.activitypub.sharedInbox'), 404);

        $headers = $request->headers->all();
        $payload = $request->getContent();

        if (! $payload || empty($payload)) {
            return;
        }

        $obj = json_decode($payload, true, 8);
        if (! isset($obj['id'])) {
            return;
        }

        $domain = parse_url($obj['id'], PHP_URL_HOST);
        if (in_array($domain, InstanceService::getBannedDomains())) {
            return;
        }
        if (isset($obj['type']) && $obj['type'] === 'Delete') {
            if (isset($obj['object']) && isset($obj['object']['type']) && isset($obj['object']['id'])) {
                if ($obj['object']['type'] === 'Person') {
                    if (Profile::whereRemoteUrl($obj['object']['id'])->exists()) {
                        dispatch(new DeleteWorker($headers, $payload))->onQueue('inbox');

                        return;
                    }
                }

                if ($obj['object']['type'] === 'Tombstone') {
                    if (Status::whereObjectUrl($obj['object']['id'])->exists()) {
                        dispatch(new DeleteWorker($headers, $payload))->onQueue('delete');

                        return;
                    }
                }

                if ($obj['object']['type'] === 'Story') {
                    dispatch(new DeleteWorker($headers, $payload))->onQueue('story');

                    return;
                }
            }

            return;
        }

        if (isset($obj['type']) && in_array($obj['type'], ['Follow', 'Accept'])) {
            dispatch(new InboxWorker($headers, $payload))->onQueue('follow');
        } else {
            dispatch(new InboxWorker($headers, $payload))->onQueue('shared');
        }
    }

    public function userFollowing(Request $request, $username): JsonResponse
    {
        abort_if(! (bool) config_cache('federation.activitypub.enabled'), 404);

        $id = AccountService::usernameToId($username);
        abort_if(! $id, 404);
        $account = AccountService::get($id);
        abort_if(! $account || ! isset($account['following_count']), 404);
        $obj = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $request->getUri(),
            'type' => 'OrderedCollection',
            'totalItems' => $account['following_count'] ?? 0,
        ];

        return response()->json($obj)->header('Content-Type', 'application/activity+json');
    }

    public function userFollowers(Request $request, $username): JsonResponse
    {
        abort_if(! (bool) config_cache('federation.activitypub.enabled'), 404);
        $id = AccountService::usernameToId($username);
        abort_if(! $id, 404);
        $account = AccountService::get($id);
        abort_if(! $account || ! isset($account['followers_count']), 404);
        $obj = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $request->getUri(),
            'type' => 'OrderedCollection',
            'totalItems' => $account['followers_count'] ?? 0,
        ];

        return response()->json($obj)->header('Content-Type', 'application/activity+json');
    }

    /**
     * FEP-8fcf: partial followers collection of a local actor, limited to the
     * followers hosted by the instance that signed the request.
     */
    public function userFollowersSynchronization(Request $request, $username): JsonResponse
    {
        abort_if(! (bool) config_cache('federation.activitypub.enabled'), 404);
        abort_if(! FollowersSyncService::enabled(), 404);

        $profile = Profile::whereNull('domain')
            ->whereNull('status')
            ->whereUsername($username)
            ->first();
        abort_if(! $profile, 404);

        $signer = ActivityPubSignedFetchService::verify($request);
        abort_if(! $signer, 401);

        $authority = FollowersSyncService::authority($signer->remote_url);
        abort_if(! $authority, 401);

        $items = FollowersSyncService::partialFollowers($profile, $authority);

        $res = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $profile->permalink('/followers_synchronization'),
            'type' => 'OrderedCollection',
            'totalItems' => count($items),
            'orderedItems' => $items,
        ];

        return response()
            ->json($res, 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/activity+json')
            ->header('Cache-Control', 'private, no-store');
    }

    public function userFeatureAuthorization(Request $request, $username, $id): JsonResponse
    {
        abort_if(! (bool) config_cache('federation.activitypub.enabled'), 404);
        abort_if(! ctype_digit((string) $id), 404);

        $pid = AccountService::usernameToId($username);
        abort_if(! $pid, 404);

        $auth = FeatureAuthorization::with('profile')
            ->whereProfileId($pid)
            ->find((int) $id);

        abort_if(! $auth || ! $auth->profile || $auth->profile->domain !== null, 404);
        abort_if($auth->isRevoked(), 410);

        return response()
            ->json(FeaturedCollectionService::stampObject($auth), 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/activity+json');
    }

    /**
     * FEP-044f QuoteAuthorization stamp.
     *
     * Stamps are only ever issued for public and unlisted posts and carry
     * nothing but ids, so they are publicly dereferenceable.
     */
    public function userQuoteAuthorization(Request $request, $username, $id): JsonResponse
    {
        abort_if(! (bool) config_cache('federation.activitypub.enabled'), 404);
        abort_if(! ctype_digit((string) $id), 404);

        $pid = AccountService::usernameToId($username);
        abort_if(! $pid, 404);

        $auth = QuoteAuthorization::with(['profile', 'status'])
            ->whereProfileId($pid)
            ->find((int) $id);

        abort_if(! $auth || ! $auth->profile || $auth->profile->domain !== null, 404);
        abort_if(! $auth->status || ! QuoteService::isQuotable($auth->status), 404);
        abort_if($auth->isRevoked(), 410);

        return response()
            ->json(QuoteService::stampObject($auth), 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/activity+json');
    }
}
