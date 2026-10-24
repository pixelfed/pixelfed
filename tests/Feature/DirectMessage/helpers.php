<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserSetting;
use App\Util\ActivityPub\Inbox;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

if (! function_exists('dmLocalUser')) {
    /**
     * A local user. By default they take messages from everyone, so a test
     * about requests has to ask for that explicitly.
     */
    function dmLocalUser(bool $publicDm = true, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['created_at' => now()->subYear()], $attributes));
        $user->refresh();

        UserSetting::updateOrCreate(['user_id' => $user->id], ['public_dm' => $publicDm]);

        return $user;
    }

    function dmProfile(User $user): Profile
    {
        return Profile::findOrFail($user->profile_id);
    }

    function dmRemoteProfile(string $username = 'alice', string $domain = 'remote.example'): Profile
    {
        $actor = "https://{$domain}/users/{$username}";

        return Profile::factory()->remote()->create([
            'domain' => $domain,
            'username' => "@{$username}@{$domain}",
            'remote_url' => $actor,
            'key_id' => "{$actor}#main-key",
            'inbox_url' => "{$actor}/inbox",
            'sharedInbox' => "https://{$domain}/inbox",
            'last_fetched_at' => now(),
        ]);
    }

    /**
     * Seed the DNS and banned-domain caches so URL validation passes without
     * a network lookup. Call after factories, the lazy refresh can flush the
     * cache.
     */
    function dmSeedHosts(array $hosts = ['remote.example', 'other.example']): void
    {
        $hosts[] = config('pixelfed.domain.app');

        foreach ($hosts as $host) {
            Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.40'], 3600);
        }

        Cache::put('instances:banned:domains', [], 1209600);
    }

    /**
     * Give $b the id right after $a, the way two profiles created in the same
     * millisecond end up. Ids that close are equal once compared as floats,
     * so anything that orders or matches ids has to cope with it.
     */
    function dmNeighbour(Profile $a, Profile $b): Profile
    {
        DB::table('profiles')->where('id', $b->id)->update(['id' => $a->id + 1]);

        return Profile::findOrFail($a->id + 1);
    }

    function dmFollow(Profile $follower, Profile $target): void
    {
        Follower::create([
            'profile_id' => $follower->id,
            'following_id' => $target->id,
            'local_profile' => $follower->domain === null,
            'local_following' => $target->domain === null,
        ]);
    }

    /**
     * A direct Note the way Mastodon sends one: addressed to people only,
     * a Mention for each of them, and the thread identifiers.
     *
     * @param  array<int, Profile>  $recipients
     */
    function dmNote(Profile $author, string $path, array $recipients, array $overrides = []): array
    {
        $id = $author->remote_url.'/statuses/'.$path;
        $to = array_map(fn (Profile $profile) => $profile->permalink(), $recipients);
        $mentions = implode(' ', array_map(
            fn (Profile $profile) => '<span class="h-card"><a href="'.$profile->permalink().'" class="u-url mention">@<span>'.ltrim(explode('@', ltrim($profile->username, '@'))[0], '@').'</span></a></span>',
            $recipients
        ));

        return array_merge([
            'id' => $id,
            'type' => 'Note',
            'attributedTo' => $author->remote_url,
            'url' => "https://{$author->domain}/@user/{$path}",
            'content' => "<p>{$mentions} hello there</p>",
            'published' => now()->subMinute()->toAtomString(),
            'inReplyTo' => null,
            'to' => $to,
            'cc' => [],
            'sensitive' => false,
            'conversation' => "tag:{$author->domain},2026-09-21:objectId=1:objectType=Conversation",
            'context' => "https://{$author->domain}/contexts/1",
            'attachment' => [],
            'tag' => array_map(fn (Profile $profile) => [
                'type' => 'Mention',
                'href' => $profile->permalink(),
                'name' => '@'.ltrim($profile->username, '@'),
            ], $recipients),
        ], $overrides);
    }

    function dmDeliver(Profile $actor, array $object, string $type = 'Create'): void
    {
        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $object['id'].'/activity',
            'type' => $type,
            'actor' => $actor->remote_url,
            'object' => $object,
        ];

        if ($type === 'Create') {
            $payload['to'] = $object['to'] ?? [];
            $payload['cc'] = $object['cc'] ?? [];
        }

        $headers = [
            'signature' => ['keyId="'.$actor->key_id.'",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="dGVzdA=="'],
            'date' => [now()->toRfc7231String()],
        ];

        (new Inbox($headers, null, $payload))->handle();
    }
}
