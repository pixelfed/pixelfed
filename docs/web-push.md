# Web Push notifications

Pixelfed supports two independent ways of delivering a push notification.

| | Expo gateway | Web Push |
|---|---|---|
| Endpoints | `/api/v1.1/push/*` | `/api/v1/push/subscription` |
| Who can use it | The official Pixelfed app | Any client |
| Gate | `X-PIXELFED-APP` header | The `push` OAuth scope |
| Requires | A gateway key issued by pixelfed.org | VAPID keys the admin generates |
| Transport | Pixelfed's hosted Expo gateway | Direct HTTPS to the client's push service |
| Standard | Expo's proprietary protocol | RFC 8030 / 8291 / 8292 |

This document covers the second one. The Expo gateway is unchanged, and an
instance can run both, either, or neither.

## Rationale

Everything Web Push needs has been in the tree for some time — the
`laravel-notification-channels/webpush` package, the `push_subscriptions`
table, the `HasPushSubscriptions` trait on `User`, the `push` OAuth scope,
and a VAPID public key that `/api/v2/instance` already advertises to every
Mastodon client that asks:

```json
"configuration": { "vapid": { "public_key": "..." } }
```

What was missing was the route that makes the advertisement true. A client
would read that key, look for `POST /api/v1/push/subscription` where
Mastodon's API documents it, and find nothing there.

The practical consequence is that push has been reachable only through the
official app: the `/api/v1.1/push/*` endpoints are gated on the
`X-PIXELFED-APP` header, and delivery runs through a hosted gateway whose
key an admin has to request. An instance admin could not enable push for a
third-party client, and a third-party client could not offer push on any
instance. Both of those are now possible without either party asking anyone
for permission.

## Benefits

**For instance admins.** Push becomes something you can turn on yourself.
Generate a VAPID keypair, restart the queue, done — no key request, no
third-party gateway in the delivery path, and no dependency on a service
staying up. Notifications go directly from your queue worker to the push
service your users' clients nominate.

**For client developers.** The subscription API is the one Mastodon
documents, so a client that already speaks it needs no Pixelfed-specific
code. A browser or PWA can subscribe through the standard `PushManager` and
be done. A native app, which has no Web Push transport of its own, can point
the subscription at a small relay that terminates Web Push and re-emits the
notification over APNs or FCM — Pixelfed neither knows nor cares which,
because it only ever speaks RFC 8291 to a URL.

**For users.** Push notifications work in whatever client they prefer, on
any instance whose admin has enabled them.

## Limitations

Worth knowing before you enable it:

- **Alerts are account-wide, not per subscription.** Mastodon stores an
  `alerts` object per subscription and lets a client change it through
  `data[alerts][…]` on this endpoint. There is no per-subscription column
  for that here, so inbound `data` is ignored and the response reports the
  account's own `notify_*` preferences. Per-type opt-out works; it is set
  in Pixelfed's notification settings rather than through this API.

- **`policy` is not implemented.** Mastodon can restrict pushes to people
  you follow, or to followers. Every subscription here behaves as `all`.

- **Registering a subscription enables notifications.** `notify_enabled`
  defaults to false and is only writable through endpoints gated on
  `X-PIXELFED-APP`, so a generic client has no way to set it. Registering a
  subscription is treated as the opt-in, exactly as registering an Expo
  token is on the other path. The per-type switches are left alone.

- **Not every notification type is covered.** Likes, follows, follow
  requests, mentions, comments and DMs push. Boosts do not — `SharePipeline`
  dispatches no push on either transport. Polls and `status` alerts do not
  exist in Pixelfed.

- **Payloads are readable by the push service.** That is inherent to Web
  Push with a relay: RFC 8291 encrypts to the subscription keypair, and
  whoever holds the private key sees the plaintext. For a browser that is
  the browser itself; for a native client it is whichever relay minted the
  subscription. Titles and bodies are deliberately terse — "New Like",
  "alice liked your post" — and never contain post content.

- **Delivery is best-effort.** Three attempts with a 10s timeout, then the
  job is dropped. A `404`/`410` deletes the subscription.

## Setup

Generate a VAPID keypair:

```bash
php artisan webpush:vapid
```

Add the result to `.env`, along with a subject — a `mailto:` or `https:`
URL identifying the operator, which push services use to contact you about
misbehaving traffic:

