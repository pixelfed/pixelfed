<?php

namespace App\Console\Commands\Admin;

use App\Models\ConfigCache;
use App\Models\Instance;
use App\Models\InstanceActor;
use App\Models\Profile;
use App\Models\User;
use App\Services\ConfigCacheService;
use App\Services\DeliveryHostService;
use App\Services\InstanceService;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use League\Uri\BaseUri;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Throwable;

class AppFederation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:federation
        {--remote= : Probe a remote server or account with signed GET requests (example.com, @user@example.com or an actor URL)}
        {--user= : Local username used for the actor, WebFinger, signing and audience checks (defaults to the first active admin)}
        {--only= : Comma separated groups to run: config,actor,endpoints,signing,queues,delivery,env,remote}
        {--skip-http : Skip every check that makes an HTTP request}
        {--json : Output the results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose ActivityPub federation: config, instance actor, endpoints, signatures, queues, delivery health and an optional remote probe';

    private const array LOCAL_GROUPS = [
        'config',
        'actor',
        'endpoints',
        'signing',
        'queues',
        'delivery',
        'env',
    ];

    private const array TITLES = [
        'config' => 'CONFIG',
        'actor' => 'INSTANCE ACTOR',
        'endpoints' => 'LOCAL ENDPOINTS (outside-in)',
        'signing' => 'HTTP SIGNATURES',
        'queues' => 'QUEUES',
        'delivery' => 'DELIVERY HEALTH',
        'env' => 'ENVIRONMENT',
        'remote' => 'REMOTE PROBE',
    ];

    /**
     * Queues that carry federation work on this branch, and what rides on
     * them. A queue without a worker means that traffic silently stalls.
     */
    private const array FEDERATION_QUEUES = [
        'default' => 'outbound status delivery',
        'high' => 'user inbox activities',
        'shared' => 'shared inbox activities',
        'follow' => 'inbound and outbound follows',
        'inbox' => 'inbound actor deletes',
        'delete' => 'inbound deletes',
        'story' => 'story deletes',
        'feed' => 'likes, home feed inserts after ingest',
        'low' => 'shares, signing actor discovery, remote avatars',
        'move' => 'account migration',
    ];

    private const array FEDERATION_JOB_NEEDLES = [
        'InboxPipeline',
        'FollowPipeline',
        'StatusPipeline',
        'SharePipeline',
        'LikePipeline',
        'DeletePipeline',
        'ProfilePipeline',
        'MovePipeline',
        'CommentPipeline',
        'StoryPipeline',
        'FeaturedCollectionPipeline',
        'Federation',
    ];

    private const array AP_ACCEPT_TYPES = [
        'application/activity+json',
        'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
    ];

    private const string CLOCK_REFERENCE = 'https://pixelfed.org';

    private const int CLOCK_SKEW_WARN = 30;

    private const int CLOCK_SKEW_FAIL = 300;

    private const int QUEUE_DEPTH_WARN = 1000;

    private const int QUEUE_DEPTH_FAIL = 10000;

    private const int QUEUE_WAIT_WARN = 120;

    private const int FAILED_JOB_SCAN_LIMIT = 5000;

    private const int MAX_REDIRECTS = 2;

    /** @var array<int, array{group: string, status: string, label: string, detail: ?string, hint: ?string}> */
    protected array $results = [];

    /** @var array<string, array<string, mixed>> */
    protected array $data = [];

    protected string $currentGroup = 'config';

    protected bool $asJson = false;

    protected bool $skipHttp = false;

    protected bool $horizonRunning = false;

    protected bool $testProfileResolved = false;

    protected ?Profile $testProfile = null;

    public function handle(): int
    {
        $this->asJson = (bool) $this->option('json');
        $this->skipHttp = (bool) $this->option('skip-http');

        $groups = $this->resolveGroups();

        if ($groups === null) {
            return self::INVALID;
        }

        foreach ($groups as $group) {
            $this->currentGroup = $group;
            $this->section(self::TITLES[$group]);

            try {
                match ($group) {
                    'config' => $this->checkConfig(),
                    'actor' => $this->checkInstanceActor(),
                    'endpoints' => $this->checkEndpoints(),
                    'signing' => $this->checkSigning(),
                    'queues' => $this->checkQueues(),
                    'delivery' => $this->checkDelivery(),
                    'env' => $this->checkEnvironment(),
                    'remote' => $this->checkRemote((string) $this->option('remote')),
                };
            } catch (Throwable $e) {
                $this->problem(
                    'Check group crashed',
                    class_basename($e).': '.Str::limit($e->getMessage(), 200)
                );
            }

            $this->blank();
        }

        return $this->finish();
    }

    /**
     * @return array<int, string>|null
     */
    protected function resolveGroups(): ?array
    {
        $known = [...self::LOCAL_GROUPS, 'remote'];
        $remote = trim((string) $this->option('remote'));
        $only = trim((string) $this->option('only'));

        if ($only === '') {
            $groups = self::LOCAL_GROUPS;
        } else {
            $groups = array_values(array_unique(array_filter(array_map(
                fn (string $group): string => strtolower(trim($group)),
                explode(',', $only)
            ))));

            $unknown = array_diff($groups, $known);

            if ($unknown !== []) {
                $this->error('Unknown group(s): '.implode(', ', $unknown).'. Valid groups: '.implode(', ', $known));

                return null;
            }

            if (in_array('remote', $groups, true) && $remote === '') {
                $this->error('The remote group needs a target, for example --remote=@user@example.com');

                return null;
            }
        }

        if ($remote !== '' && ! in_array('remote', $groups, true)) {
            $groups[] = 'remote';
        }

        return array_values(array_intersect($known, $groups));
    }

    protected function checkConfig(): void
    {
        $env = app()->environment();

        if ($env === 'production') {
            $this->pass('APP_ENV is production');
        } else {
            $this->problem(
                'APP_ENV is "'.$env.'"',
                'outbound delivery is skipped and instance bans are not enforced',
                'ActivityPubDeliveryService only delivers when APP_ENV=production. Expected on a dev box, fatal on a live instance.'
            );
        }

        $this->checkToggle('federation.activitypub.enabled', 'ActivityPub', 'ACTIVITY_PUB', true);

        foreach (
            [
                'federation.activitypub.inbox' => 'Inbox (AP_INBOX)',
                'federation.activitypub.outbox' => 'Outbox (AP_OUTBOX)',
                'federation.activitypub.sharedInbox' => 'Shared inbox (AP_SHAREDINBOX)',
                'federation.activitypub.remoteFollow' => 'Remote follow (AP_REMOTE_FOLLOW)',
            ] as $key => $label
        ) {
            if (config($key)) {
                $this->pass($label.' enabled');
            } else {
                $this->caution($label.' is disabled');
            }
        }

        if (config('federation.webfinger.enabled')) {
            $this->pass('WebFinger enabled');
        } else {
            $this->problem(
                'WebFinger is disabled (WEBFINGER=false)',
                'remote servers cannot discover local accounts'
            );
        }

        if (config('federation.nodeinfo.enabled')) {
            $this->pass('NodeInfo enabled');
        } else {
            $this->caution(
                'NodeInfo is disabled (NODEINFO=false)',
                'other servers and crawlers cannot identify this instance'
            );
        }

        $this->checkToggle('federation.activitypub.authorized_fetch', 'Authorized fetch', 'AUTHORIZED_FETCH', false);

        $this->note(
            'Followers sync (FEP-8fcf)',
            config('federation.activitypub.followers_sync.enabled') ? 'enabled' : 'disabled'
        );

        $this->note('Delivery tuning', sprintf(
            'timeout=%ss concurrency=%s failure_threshold=%s max_backoff=%ss',
            config('federation.activitypub.delivery.timeout'),
            config('federation.activitypub.delivery.concurrency'),
            config('federation.activitypub.delivery.failure_threshold'),
            config('federation.activitypub.delivery.max_backoff')
        ));

        $this->checkAppUrl();
        $this->checkConfigCacheFreshness();
        $this->checkCacheStore();
        $this->checkSchema();
    }

    /**
     * Report a boolean config key that the admin dashboard can override
     * through the config_cache table, without calling config_cache() itself
     * (it inserts a row when none exists).
     */
    protected function checkToggle(string $key, string $label, string $envName, bool $required): void
    {
        $envValue = (bool) config($key);
        $effective = $envValue;
        $parts = ['env='.$this->bool($envValue)];

        if (config('instance.enable_cc')) {
            try {
                $row = ConfigCache::where('k', $key)->first();
                $cached = Cache::get(ConfigCacheService::CACHE_KEY.$key);

                if ($row) {
                    $dbValue = $this->truthy($row->v);
                    $effective = $dbValue;
                    $parts[] = 'admin setting='.$this->bool($dbValue);

                    if ($cached !== null && $this->truthy($cached) !== $dbValue) {
                        $effective = $this->truthy($cached);

                        $this->caution(
                            $label.' setting cache is stale',
                            'cached='.$this->bool($effective).' database='.$this->bool($dbValue),
                            'php artisan cache:forget "'.ConfigCacheService::CACHE_KEY.$key.'"'
                        );
                    }
                } elseif ($cached !== null) {
                    $effective = $this->truthy($cached);
                    $parts[] = 'cached='.$this->bool($effective);
                }
            } catch (Throwable $e) {
                $this->caution(
                    'Could not read the admin override for '.$key,
                    class_basename($e)
                );
            }
        }

        $detail = implode(' ', $parts);

        if (! $required) {
            $this->note($label, ($effective ? 'enabled' : 'disabled').' ('.$detail.')');

            return;
        }

        if (! $effective) {
            $this->problem(
                $label.' is disabled',
                $detail,
                $envValue
                    ? 'The admin settings override is turning it off. Re-enable it in the admin dashboard.'
                    : 'Set '.$envName.'=true, then run php artisan config:cache.'
            );

            return;
        }

        if ($effective !== $envValue) {
            $this->caution($label.' enabled by admin setting only', $detail, 'The .env value disagrees with the admin setting. The admin setting wins while ENABLE_CONFIG_CACHE is on.');

            return;
        }

        $this->pass($label.' enabled', $detail);
    }

    protected function checkAppUrl(): void
    {
        $appUrl = (string) config('app.url');
        $domain = strtolower((string) config('pixelfed.domain.app'));
        $parts = parse_url($appUrl) ?: [];
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (($parts['scheme'] ?? '') !== 'https') {
            $this->problem(
                'APP_URL is not https',
                $appUrl,
                'Actor ids are built from APP_URL and Pixelfed only accepts https ActivityPub URLs.'
            );
        } else {
            $this->pass('APP_URL uses https', $appUrl);
        }

        if ($domain === '') {
            $this->problem('APP_DOMAIN is empty', null, 'Set APP_DOMAIN to the bare hostname of this instance.');
        } elseif ($host !== $domain) {
            $this->problem(
                'APP_URL host does not match APP_DOMAIN',
                'APP_URL host='.$host.' APP_DOMAIN='.$domain,
                'WebFinger answers for APP_DOMAIN while actor ids use APP_URL. They have to be the same host.'
            );
        } else {
            $this->pass('APP_URL host matches APP_DOMAIN', $domain);
        }

        $path = trim((string) ($parts['path'] ?? ''), '/');

        if ($path !== '' || isset($parts['query'])) {
            $this->caution('APP_URL contains a path or query', $appUrl, 'Use the bare origin, for example https://'.$domain);
        }
    }

    protected function checkConfigCacheFreshness(): void
    {
        if (! app()->configurationIsCached()) {
            $this->note('Config cache', 'not cached, .env is read live');

            return;
        }

        $cachedAt = @filemtime(app()->getCachedConfigPath());
        $envAt = @filemtime(app()->environmentFilePath());

        if ($cachedAt && $envAt && $envAt > $cachedAt) {
            $this->caution(
                '.env is newer than the cached config',
                '.env changed '.Carbon::createFromTimestamp($envAt)->diffForHumans().', config cached '.Carbon::createFromTimestamp($cachedAt)->diffForHumans(),
                'php artisan config:cache (and restart Horizon so workers pick it up)'
            );

            return;
        }

        $this->pass('Cached config is newer than .env');
    }

    protected function checkCacheStore(): void
    {
        $store = (string) config('cache.default');

        if (in_array($store, ['array', 'null'], true)) {
            $this->problem(
                'Cache store is "'.$store.'"',
                'nothing is shared between web requests and queue workers',
                'Set CACHE_STORE=redis.'
            );

            return;
        }

        $this->note('Cache store', $store);
    }

    protected function checkSchema(): void
    {
        $expected = [
            ['instance_actors', null, true],
            ['instances', 'delivery_failures', true],
            ['profiles', 'followers_url', (bool) config('federation.activitypub.followers_sync.enabled')],
        ];

        foreach ($expected as [$table, $column, $required]) {
            try {
                $exists = $column === null
                    ? Schema::hasTable($table)
                    : Schema::hasColumn($table, $column);
            } catch (Throwable) {
                $exists = false;
            }

            if ($exists || ! $required) {
                continue;
            }

            $this->problem(
                'Missing '.($column === null ? 'table '.$table : 'column '.$table.'.'.$column),
                null,
                'php artisan migrate'
            );
        }

        try {
            $migrator = app('migrator');

            if (! $migrator->repositoryExists()) {
                $this->caution('Migration table not found', null, 'php artisan migrate');

                return;
            }

            $files = array_keys($migrator->getMigrationFiles([database_path('migrations'), ...$migrator->paths()]));
            $pending = array_values(array_diff($files, $migrator->getRepository()->getRan()));

            if ($pending === []) {
                $this->pass('No pending migrations');

                return;
            }

            $this->caution(
                count($pending).' pending migration(s)',
                implode(', ', array_slice($pending, 0, 3)).(count($pending) > 3 ? ', ...' : ''),
                'php artisan migrate'
            );
        } catch (Throwable $e) {
            $this->skipped('Pending migrations', class_basename($e));
        }
    }

    protected function checkInstanceActor(): void
    {
        if (! Schema::hasTable('instance_actors')) {
            $this->problem('Missing instance_actors table', null, 'php artisan migrate');

            return;
        }

        $count = InstanceActor::count();
        $actor = InstanceActor::first();

        if (! $actor) {
            $this->problem(
                'No instance actor',
                'signed fetches cannot work without it',
                'php artisan instance:actor'
            );

            return;
        }

        if ($count > 1) {
            $this->caution('Multiple instance actor rows', $count.' rows, the first one (id '.$actor->id.') is used');
        } else {
            $this->pass('Instance actor exists', $actor->permalink());
        }

        $pair = $this->inspectKeyPair($actor->private_key, $actor->public_key);

        if ($pair['error']) {
            $this->problem(
                'Instance actor keypair is broken',
                $pair['error'],
                'Remote servers verify with the published public key, so every signed fetch fails. Delete the row and run php artisan instance:actor, then clear the three caches below.'
            );
        } else {
            $this->pass('Instance actor keypair is valid', $pair['bits'].' bit RSA, public key derives from private key');

            if ($pair['bits'] < 2048) {
                $this->caution('Instance actor key is shorter than 2048 bits', $pair['bits'].' bits');
            }
        }

        $this->compareCachedKey(InstanceActor::PKI_PRIVATE, $actor->private_key, 'private');
        $this->compareCachedKey(InstanceActor::PKI_PUBLIC, $actor->public_key, 'public');

        $cachedProfile = Cache::get(InstanceActor::PROFILE_KEY);

        if (! is_string($cachedProfile) || $cachedProfile === '') {
            $this->note('Cached actor document', 'not cached yet');

            return;
        }

        $doc = json_decode($cachedProfile, true);
        $stale = [];

        if (! is_array($doc)) {
            $stale[] = 'not valid JSON';
        } else {
            if (($doc['id'] ?? null) !== $actor->permalink()) {
                $stale[] = 'id is '.($doc['id'] ?? 'missing').', expected '.$actor->permalink();
            }

            if (! $this->samePem($doc['publicKey']['publicKeyPem'] ?? null, $actor->public_key)) {
                $stale[] = 'publicKeyPem differs from the database';
            }
        }

        if ($stale !== []) {
            $this->problem(
                'Cached actor document is stale',
                implode('; ', $stale),
                'php artisan cache:forget "'.InstanceActor::PROFILE_KEY.'"'
            );

            return;
        }

        $this->pass('Cached actor document matches the database');
    }

    protected function compareCachedKey(string $cacheKey, ?string $dbValue, string $kind): void
    {
        $cached = Cache::get($cacheKey);

        if ($cached === null) {
            $this->note('Cached '.$kind.' key', 'not cached yet');

            return;
        }

        if ($this->samePem($cached, $dbValue)) {
            $this->pass('Cached '.$kind.' key matches the database');

            return;
        }

        $this->problem(
            'Cached '.$kind.' key differs from the database',
            'requests are signed with a key that is not the published one',
            'php artisan cache:forget "'.$cacheKey.'" (then restart Horizon)'
        );
    }

    protected function checkEndpoints(): void
    {
        if ($this->skipHttp) {
            $this->skipped('Local endpoint checks', '--skip-http');

            return;
        }

        $domain = (string) config('pixelfed.domain.app');
        $profile = $this->testProfile();

        $probe = $this->localGet('/.well-known/host-meta', 'application/xrd+xml');

        if ($probe['error']) {
            $this->caution(
                'Could not reach '.config('app.url').' from this server',
                $probe['error'],
                'No hairpin route or internal DNS for your own domain? Run this group from a box that can reach the public URL, the remaining endpoint checks are skipped.'
            );

            return;
        }

        if ($this->expectStatus($probe, 'host-meta', 200)) {
            if (str_contains($probe['body'], 'lrdd') && str_contains($probe['body'], '/.well-known/webfinger')) {
                $this->pass('host-meta', 'points at /.well-known/webfinger');
            } else {
                $this->problem('host-meta has no lrdd webfinger template');
            }
        }

        if ($profile) {
            $acct = 'acct:'.$profile->username.'@'.$domain;
            $res = $this->localGet('/.well-known/webfinger?resource='.rawurlencode($acct), 'application/jrd+json, application/json');

            if ($this->expectStatus($res, 'WebFinger '.$acct, 200)) {
                $this->assertWebfinger($res, $acct, $profile->permalink());
            }
        } else {
            $this->skipped('WebFinger for a local user', 'no usable local profile, pass --user=');
        }

        if (config('federation.activitypub.sharedInbox')) {
            $acct = 'acct:'.$domain.'@'.$domain;
            $res = $this->localGet('/.well-known/webfinger?resource='.rawurlencode($acct), 'application/jrd+json, application/json');

            if ($this->expectStatus($res, 'WebFinger instance actor', 200)) {
                $this->assertWebfinger($res, $acct, url('/i/actor'));
            }
        }

        $this->checkNodeinfoEndpoints();
        $this->checkInstanceActorEndpoint();

        if ($profile) {
            foreach (self::AP_ACCEPT_TYPES as $accept) {
                $this->checkUserActorEndpoint($profile, $accept);
            }
        }

        $inbox = $this->localGet('/f/inbox', 'application/activity+json');

        if ($inbox['error']) {
            $this->caution('Shared inbox route', $inbox['error']);
        } elseif ($inbox['status'] === 405) {
            $this->pass('Shared inbox route reaches Laravel', 'GET /f/inbox returned 405 as expected');
        } else {
            $this->problem(
                'Shared inbox route answered '.$inbox['status'].' to a GET',
                'expected 405 Method Not Allowed',
                $this->statusHint($inbox)
            );
        }
    }

    protected function checkNodeinfoEndpoints(): void
    {
        if (! config('federation.nodeinfo.enabled')) {
            $this->skipped('NodeInfo endpoints', 'disabled');

            return;
        }

        $res = $this->localGet('/.well-known/nodeinfo', 'application/json');

        if (! $this->expectStatus($res, 'NodeInfo discovery', 200)) {
            return;
        }

        $href = collect($res['json']['links'] ?? [])->pluck('href')->filter()->first();

        if (! is_string($href) || parse_url($href, PHP_URL_HOST) !== parse_url((string) config('app.url'), PHP_URL_HOST)) {
            $this->problem('NodeInfo discovery link is missing or on another host', is_string($href) ? $href : null);

            return;
        }

        $this->pass('NodeInfo discovery', $href);

        $doc = $this->localGet((string) parse_url($href, PHP_URL_PATH), 'application/json');

        if (! $this->expectStatus($doc, 'NodeInfo document', 200)) {
            return;
        }

        $software = $doc['json']['software']['name'] ?? null;
        $protocols = $doc['json']['protocols'] ?? [];

        if ($software === 'pixelfed' && in_array('activitypub', (array) $protocols, true)) {
            $this->pass('NodeInfo document', 'pixelfed '.($doc['json']['software']['version'] ?? '?').', protocols: '.implode(',', (array) $protocols));

            return;
        }

        $this->problem(
            'NodeInfo document looks wrong',
            'software='.json_encode($software).' protocols='.json_encode($protocols)
        );
    }

    protected function checkInstanceActorEndpoint(): void
    {
        $res = $this->localGet('/i/actor', 'application/activity+json');

        if (! $this->expectStatus($res, 'Instance actor document', 200)) {
            return;
        }

        $issues = $this->actorDocumentIssues($res, url('/i/actor'), InstanceActor::first()?->public_key);

        if ($issues !== []) {
            $this->problem(
                'Instance actor document',
                implode('; ', $issues),
                'The document is cached forever. If the id or key is stale: php artisan cache:forget "'.InstanceActor::PROFILE_KEY.'"'
            );

            return;
        }

        $this->pass('Instance actor document', 'id, inbox and publicKeyPem all match');
    }

    protected function checkUserActorEndpoint(Profile $profile, string $accept): void
    {
        $label = 'Actor @'.$profile->username.' as '.Str::before($accept, ';');
        $path = (string) parse_url($profile->permalink(), PHP_URL_PATH);
        $res = $this->localGet($path, $accept);

        if (! $this->expectStatus($res, $label, 200)) {
            return;
        }

        $issues = $this->actorDocumentIssues($res, $profile->permalink(), $profile->public_key);

        if (($res['json']['publicKey']['id'] ?? null) !== $profile->keyId()) {
            $issues[] = 'publicKey.id is '.($res['json']['publicKey']['id'] ?? 'missing').', expected '.$profile->keyId();
        }

        if (
            config('federation.activitypub.sharedInbox')
            && ($res['json']['endpoints']['sharedInbox'] ?? null) !== url('/f/inbox')
        ) {
            $issues[] = 'endpoints.sharedInbox is '.($res['json']['endpoints']['sharedInbox'] ?? 'missing');
        }

        if ($issues !== []) {
            $this->problem(
                $label,
                implode('; ', $issues),
                'The actor document is cached for 30 minutes: php artisan cache:forget "pf:activitypub:user-object:by-id:'.$profile->id.'"'
            );

            return;
        }

        $this->pass($label, 'id, inbox, keyId and publicKeyPem all match');

        $vary = strtolower((string) $res['response']->header('Vary'));
        $cacheHeader = collect(['cf-cache-status', 'x-cache', 'age', 'x-served-by'])
            ->first(fn (string $name): bool => (string) $res['response']->header($name) !== '');

        if ($cacheHeader && ! str_contains($vary, 'accept')) {
            $this->caution(
                'Actor response is behind a cache without Vary: Accept',
                'saw a '.$cacheHeader.' header, Vary='.($vary ?: 'none'),
                'A CDN can end up serving the HTML profile page to ActivityPub fetches. Bypass the cache for /users/* or add Vary: Accept.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $res
     * @return array<int, string>
     */
    protected function actorDocumentIssues(array $res, string $expectedId, ?string $expectedPem): array
    {
        $issues = [];
        $doc = $res['json'];

        if (! is_array($doc)) {
            return ['response body is not JSON'];
        }

        if (! $this->isActivityPubContentType($res['content_type'])) {
            $issues[] = 'Content-Type is '.($res['content_type'] ?: 'missing');
        }

        if (($doc['id'] ?? null) !== $expectedId) {
            $issues[] = 'id is '.($doc['id'] ?? 'missing').', expected '.$expectedId;
        }

        if (! is_string($doc['inbox'] ?? null) || ! str_starts_with($doc['inbox'], $expectedId)) {
            $issues[] = 'inbox is '.json_encode($doc['inbox'] ?? null, JSON_UNESCAPED_SLASHES);
        }

        if (! $this->samePem($doc['publicKey']['publicKeyPem'] ?? null, $expectedPem)) {
            $issues[] = 'publicKeyPem differs from the database';
        }

        return $issues;
    }

    /**
     * @param  array<string, mixed>  $res
     */
    protected function assertWebfinger(array $res, string $acct, string $expectedSelf): void
    {
        $doc = $res['json'];

        if (! is_array($doc)) {
            $this->problem('WebFinger '.$acct, 'response body is not JSON', $this->statusHint($res));

            return;
        }

        $self = collect($doc['links'] ?? [])
            ->first(fn ($link): bool => is_array($link)
                && ($link['rel'] ?? null) === 'self'
                && $this->isActivityPubContentType($link['type'] ?? null));

        $issues = [];

        if (($doc['subject'] ?? null) !== $acct) {
            $issues[] = 'subject is '.json_encode($doc['subject'] ?? null, JSON_UNESCAPED_SLASHES);
        }

        if (! $self) {
            $issues[] = 'no rel=self ActivityPub link';
        } elseif (($self['href'] ?? null) !== $expectedSelf) {
            $issues[] = 'self link is '.($self['href'] ?? 'missing').', expected '.$expectedSelf;
        }

        if ($issues !== []) {
            $this->problem('WebFinger '.$acct, implode('; ', $issues));

            return;
        }

        $this->pass('WebFinger '.$acct, 'self link is '.$expectedSelf);
    }

    protected function checkSigning(): void
    {
        $active = Profile::whereNull('domain')->whereNull('status');
        $total = (clone $active)->count();

        $missingPrivate = (clone $active)->where(function ($query) {
            $query->whereNull('private_key')->orWhere('private_key', '');
        })->count();

        $missingPublic = (clone $active)->where(function ($query) {
            $query->whereNull('public_key')->orWhere('public_key', '');
        })->count();

        if ($missingPrivate === 0 && $missingPublic === 0) {
            $this->pass('All active local profiles have a keypair', $total.' profiles');
        } else {
            $this->caution(
                'Active local profiles without keys',
                $missingPrivate.' missing private_key, '.$missingPublic.' missing public_key (of '.$total.')',
                'Those accounts cannot deliver anything (validateSender throws). Inspect one with php artisan status:profile <username>.'
            );
        }

        $profile = $this->testProfile();

        if (! $profile) {
            $this->skipped('Profile signature round trip', 'no usable local profile, pass --user=');
        } else {
            $this->profileRoundTrip($profile);
        }

        $this->instanceActorRoundTrip();
    }

    protected function profileRoundTrip(Profile $profile): void
    {
        $label = 'POST signature round trip as @'.$profile->username;

        $pair = $this->inspectKeyPair($profile->private_key, $profile->public_key);

        if ($pair['error']) {
            $this->problem($label, $pair['error'], 'Remote servers verify against the published public_key, so everything this account sends is rejected.');

            return;
        }

        $url = 'https://federation-check.invalid/inbox';

        $payload = json_encode([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $profile->permalink('#status-federation-'.Str::random(8)),
            'type' => 'Like',
            'actor' => $profile->permalink(),
            'object' => 'https://federation-check.invalid/objects/1',
        ], JSON_UNESCAPED_SLASHES);

        $signed = $this->parseCurlHeaders(HttpSignature::sign($profile, $url, $payload, [
            'Content-Type' => self::AP_ACCEPT_TYPES[1],
            'User-Agent' => $this->userAgent(),
        ]));

        if ($signed === []) {
            $this->problem($label, 'HttpSignature::sign() returned no headers');

            return;
        }

        $issues = [];
        $lower = array_change_key_case($signed, CASE_LOWER);

        foreach (['host', 'date', 'digest', 'content-type', 'signature'] as $required) {
            if (empty($lower[$required])) {
                $issues[] = 'missing '.$required.' header';
            }
        }

        $signature = $this->parseSignature($lower['signature'] ?? '');
        $signedNames = preg_split('/\s+/', strtolower($signature['headers'] ?? '')) ?: [];

        foreach (['(request-target)', 'host', 'date', 'digest'] as $required) {
            if (! in_array($required, $signedNames, true)) {
                $issues[] = $required.' is not part of the signed header list';
            }
        }

        if (($signature['keyId'] ?? null) !== $profile->keyId()) {
            $issues[] = 'keyId is '.($signature['keyId'] ?? 'missing').', expected '.$profile->keyId();
        }

        if (($lower['digest'] ?? null) !== 'SHA-256='.base64_encode(hash('sha256', $payload, true))) {
            $issues[] = 'Digest does not match the payload bytes';
        }

        if ($issues === []) {
            [$verified] = HttpSignature::verify(
                openssl_pkey_get_public($profile->public_key),
                $signature,
                $signed,
                '/inbox',
                $payload,
                'post'
            );

            if ($verified !== 1) {
                $issues[] = 'signature does not verify against the published public key';
            }
        }

        if ($issues !== []) {
            $this->problem($label, implode('; ', $issues));

            return;
        }

        $this->pass($label, 'signed headers: '.$signature['headers']);
    }

    protected function instanceActorRoundTrip(): void
    {
        $label = 'GET signature round trip as the instance actor';
        $actor = Schema::hasTable('instance_actors') ? InstanceActor::first() : null;

        if (! $actor || empty($actor->public_key)) {
            $this->skipped($label, 'no instance actor');

            return;
        }

        $accept = self::AP_ACCEPT_TYPES[0];

        $signed = HttpSignature::instanceActorSign(
            'https://federation-check.invalid/users/check?page=1',
            false,
            ['Accept' => $accept],
            'get'
        );

        $signature = $this->parseSignature($signed['Signature'] ?? '');
        $issues = [];

        if (($signature['keyId'] ?? null) !== $actor->permalink('#main-key')) {
            $issues[] = 'keyId is '.($signature['keyId'] ?? 'missing').', the actor document publishes '.$actor->permalink('#main-key');
        }

        $publicKey = openssl_pkey_get_public($actor->public_key);

        if (! $publicKey) {
            $issues[] = 'published public key does not parse';
        } else {
            [$verified] = HttpSignature::verify(
                $publicKey,
                $signature,
                $signed,
                '/users/check?page=1',
                '',
                'get'
            );

            if ($verified !== 1) {
                $issues[] = 'signature does not verify against the published public key';
            }
        }

        if ($issues !== []) {
            $this->problem(
                $label,
                implode('; ', $issues),
                'Usually a stale cached private key: php artisan cache:forget "'.InstanceActor::PKI_PRIVATE.'"'
            );

            return;
        }

        $this->pass($label, 'signed headers: '.$signature['headers']);
    }

    protected function checkQueues(): void
    {
        $connection = (string) config('queue.default');
        $driver = (string) config('queue.connections.'.$connection.'.driver');

        if ($driver === 'sync') {
            $this->caution(
                'Queue driver is sync',
                'inbox processing and delivery run inside web requests',
                'Set QUEUE_CONNECTION=redis and run Horizon.'
            );
        } else {
            $this->note('Queue connection', $connection.' ('.$driver.' driver)');
        }

        $covered = $driver === 'redis' ? $this->horizonCoverage() : null;

        if ($covered === null && $driver !== 'sync') {
            $this->note(
                'Workers cannot be detected for this driver',
                'make sure something works these queues: '.implode(',', array_keys(self::FEDERATION_QUEUES))
            );
        }

        $waits = $this->horizonWaits();
        $rows = [];

        foreach (self::FEDERATION_QUEUES as $queue => $carries) {
            $pending = null;

            try {
                $pending = (int) Queue::connection($connection)->size($queue);
            } catch (Throwable) {
            }

            $wait = $waits[$connection.':'.$queue] ?? null;
            $hasWorker = $covered === null ? null : in_array($queue, $covered, true);

            $rows[] = [
                $queue,
                match (true) {
                    $hasWorker === null => '?',
                    $hasWorker === false => 'NO',
                    $this->horizonRunning => 'yes',
                    default => 'configured',
                },
                $pending ?? '?',
                $wait === null ? '-' : $wait.'s',
                $carries,
            ];

            if ($hasWorker === false) {
                $this->problem(
                    'No worker for the "'.$queue.'" queue',
                    'carries '.$carries,
                    'Add it to a supervisor in config/horizon.php, then php artisan horizon:terminate.'
                );
            }

            if ($pending !== null && $pending >= self::QUEUE_DEPTH_FAIL) {
                $this->problem('Queue "'.$queue.'" is badly backed up', number_format($pending).' pending jobs, carries '.$carries);
            } elseif ($pending !== null && $pending >= self::QUEUE_DEPTH_WARN) {
                $this->caution('Queue "'.$queue.'" is backing up', number_format($pending).' pending jobs, carries '.$carries);
            }

            if ($wait !== null && $wait >= self::QUEUE_WAIT_WARN) {
                $this->caution('Queue "'.$queue.'" wait time is '.$wait.'s', 'carries '.$carries, 'Raise maxProcesses for its supervisor.');
            }
        }

        $this->renderTable('federation_queues', ['Queue', 'Worker', 'Pending', 'Wait', 'Carries'], $rows);

        $this->checkFailedJobs();
    }

    /**
     * Queues that have a Horizon worker, or null when Horizon is not in use.
     *
     * Live supervisors are preferred. When Horizon is down the configured
     * plan is used instead, so a single "not running" failure is reported
     * rather than one per queue.
     *
     * @return array<int, string>|null
     */
    protected function horizonCoverage(): ?array
    {
        $masters = 'Laravel\Horizon\Contracts\MasterSupervisorRepository';
        $supervisors = 'Laravel\Horizon\Contracts\SupervisorRepository';

        if (! interface_exists($masters) || ! app()->bound($masters)) {
            return null;
        }

        $horizonEnv = (string) (config('horizon.env') ?? config('app.env'));
        $environments = (array) config('horizon.environments', []);

        $plan = collect($environments)
            ->first(fn ($supervisors, $pattern): bool => Str::is((string) $pattern, $horizonEnv));

        if ($plan === null) {
            $this->problem(
                'Horizon has no supervisors for the "'.$horizonEnv.'" environment',
                'config/horizon.php defines: '.implode(', ', array_keys($environments)),
                'Horizon boots without workers unless the environment key exists. Add it or set HORIZON_ENV.'
            );
        }

        $configured = $plan === null ? [] : $this->queuesFrom(
            array_replace_recursive((array) config('horizon.defaults', []), (array) $plan)
        );

        try {
            $running = app($masters)->all();
        } catch (Throwable $e) {
            $this->problem('Could not read Horizon state from Redis', class_basename($e).': '.Str::limit($e->getMessage(), 120));

            return $configured;
        }

        if ($running === []) {
            $this->problem(
                'Horizon is not running',
                'no master supervisor found, nothing is being processed',
                'php artisan horizon (check your supervisor or systemd unit)'
            );

            return $configured;
        }

        $paused = collect($running)->first(fn ($master): bool => ($master->status ?? '') !== 'running');

        $this->horizonRunning = true;

        if ($paused) {
            $this->caution('Horizon is '.$paused->status, null, 'php artisan horizon:continue');
        } else {
            $this->pass('Horizon is running', count($running).' master supervisor(s)');
        }

        return $this->queuesFrom(
            collect(app($supervisors)->all())
                ->map(fn ($supervisor): array => (array) ($supervisor->options ?? []))
                ->all()
        );
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $supervisors
     * @return array<int, string>
     */
    protected function queuesFrom(array $supervisors): array
    {
        $queues = [];

        foreach ($supervisors as $options) {
            $list = $options['queue'] ?? [];
            $list = is_array($list) ? $list : explode(',', (string) $list);

            foreach ($list as $queue) {
                $queues[] = trim((string) $queue);
            }
        }

        return array_values(array_unique(array_filter($queues)));
    }

    /**
     * @return array<string, int>
     */
    protected function horizonWaits(): array
    {
        $calculator = 'Laravel\Horizon\WaitTimeCalculator';

        if (! class_exists($calculator)) {
            return [];
        }

        try {
            return array_map(
                fn ($seconds): int => (int) round((float) $seconds),
                (array) app($calculator)->calculate()
            );
        } catch (Throwable) {
            return [];
        }
    }

    protected function checkFailedJobs(): void
    {
        $table = (string) config('queue.failed.table', 'failed_jobs');
        $connection = config('queue.failed.database');

        try {
            if (! Schema::connection($connection)->hasTable($table)) {
                $this->skipped('Failed jobs', 'no '.$table.' table');

                return;
            }

            $rows = DB::connection($connection)
                ->table($table)
                ->where('failed_at', '>=', now()->subDay())
                ->orderByDesc('id')
                ->limit(self::FAILED_JOB_SCAN_LIMIT)
                ->get([
                    'queue',
                    'failed_at',
                    DB::raw('SUBSTR(payload, 1, 600) as head'),
                    DB::raw('SUBSTR(exception, 1, 400) as reason'),
                ]);
        } catch (Throwable $e) {
            $this->skipped('Failed jobs', class_basename($e));

            return;
        }

        $groups = [];

        foreach ($rows as $row) {
            $class = $this->jobClassFromPayloadHead((string) $row->head);

            if (! $class || ! Str::contains($class, self::FEDERATION_JOB_NEEDLES)) {
                continue;
            }

            $groups[$class] ??= [
                'count' => 0,
                'queue' => (string) $row->queue,
                'latest' => (string) $row->failed_at,
                'reason' => Str::limit(trim(Str::before((string) $row->reason, "\n")), 110),
            ];

            $groups[$class]['count']++;
        }

        $scanned = $rows->count().($rows->count() >= self::FAILED_JOB_SCAN_LIMIT ? '+' : '');

        if ($groups === []) {
            $this->pass('No failed federation jobs in the last 24h', $scanned.' failed jobs scanned');

            return;
        }

        uasort($groups, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        $this->caution(
            array_sum(array_column($groups, 'count')).' failed federation jobs in the last 24h',
            count($groups).' job classes, '.$scanned.' failed jobs scanned',
            'Full traces: Horizon dashboard or php artisan queue:failed'
        );

        $this->renderTable(
            'failed_federation_jobs',
            ['Job', 'Count', 'Queue', 'Latest', 'Latest exception'],
            collect($groups)
                ->map(fn (array $group, string $class): array => [
                    Str::after($class, 'App\\Jobs\\'),
                    $group['count'],
                    $group['queue'],
                    $group['latest'],
                    $group['reason'],
                ])
                ->values()
                ->all()
        );
    }

    protected function checkDelivery(): void
    {
        if (! Schema::hasColumn('instances', 'delivery_failures')) {
            $this->problem(
                'instances.delivery_failures column is missing',
                'DeliveryHostService cannot track dead hosts, every fanout retries them',
                'php artisan migrate'
            );

            return;
        }

        $total = Instance::count();
        $banned = Instance::whereBanned(true)->count();
        $failing = Instance::where('delivery_failures', '>', 0)->count();

        $backedOff = Instance::where('delivery_timeout', true)
            ->where('delivery_next_after', '>', now())
            ->count();

        $this->note('Known instances', number_format($total).' total, '.number_format($banned).' banned');

        if ($failing === 0) {
            $this->pass('No hosts with delivery failures');
        } else {
            $this->note(
                'Hosts with delivery failures',
                number_format($failing).' failing, '.number_format($backedOff).' currently skipped (backoff)'
            );
        }

        if ($total > 0 && $backedOff / $total >= 0.25 && $backedOff >= 20) {
            $this->caution(
                round($backedOff / $total * 100).'% of known hosts are in delivery backoff',
                null,
                'That many dead hosts at once usually means the problem is on this side (DNS, egress firewall, TLS, clock, signatures). Check the env and signing groups.'
            );
        }

        $this->compareBannedCache();

        $worst = Instance::where('delivery_failures', '>', 0)
            ->orderByDesc('delivery_failures')
            ->limit(15)
            ->get(['domain', 'software', 'delivery_failures', 'delivery_timeout', 'delivery_next_after']);

        if ($worst->isNotEmpty()) {
            $profiles = Profile::whereIn('domain', $worst->pluck('domain'))
                ->selectRaw('domain, count(*) as total')
                ->groupBy('domain')
                ->pluck('total', 'domain');

            $this->renderTable(
                'failing_hosts',
                ['Host', 'Software', 'Failures', 'Skipped until', 'Known profiles'],
                $worst->map(fn (Instance $instance): array => [
                    $instance->domain,
                    $instance->software ?? '-',
                    $instance->delivery_failures,
                    $instance->delivery_timeout && $instance->delivery_next_after?->isFuture()
                        ? $instance->delivery_next_after->toDateTimeString().' ('.$instance->delivery_next_after->diffForHumans().')'
                        : '-',
                    $profiles[$instance->domain] ?? 0,
                ])->all()
            );

            $this->note('Clear a host manually', 'App\Services\DeliveryHostService::reset("example.com") in tinker');
        }

        $this->checkAudience();
    }

    protected function compareBannedCache(): void
    {
        $cached = Cache::get(InstanceService::CACHE_KEY_BANNED_DOMAINS);

        if (! is_array($cached)) {
            $this->note('Banned domain cache', 'not cached yet');

            return;
        }

        $live = Instance::whereBanned(true)->pluck('domain')->all();
        $stillBlocked = array_values(array_diff($cached, $live));
        $notYetBlocked = array_values(array_diff($live, $cached));

        if ($stillBlocked === [] && $notYetBlocked === []) {
            $this->pass('Banned domain cache matches the database', count($live).' domains');

            return;
        }

        $this->caution(
            'Banned domain cache is stale',
            count($stillBlocked).' unbanned host(s) still blocked, '.count($notYetBlocked).' banned host(s) not blocked yet'
                .($stillBlocked !== [] ? ' (e.g. '.$stillBlocked[0].')' : ''),
            'The list is cached for 14 days: php artisan cache:forget "'.InstanceService::CACHE_KEY_BANNED_DOMAINS.'"'
        );
    }

    /**
     * Compare the 5 day audience cache used for fanout with the followers
     * table, for the test user only.
     */
    protected function checkAudience(): void
    {
        $profile = $this->testProfile();

        if (! $profile) {
            $this->skipped('Delivery audience', 'no usable local profile, pass --user=');

            return;
        }

        $live = DB::table('followers')
            ->join('profiles', 'followers.profile_id', '=', 'profiles.id')
            ->where('followers.following_id', $profile->id)
            ->whereNotNull('profiles.inbox_url')
            ->whereNull('profiles.deleted_at')
            ->distinct()
            ->get(['profiles.sharedInbox', 'profiles.inbox_url'])
            ->map(fn ($row): ?string => $row->sharedInbox ?? $row->inbox_url)
            ->filter()
            ->unique()
            ->values();

        $hosts = $live->map(fn (string $inbox): ?string => DeliveryHostService::domain($inbox))->filter()->unique();
        $skipped = $hosts->filter(fn (string $host): bool => DeliveryHostService::isUnavailable($host))->count();

        $this->note(
            'Audience of @'.$profile->username,
            $live->count().' inboxes on '.$hosts->count().' hosts, '.$skipped.' host(s) currently skipped'
        );

        $cached = Cache::get('pf:services:follower:audience:'.$profile->id);

        if ($cached === null) {
            return;
        }

        $banned = Instance::whereBanned(true)->pluck('domain')->all();

        $missing = $live
            ->reject(fn (string $inbox): bool => in_array(DeliveryHostService::domain($inbox), $banned, true))
            ->diff(collect($cached))
            ->values();

        if ($missing->isEmpty()) {
            $this->pass('Cached audience covers every follower inbox');

            return;
        }

        $this->caution(
            'Cached audience is missing '.$missing->count().' inbox(es)',
            'e.g. '.$missing->first(),
            'Followers on those inboxes do not get new posts until the cache expires: php artisan cache:forget "pf:services:follower:audience:'.$profile->id.'"'
        );
    }

    protected function checkEnvironment(): void
    {
        $this->note('PHP', PHP_VERSION.' ('.PHP_SAPI.')');

        foreach (['curl', 'openssl', 'json', 'mbstring'] as $extension) {
            if (! extension_loaded($extension)) {
                $this->problem('PHP extension "'.$extension.'" is not loaded');
            }
        }

        if (! function_exists('idn_to_ascii')) {
            $this->caution('PHP intl extension is not loaded', 'internationalized (IDN) hosts are rejected by Helpers::normalizeHost()');
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        if (! function_exists('dns_get_record') || in_array('dns_get_record', $disabled, true)) {
            $this->problem(
                'dns_get_record() is unavailable',
                'Helpers::validateUrl() rejects every remote URL without it',
                'Remove it from disable_functions in the CLI and FPM php.ini.'
            );

            return;
        }

        $this->pass('Required PHP extensions and dns_get_record() are available');

        $this->checkOwnDomainResolution();

        if ($this->skipHttp) {
            $this->skipped('Outbound HTTPS and clock skew', '--skip-http');

            return;
        }

        $this->checkClock(self::CLOCK_REFERENCE);
    }

    /**
     * The inbox handlers run local object URLs (follow targets, inReplyTo,
     * undo objects) through Helpers::validateLocalUrl(), which requires this
     * instance's own domain to resolve to public IPs only.
     */
    protected function checkOwnDomainResolution(): void
    {
        $domain = (string) config('pixelfed.domain.app');

        if ($domain === '') {
            return;
        }

        $ips = Helpers::resolvePublicIps($domain);

        if ($ips !== []) {
            $this->pass('Own domain resolves to public IPs', $domain.' -> '.implode(', ', $ips));
        } else {
            $raw = collect(@dns_get_record($domain.'.', DNS_A | DNS_AAAA) ?: [])
                ->map(fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null)
                ->filter()
                ->implode(', ');

            $this->problem(
                'Own domain does not pass the public IP check from this server',
                $domain.' -> '.($raw !== '' ? $raw : 'no A/AAAA records'),
                'Helpers::validateLocalUrl() fails here, so inbound Follows, replies (inReplyTo) and Undos that reference local URLs are dropped. One private or reserved record (split-horizon DNS, internal resolver, ULA AAAA) is enough. Negative results are cached for 5 minutes.'
            );

            return;
        }

        $profile = $this->testProfile();

        if (! $profile) {
            return;
        }

        if (Helpers::validateLocalUrl($profile->permalink())) {
            $this->pass('Helpers::validateLocalUrl() accepts a local actor URL');

            return;
        }

        $this->problem(
            'Helpers::validateLocalUrl() rejects '.$profile->permalink(),
            null,
            'Inbound activities that reference local URLs are dropped. Check APP_URL, APP_DOMAIN and the instance ban list.'
        );
    }

    protected function checkClock(string $url): void
    {
        try {
            $before = microtime(true);

            $response = Http::withHeaders(['User-Agent' => $this->userAgent()])
                ->timeout(10)
                ->connectTimeout(5)
                ->head($url);

            $elapsed = microtime(true) - $before;
        } catch (Throwable $e) {
            $this->problem(
                'Outbound HTTPS to '.$url.' failed',
                class_basename($e).': '.Str::limit($e->getMessage(), 140),
                'If every outbound request fails, look at the egress firewall, DNS and the CA bundle (curl.cainfo).'
            );

            return;
        }

        $this->pass('Outbound HTTPS works', $url.' answered '.$response->status().' in '.round($elapsed * 1000).'ms');

        $date = (string) $response->header('Date');

        if ($date === '') {
            $this->skipped('Clock skew', 'no Date header from '.$url);

            return;
        }

        try {
            $remote = Carbon::parse($date)->getTimestamp();
        } catch (Throwable) {
            $this->skipped('Clock skew', 'unparseable Date header: '.$date);

            return;
        }

        $skew = (int) round(abs(($before + $elapsed / 2) - $remote));

        if ($skew >= self::CLOCK_SKEW_FAIL) {
            $this->problem(
                'Server clock is off by '.$skew.'s',
                'compared with the Date header of '.$url,
                'Signed requests carry a Date header and remote servers reject stale ones. Fix NTP (timedatectl set-ntp true).'
            );
        } elseif ($skew >= self::CLOCK_SKEW_WARN) {
            $this->caution('Server clock is off by '.$skew.'s', 'compared with the Date header of '.$url, 'Check NTP.');
        } else {
            $this->pass('Server clock is in sync', 'skew '.$skew.'s against '.parse_url($url, PHP_URL_HOST));
        }
    }

    protected function checkRemote(string $input): void
    {
        if ($this->skipHttp) {
            $this->skipped('Remote probe', '--skip-http');

            return;
        }

        $target = $this->parseRemoteTarget($input);

        if (! $target) {
            $this->problem('Could not parse "'.$input.'"', null, 'Use example.com, @user@example.com or an https actor URL.');

            return;
        }

        $host = $target['host'];

        if (Helpers::isLocalDomain($host)) {
            $this->problem($host.' is this instance', null, 'The remote probe needs another server.');

            return;
        }

        $this->note('Target', $host.($target['user'] ? ' (account '.$target['user'].')' : '').($target['actor'] ? ' (actor '.$target['actor'].')' : ''));

        if (! $this->checkRemoteHost($host)) {
            return;
        }

        $this->checkRemoteNodeinfo($host);

        $actorUrl = $target['actor'];

        if (! $actorUrl) {
            $account = $target['user'] ?? $host;
            $actorUrl = $this->remoteWebfinger($host, $account, $target['user'] !== null);
        }

        if (! $actorUrl) {
            if (! $target['user']) {
                $this->skipped('Signed actor fetch', 'no instance actor advertised over WebFinger, pass --remote=@user@'.$host.' to test one');
            }

            return;
        }

        $this->checkRemoteActor($actorUrl);
    }

    protected function checkRemoteHost(string $host): bool
    {
        $instance = Instance::whereDomain($host)->first();

        if (! $instance) {
            $this->note('Instance row', 'none yet, this server has never been seen');
        } else {
            $this->note('Instance row', sprintf(
                'id=%s software=%s banned=%s unlisted=%s delivery_failures=%s',
                $instance->id,
                $instance->software ?? 'null',
                $this->bool((bool) $instance->banned),
                $this->bool((bool) $instance->unlisted),
                $instance->delivery_failures ?? 0
            ));

            if ($instance->banned) {
                $this->caution(
                    $host.' is banned on this instance',
                    'fetches and deliveries are refused by design',
                    app()->environment('production') ? null : 'Bans are only enforced in production, so the probe below still runs.'
                );
            }
        }

        if (DeliveryHostService::isUnavailable($host)) {
            $this->caution(
                'Deliveries to '.$host.' are currently skipped',
                'in backoff until '.($instance?->delivery_next_after?->toDateTimeString() ?? 'unknown'),
                'Clear it with App\Services\DeliveryHostService::reset("'.$host.'") once the host is healthy.'
            );
        }

        $ips = Helpers::resolvePublicIps($host);

        if ($ips === []) {
            $this->problem(
                $host.' does not pass the public IP check',
                'no records, or at least one private/reserved record',
                'Helpers::validateUrl() rejects every URL on this host, so nothing is fetched or delivered. Negative results are cached for 5 minutes.'
            );

            return false;
        }

        $this->pass('DNS', $host.' -> '.implode(', ', $ips));

        if (! Helpers::validateUrl('https://'.$host.'/')) {
            $this->problem(
                'Helpers::validateUrl() rejects https://'.$host.'/',
                $instance?->banned ? 'the host is banned' : null
            );

            return false;
        }

        $this->pass('Helpers::validateUrl() accepts the host');

        return true;
    }

    protected function checkRemoteNodeinfo(string $host): void
    {
        $discovery = $this->remoteGet('https://'.$host.'/.well-known/nodeinfo', false, 'application/json');

        if ($discovery['error'] || $discovery['status'] !== 200) {
            $this->caution(
                'NodeInfo discovery',
                $discovery['error'] ?? 'HTTP '.$discovery['status'],
                $discovery['error'] ? 'If this is a connection error the server is down or unreachable from here.' : null
            );

            return;
        }

        $href = collect($discovery['json']['links'] ?? [])->pluck('href')->filter()->last();
        $doc = is_string($href) ? $this->remoteGet($href, false, 'application/json') : null;

        if (! $doc || $doc['error'] || $doc['status'] !== 200 || ! is_array($doc['json'])) {
            $this->caution('NodeInfo document', $doc['error'] ?? ($doc ? 'HTTP '.$doc['status'] : 'no link in discovery document'));

            return;
        }

        $this->pass('Reachable over HTTPS', sprintf(
            '%s %s',
            $doc['json']['software']['name'] ?? 'unknown software',
            $doc['json']['software']['version'] ?? ''
        ));
    }

    protected function remoteWebfinger(string $host, string $account, bool $required): ?string
    {
        $acct = 'acct:'.$account.'@'.$host;

        $res = $this->remoteGet(
            'https://'.$host.'/.well-known/webfinger?resource='.rawurlencode($acct),
            false,
            'application/jrd+json, application/json'
        );

        $self = collect($res['json']['links'] ?? [])
            ->first(fn ($link): bool => is_array($link)
                && ($link['rel'] ?? null) === 'self'
                && $this->isActivityPubContentType($link['type'] ?? null));

        $href = is_array($self) ? ($self['href'] ?? null) : null;

        if (is_string($href) && $href !== '') {
            $this->pass('WebFinger '.$acct, $href);

            return $href;
        }

        if ($required) {
            $this->problem(
                'WebFinger '.$acct,
                $res['error'] ?? 'HTTP '.$res['status'].', no rel=self ActivityPub link',
                $this->statusHint($res, false)
            );
        }

        return null;
    }

    protected function checkRemoteActor(string $actorUrl): void
    {
        $signed = $this->remoteGet($actorUrl, true);
        $unsigned = $this->remoteGet($actorUrl, false);

        $signedOk = ! $signed['error'] && $signed['status'] === 200;
        $unsignedOk = ! $unsigned['error'] && $unsigned['status'] === 200;
        $describe = fn (array $res): string => $res['error'] ?? 'HTTP '.$res['status'];

        if ($signedOk) {
            $this->pass(
                'Signed actor fetch',
                $unsignedOk
                    ? 'HTTP 200 (unsigned also 200)'
                    : 'HTTP 200 (unsigned: '.$describe($unsigned).', the remote enforces authorized fetch)'
            );
        } elseif ($unsignedOk) {
            $this->problem(
                'Signed actor fetch failed but the unsigned one works',
                'signed: '.$describe($signed).', unsigned: HTTP 200',
                'The remote is rejecting this instance\'s signature specifically. Check the actor and signing groups, the clock, and that the remote can fetch '.url('/i/actor').' (run the endpoints group).'
            );
        } else {
            $this->problem(
                'Actor fetch failed signed and unsigned',
                'signed: '.$describe($signed).', unsigned: '.$describe($unsigned),
                in_array($signed['status'], [401, 403], true)
                    ? 'Refused either way: this instance may be blocked (defederated) there, or a WAF is rejecting the PixelFedBot user agent.'
                    : $this->statusHint($signed, false)
            );
        }

        $res = $signedOk ? $signed : ($unsignedOk ? $unsigned : null);

        if (! $res) {
            return;
        }

        if (! $this->isActivityPubContentType($res['content_type'])) {
            $this->problem(
                'Remote actor Content-Type is '.($res['content_type'] ?: 'missing'),
                null,
                'ActivityPubFetchService only accepts application/activity+json or application/ld+json with the ActivityStreams profile, so this actor cannot be imported.'
            );
        }

        $doc = $res['json'];

        if (! is_array($doc) || ! isset($doc['id'], $doc['inbox'], $doc['publicKey']['publicKeyPem'])) {
            $this->problem('Remote actor document is missing id, inbox or publicKey');

            return;
        }

        $this->note('Remote actor', sprintf(
            'type=%s inbox=%s sharedInbox=%s',
            is_string($doc['type'] ?? null) ? $doc['type'] : json_encode($doc['type'] ?? null),
            $doc['inbox'],
            $doc['endpoints']['sharedInbox'] ?? 'none'
        ));

        if (parse_url((string) ($doc['publicKey']['id'] ?? ''), PHP_URL_HOST) !== parse_url((string) $doc['id'], PHP_URL_HOST)) {
            $this->problem('publicKey.id is on a different host than the actor id', (string) ($doc['publicKey']['id'] ?? 'missing'), 'Helpers::isValidProfileData() rejects this actor.');
        }

        $this->compareLocalCopy($doc);
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    protected function compareLocalCopy(array $doc): void
    {
        $local = Profile::withTrashed()->where('remote_url', $doc['id'])->first();

        if (! $local) {
            $this->note('Local copy', 'none, this actor has not been imported yet');

            return;
        }

        $issues = [];

        if ($local->deleted_at) {
            $issues[] = 'soft-deleted locally';
        }

        if ($local->status !== null) {
            $issues[] = 'status='.$local->status;
        }

        if (! $this->samePem($local->public_key, $doc['publicKey']['publicKeyPem'])) {
            $issues[] = 'stored public_key differs from the live key (key rotated, so their signatures fail verification here)';
        }

        if ($local->key_id !== ($doc['publicKey']['id'] ?? null)) {
            $issues[] = 'stored key_id is '.($local->key_id ?? 'null');
        }

        if ($local->inbox_url !== $doc['inbox']) {
            $issues[] = 'stored inbox_url is '.($local->inbox_url ?? 'null');
        }

        if (($local->getAttributes()['sharedInbox'] ?? null) !== ($doc['endpoints']['sharedInbox'] ?? null)) {
            $issues[] = 'stored sharedInbox is '.($local->getAttributes()['sharedInbox'] ?? 'null');
        }

        $fetched = $local->last_fetched_at ? Carbon::parse($local->last_fetched_at)->diffForHumans() : 'never';

        if ($issues !== []) {
            $this->problem(
                'Local copy (profile '.$local->id.') is out of date',
                implode('; ', $issues).'; last fetched '.$fetched,
                'php artisan status:profile '.$local->id.' for the full row. It refreshes on the next fetch once Helpers::needsFetch() is true.'
            );

            return;
        }

        $this->pass('Local copy (profile '.$local->id.') matches the live actor', 'last fetched '.$fetched);
    }

    /**
     * @return array{host: string, user: ?string, actor: ?string}|null
     */
    protected function parseRemoteTarget(string $input): ?array
    {
        $input = trim($input);
        $user = null;
        $actor = null;

        if (Str::startsWith($input, ['https://', 'http://'])) {
            $host = parse_url($input, PHP_URL_HOST);
            $path = trim((string) parse_url($input, PHP_URL_PATH), '/');
            $actor = $path !== '' ? $input : null;
        } else {
            $input = ltrim($input, '@');

            if (str_contains($input, '@')) {
                [$user, $host] = explode('@', $input, 2);
                $user = $user !== '' ? $user : null;
            } else {
                $host = explode('/', $input)[0];
            }
        }

        $host = Helpers::normalizeHost(is_string($host) ? $host : null);

        if (! $host) {
            return null;
        }

        return ['host' => $host, 'user' => $user, 'actor' => $actor];
    }

    /**
     * GET one of this instance's own public URLs the way a remote server
     * would, without following redirects.
     *
     * @return array{error: ?string, status: ?int, content_type: string, body: string, json: mixed, response: ?Response}
     */
    protected function localGet(string $path, string $accept): array
    {
        $url = rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');

        try {
            $response = Http::withHeaders([
                'Accept' => $accept,
                'User-Agent' => $this->userAgent(),
            ])
                ->withOptions(['allow_redirects' => false])
                ->timeout(10)
                ->connectTimeout(5)
                ->get($url);
        } catch (Throwable $e) {
            return $this->httpResult(null, class_basename($e).': '.Str::limit($e->getMessage(), 140));
        }

        return $this->httpResult($response);
    }

    /**
     * Read-only GET against a remote server, mirroring ActivityPubFetchService:
     * every hop is validated, DNS is pinned to the validated public IPs and,
     * when $signed, the request is signed by the instance actor.
     *
     * @return array{error: ?string, status: ?int, content_type: string, body: string, json: mixed, response: ?Response}
     */
    protected function remoteGet(string $url, bool $signed, string $accept = 'application/activity+json'): array
    {
        $current = $url;

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $valid = Helpers::validateUrl($current);

            if (! is_string($valid)) {
                return $this->httpResult(null, 'rejected by Helpers::validateUrl(): '.$current);
            }

            $host = (string) parse_url($valid, PHP_URL_HOST);
            $port = parse_url($valid, PHP_URL_PORT) ?: 443;
            $ips = Helpers::resolvePublicIps($host);

            if ($ips === []) {
                return $this->httpResult(null, 'no public IPs for '.$host);
            }

            try {
                $headers = $signed
                    ? HttpSignature::instanceActorSign($valid, false, ['Accept' => $accept], 'get')
                    : [];

                $headers['Accept'] = $accept;
                $headers['User-Agent'] = $this->userAgent();

                $pinned = implode(',', array_map(
                    fn (string $ip): string => str_contains($ip, ':') ? '['.$ip.']' : $ip,
                    $ips
                ));

                $response = Http::withOptions([
                    'allow_redirects' => false,
                    'curl' => [
                        CURLOPT_RESOLVE => [$host.':'.$port.':'.$pinned],
                    ],
                ])
                    ->withHeaders($headers)
                    ->timeout(15)
                    ->connectTimeout(5)
                    ->get($valid);
            } catch (Throwable $e) {
                return $this->httpResult(null, class_basename($e).': '.Str::limit($e->getMessage(), 140));
            }

            if (! in_array($response->status(), [301, 302, 303, 307, 308], true)) {
                return $this->httpResult($response);
            }

            $location = trim((string) $response->header('Location'));

            if ($location === '') {
                return $this->httpResult($response);
            }

            try {
                $current = (string) BaseUri::from($valid)->resolve($location);
            } catch (Throwable) {
                return $this->httpResult(null, 'unresolvable redirect to '.$location);
            }
        }

        return $this->httpResult(null, 'more than '.self::MAX_REDIRECTS.' redirects');
    }

    /**
     * @return array{error: ?string, status: ?int, content_type: string, body: string, json: mixed, response: ?Response}
     */
    protected function httpResult(?Response $response, ?string $error = null): array
    {
        $body = $response ? substr($response->body(), 0, 2 * 1024 * 1024) : '';

        return [
            'error' => $error,
            'status' => $response?->status(),
            'content_type' => strtolower((string) $response?->header('Content-Type')),
            'body' => $body,
            'json' => $body !== '' ? json_decode($body, true) : null,
            'response' => $response,
        ];
    }

    /**
     * @param  array<string, mixed>  $res
     */
    protected function expectStatus(array $res, string $label, int $expected): bool
    {
        if ($res['error']) {
            $this->problem($label, $res['error']);

            return false;
        }

        if ($res['status'] === $expected) {
            return true;
        }

        $this->problem($label, 'HTTP '.$res['status'].', expected '.$expected, $this->statusHint($res));

        return false;
    }

    /**
     * @param  array<string, mixed>  $res
     */
    protected function statusHint(array $res, bool $local = true): ?string
    {
        $response = $res['response'] ?? null;

        if (! $response instanceof Response) {
            return null;
        }

        $status = $response->status();

        if ((string) $response->header('cf-mitigated') !== '') {
            return $local
                ? 'Cloudflare is challenging this request (cf-mitigated: '.$response->header('cf-mitigated').'). Add a WAF skip rule for /.well-known/*, /users/*, /i/actor, /f/inbox and /api/nodeinfo/*. Bot fight mode breaks federation.'
                : 'Cloudflare on the remote side is challenging requests from this server.';
        }

        if (in_array($status, [301, 302, 303, 307, 308], true)) {
            return $local
                ? 'Redirects to '.$response->header('Location').'. Remote servers do not reliably follow redirects here, APP_URL should be the canonical origin.'
                : 'Redirects to '.$response->header('Location').'.';
        }

        if (str_contains($res['content_type'], 'text/html')) {
            return $local
                ? 'Got HTML back: a WAF or bot challenge, a maintenance page, or the web server is not passing this path to Laravel.'
                : 'Got HTML back: the remote is not doing content negotiation for this URL, or a WAF challenged the request.';
        }

        if (! $local) {
            return match (true) {
                $status === 404, $status === 410 => 'The remote says this resource does not exist (deleted, moved or suspended).',
                $status === 429 => 'This server is being rate limited by the remote.',
                $status >= 500 => 'The remote server is erroring, nothing to fix on this side.',
                default => null,
            };
        }

        return match (true) {
            $status === 401, $status === 403 => 'Blocked before or inside the app: check WAF rules, basic auth and IP allow lists.',
            $status === 404 => 'The route is not reaching Laravel, or the feature is disabled in config.',
            $status === 429 => 'Rate limited by the proxy or the app.',
            $status >= 500 => 'Server error: check storage/logs/laravel.log and the web server error log.',
            default => null,
        };
    }

    protected function userAgent(): string
    {
        return 'PixelFedBot/1.0.0 (Pixelfed/'.config('pixelfed.version').'; +'.config('app.url').')';
    }

    /**
     * @return array{error: ?string, bits: int}
     */
    protected function inspectKeyPair(?string $privatePem, ?string $publicPem): array
    {
        if (empty($privatePem)) {
            return ['error' => 'private key is empty', 'bits' => 0];
        }

        if (empty($publicPem)) {
            return ['error' => 'public key is empty', 'bits' => 0];
        }

        $private = openssl_pkey_get_private($privatePem);

        if (! $private) {
            return ['error' => 'private key does not parse', 'bits' => 0];
        }

        if (! openssl_pkey_get_public($publicPem)) {
            return ['error' => 'public key does not parse', 'bits' => 0];
        }

        $details = openssl_pkey_get_details($private) ?: [];

        if (($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            return ['error' => 'private key is not RSA', 'bits' => 0];
        }

        if (! $this->samePem($details['key'] ?? null, $publicPem)) {
            return ['error' => 'public key does not belong to the private key', 'bits' => (int) $details['bits']];
        }

        return ['error' => null, 'bits' => (int) $details['bits']];
    }

    protected function samePem(mixed $a, mixed $b): bool
    {
        if (! is_string($a) || ! is_string($b) || $a === '' || $b === '') {
            return false;
        }

        return preg_replace('/\s+/', '', $a) === preg_replace('/\s+/', '', $b);
    }

    /**
     * Parse a draft-cavage Signature header. HttpSignature::parseSignatureHeader()
     * is avoided on purpose: it runs keyId through Helpers::validateUrl(), which
     * would make the local round trip depend on DNS.
     *
     * @return array<string, string>
     */
    protected function parseSignature(string $header): array
    {
        preg_match_all(
            '/(?:^|,)\s*([A-Za-z][A-Za-z0-9_-]*)\s*=\s*"([^"]*)"/',
            $header,
            $matches,
            PREG_SET_ORDER
        );

        $parsed = [];

        foreach ($matches as $match) {
            $parsed[$match[1]] = $match[2];
        }

        return $parsed;
    }

    /**
     * @param  array<int, string>  $curlHeaders
     * @return array<string, string>
     */
    protected function parseCurlHeaders(array $curlHeaders): array
    {
        $headers = [];

        foreach ($curlHeaders as $header) {
            $position = is_string($header) ? strpos($header, ':') : false;

            if ($position === false) {
                continue;
            }

            $headers[trim(substr($header, 0, $position))] = trim(substr($header, $position + 1));
        }

        return $headers;
    }

    protected function jobClassFromPayloadHead(string $head): ?string
    {
        if (! preg_match('/"displayName":"((?:[^"\\\\]|\\\\.)+)"/', $head, $match)) {
            return null;
        }

        $decoded = json_decode('"'.$match[1].'"');

        return is_string($decoded) ? $decoded : null;
    }

    protected function isActivityPubContentType(mixed $type): bool
    {
        if (! is_string($type)) {
            return false;
        }

        $type = strtolower($type);

        if (str_starts_with($type, 'application/activity+json')) {
            return true;
        }

        return str_starts_with($type, 'application/ld+json')
            && str_contains($type, 'https://www.w3.org/ns/activitystreams');
    }

    protected function testProfile(): ?Profile
    {
        if ($this->testProfileResolved) {
            return $this->testProfile;
        }

        $username = ltrim(trim((string) $this->option('user')), '@');

        if ($username !== '') {
            $profile = Profile::whereNull('domain')->whereUsername($username)->first();

            if (! $profile) {
                $this->caution('Local user "'.$username.'" not found', 'user dependent checks are skipped');
            }
        } else {
            $admin = User::whereIsAdmin(true)->whereNull('status')->orderBy('id')->first();

            $profile = $admin?->profile
                ?? Profile::whereNull('domain')
                    ->whereNull('status')
                    ->whereNotNull('private_key')
                    ->orderBy('id')
                    ->first();
        }

        $this->testProfileResolved = true;

        return $this->testProfile = $profile;
    }

    protected function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected function bool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    protected function pass(string $label, ?string $detail = null): void
    {
        $this->record('ok', $label, $detail);
    }

    protected function caution(string $label, ?string $detail = null, ?string $hint = null): void
    {
        $this->record('warn', $label, $detail, $hint);
    }

    protected function problem(string $label, ?string $detail = null, ?string $hint = null): void
    {
        $this->record('fail', $label, $detail, $hint);
    }

    protected function note(string $label, ?string $detail = null): void
    {
        $this->record('info', $label, $detail);
    }

    protected function skipped(string $label, ?string $detail = null): void
    {
        $this->record('skip', $label, $detail);
    }

    protected function record(string $status, string $label, ?string $detail = null, ?string $hint = null): void
    {
        $this->results[] = [
            'group' => $this->currentGroup,
            'status' => $status,
            'label' => $label,
            'detail' => $detail,
            'hint' => $hint,
        ];

        if ($this->asJson) {
            return;
        }

        $this->line(
            '  '.$this->icon($status).' '.OutputFormatter::escape($label)
                .($detail !== null ? '  <fg=gray>'.OutputFormatter::escape($detail).'</>' : '')
        );

        if ($hint !== null) {
            $this->line('      <fg=gray>↳ '.OutputFormatter::escape($hint).'</>');
        }
    }

    protected function icon(string $status): string
    {
        return match ($status) {
            'ok' => '<fg=green>✓</>',
            'warn' => '<fg=yellow>!</>',
            'fail' => '<fg=red>✗</>',
            'skip' => '<fg=gray>-</>',
            default => '<fg=gray>·</>',
        };
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function renderTable(string $name, array $headers, array $rows): void
    {
        $this->data[$this->currentGroup][$name] = array_map(
            fn (array $row): array => array_combine($headers, $row),
            $rows
        );

        if ($this->asJson || $rows === []) {
            return;
        }

        $this->newLine();
        $this->table($headers, $rows);
    }

    protected function section(string $title): void
    {
        if ($this->asJson) {
            return;
        }

        $this->line(str_repeat('=', 64));
        $this->info($title);
        $this->line(str_repeat('=', 64));
    }

    protected function blank(): void
    {
        if (! $this->asJson) {
            $this->newLine();
        }
    }

    protected function finish(): int
    {
        $counts = array_count_values(array_column($this->results, 'status')) + [
            'ok' => 0,
            'warn' => 0,
            'fail' => 0,
            'info' => 0,
            'skip' => 0,
        ];

        if ($this->asJson) {
            $this->line(json_encode([
                'domain' => config('pixelfed.domain.app'),
                'version' => config('pixelfed.version'),
                'generated_at' => now()->toIso8601String(),
                'healthy' => $counts['fail'] === 0,
                'summary' => $counts,
                'results' => $this->results,
                'data' => $this->data,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $counts['fail'] === 0 ? self::SUCCESS : self::FAILURE;
        }

        $this->section('SUMMARY');
        $this->line(sprintf(
            '  %d ok, %d warning(s), %d failure(s), %d skipped',
            $counts['ok'],
            $counts['warn'],
            $counts['fail'],
            $counts['skip']
        ));

        foreach (['fail' => 'FAILURES', 'warn' => 'WARNINGS'] as $status => $heading) {
            $matching = array_filter($this->results, fn (array $result): bool => $result['status'] === $status);

            if ($matching === []) {
                continue;
            }

            $this->newLine();

            if ($status === 'fail') {
                $this->error($heading.':');
            } else {
                $this->warn($heading.':');
            }

            foreach ($matching as $result) {
                $this->line('  '.$this->icon($status).' ['.$result['group'].'] '.OutputFormatter::escape($result['label']));
            }
        }

        if ($counts['fail'] === 0 && $counts['warn'] === 0) {
            $this->newLine();
            $this->info('No federation issues detected.');
        }

        return $counts['fail'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