```
VAPID_SUBJECT=mailto:admin@example.org
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
```

Then run migrations and restart the queue:

```bash
php artisan migrate
php artisan config:cache
php artisan horizon:terminate   # or restart your queue workers
```

Deliveries run on the `pushnotify` queue, the same one the Expo path uses,
so no new worker is needed.

**These keys are permanent.** Every existing subscription is bound to the
public key it was created with; rotating the pair invalidates all of them
and every client has to re-subscribe. Back them up with the rest of your
instance configuration.

Leaving them unset disables Web Push. `POST /api/v1/push/subscription` then
returns `404` rather than accepting a subscription that could never be
delivered to, and no delivery is attempted.

## API

Both endpoints require an OAuth token with the `push` scope.

### `POST /api/v1/push/subscription`

```
subscription[endpoint]      the push service URL, https, max 500 chars
subscription[keys][p256dh]  the client's public key, base64url
subscription[keys][auth]    the client's auth secret, base64url
```

Returns Mastodon's `WebPushSubscription` entity:

```json
{
  "id": "17",
  "endpoint": "https://push.example.org/wp/8f2c…",
  "standard": true,
  "alerts": {
    "follow": true, "favourite": true, "mention": true,
    "reblog": false, "poll": false, "status": false
  },
  "server_key": "BJ8…"
}
```

Re-posting the same endpoint updates it in place. A user may hold ten
subscriptions; registering an eleventh drops the oldest.

### `DELETE /api/v1/push/subscription`

Removes the subscription belonging to the access token making the request,
and returns `{}`. Subscriptions are keyed to the token that created them, so
unsubscribing on one device leaves the others alone.

### Payload

Each notification arrives as an `aes128gcm`-encrypted JSON object:

```json
{
  "notification_type": "like",
  "title": "New Like",
  "body": "alice liked your post",
  "account_id": "402…",
  "status_id": "679…"
}
```

`notification_type` is one of `like`, `follow`, `follow_request`, `mention`
or `comment`. `account_id` and `status_id` are the ids to deep-link to, and
are **strings**: they exceed what a JSON number holds exactly, and a client
parsing them as doubles would open the wrong post. `status_id` is null where
no status exists — a follow, or a DM.

## Security notes

The endpoint URL is supplied by an authenticated user and the queue worker
will make an outbound HTTPS request to it from inside the instance's
network, which makes it an SSRF surface. On an open-registration instance,
anyone can register one.

`WebPushEndpointGuard` therefore requires `https`, refuses embedded
credentials, and rejects any host resolving to a private or reserved
address — including a host that resolves to a mix, which has no legitimate
reason to be a push endpoint.

Because DNS can change between registration and delivery, the same check
runs again at send time and the connection is pinned to the addresses just
validated, via `CURLOPT_RESOLVE`. Redirects are refused, since following one
would hand back the control that pinning just took away. An endpoint that
cannot be pinned is skipped rather than sent to unpinned.

## Implementation

| File | Role |
|---|---|
| `app/Http/Controllers/Api/V1/PushSubscriptionController.php` | subscribe / unsubscribe |
| `app/Services/WebPushService.php` | is Web Push configured |
| `app/Services/WebPushEndpointGuard.php` | SSRF checks and connection pinning |
| `app/Rules/WebPushEndpoint.php` | the guard as a validation rule |
| `app/Jobs/PushNotificationPipeline/WebPushNotifyPipeline.php` | encrypt and deliver |
| `app/Observers/FollowRequestObserver.php` | follow-request notifications |

`WebPushNotifyPipeline::maybeDispatch()` is the single entry point. It
checks `notify_enabled` and the per-type `notify_*` switch — the same
preferences `PushNotificationService::check()` applies to the Expo path,
because a user who turned off like notifications meant it regardless of
transport — then queues the job on `pushnotify`.

It is called from the five places that already decide a notification is
warranted: `LikePipeline`, `FollowPipeline`, `MentionPipeline`,
`CommentPipeline`, and `HandlesCreates` for DMs, plus
`FollowRequestObserver`. Each call sits alongside the existing Expo dispatch
rather than replacing it, and outside the `NotificationAppGatewayService`
gate, because a Web Push subscription is self-contained and works on an
instance that has never configured the Expo gateway.
