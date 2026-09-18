<?php

namespace App\Services\Groups;

use App\Models\Group;
use App\Models\GroupComment;
use App\Models\GroupPost;
use App\Rules\ValidUrl;
use App\Services\ActivityPubFetchService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Validator;
use Purify;

class GroupActivityPubService
{
    const CACHE_KEY = 'pf:services:groups:ap:';

    public static function fetchGroup($url, $saveOnFetch = true)
    {
        $group = Group::where('remote_url', $url)->first();
        if ($group) {
            return $group;
        }

        $res = ActivityPubFetchService::get($url);
        if (! $res) {
            return $res;
        }
        $json = json_decode($res, true);
        $group = self::validateGroup($json);
        if (! $group) {
            return false;
        }
        if ($saveOnFetch) {
            return self::storeGroup($group);
        }

        return $group;
    }

    public static function fetchGroupPost($url, $saveOnFetch = true)
    {
        $group = GroupPost::where('remote_url', $url)->first();

        if ($group) {
            return $group;
        }

        $res = ActivityPubFetchService::get($url);
        if (! $res) {
            return 'invalid res';
        }
        $json = json_decode($res, true);
        if (! $json) {
            return 'invalid json';
        }
        if (isset($json['inReplyTo'])) {
            $comment = self::validateGroupComment($json);

            return self::storeGroupComment($comment);
        }

        $group = self::validateGroupPost($json);
        if ($saveOnFetch) {
            return self::storeGroupPost($group);
        }

        return $group;
    }

    public static function validateGroup($obj)
    {
        $validator = Validator::make($obj, [
            '@context' => 'required',
            'id' => ['required', 'url', new ValidUrl],
            'type' => 'required|in:Group',
            'preferredUsername' => 'required',
            'name' => 'required',
            'url' => ['sometimes', 'url', new ValidUrl],
            'inbox' => ['required', 'url', new ValidUrl],
            'outbox' => ['required', 'url', new ValidUrl],
            'followers' => ['required', 'url', new ValidUrl],
            'attributedTo' => 'required',
            'summary' => 'sometimes',
            'publicKey' => 'required',
            'publicKey.id' => 'required',
            'publicKey.owner' => ['required', 'url', 'same:id', new ValidUrl],
            'publicKey.publicKeyPem' => 'required',
        ]);

        if ($validator->fails()) {
            return false;
        }

        return $validator->validated();
    }

    public static function validateGroupPost($obj)
    {
        $validator = Validator::make($obj, [
            '@context' => 'required',
            'id' => ['required', 'url', new ValidUrl],
            'type' => 'required|in:Page,Note',
            'to' => 'required|array',
            'to.*' => ['required', 'url', new ValidUrl],
            'cc' => 'sometimes|array',
            'cc.*' => ['sometimes', 'url', new ValidUrl],
            'url' => ['sometimes', 'url', new ValidUrl],
            'attributedTo' => 'required',
            'name' => 'sometimes',
            'target' => 'sometimes',
            'audience' => 'sometimes',
            'inReplyTo' => 'sometimes',
            'content' => 'sometimes',
            'mediaType' => 'sometimes',
            'sensitive' => 'sometimes',
            'attachment' => 'sometimes',
            'published' => 'required',
        ]);

        if ($validator->fails()) {
            return false;
        }

        return $validator->validated();
    }

    public static function validateGroupComment($obj)
    {
        $validator = Validator::make($obj, [
            '@context' => 'required',
            'id' => ['required', 'url', new ValidUrl],
            'type' => 'required|in:Note',
            'to' => 'required|array',
            'to.*' => ['required', 'url', new ValidUrl],
            'cc' => 'sometimes|array',
            'cc.*' => ['sometimes', 'url', new ValidUrl],
            'url' => ['sometimes', 'url', new ValidUrl],
            'attributedTo' => 'required',
            'name' => 'sometimes',
            'target' => 'sometimes',
            'audience' => 'sometimes',
            'inReplyTo' => 'sometimes',
            'content' => 'sometimes',
            'mediaType' => 'sometimes',
            'sensitive' => 'sometimes',
            'published' => 'required',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
            // Dead return false;
        }

        return $validator->validated();
    }

    public static function getGroupFromPostActivity($groupPost)
    {
        if (isset($groupPost['audience']) && is_string($groupPost['audience'])) {
            return $groupPost['audience'];
        }

        if (
            isset(
                $groupPost['target'],
                $groupPost['target']['type'],
                $groupPost['target']['attributedTo']
            ) && $groupPost['target']['type'] == 'Collection'
        ) {
            return $groupPost['target']['attributedTo'];
        }

        return false;
    }

    public static function getActorFromPostActivity($groupPost)
    {
        if (! isset($groupPost['attributedTo'])) {
            return false;
        }

        $field = $groupPost['attributedTo'];

        if (is_string($field)) {
            return $field;
        }

        if (is_array($field) && count($field) === 1) {
            if (
                isset(
                    $field[0]['id'],
                    $field[0]['type']
                ) &&
                $field[0]['type'] === 'Person' &&
                is_string($field[0]['id'])
            ) {
                return $field[0]['id'];
            }
        }

        return false;
    }

    public static function getCaptionFromPostActivity($groupPost)
    {
        if (! isset($groupPost['name']) && isset($groupPost['content'])) {
            return Purify::clean(strip_tags($groupPost['content']));
        }

        if (isset($groupPost['name'], $groupPost['content'])) {
            return Purify::clean(strip_tags($groupPost['name'])).Purify::clean(strip_tags($groupPost['content']));
        }
    }

    public static function getSensitiveFromPostActivity($groupPost)
    {
        if (! isset($groupPost['sensitive'])) {
            return true;
        }

        if (isset($groupPost['sensitive']) && ! is_bool($groupPost['sensitive'])) {
            return true;
        }

        return boolval($groupPost['sensitive']);
    }

    public static function storeGroup($activity)
    {
        $group = new Group;
        $group->profile_id = null;
        $group->category_id = 1;
        $group->name = $activity['name'] ?? 'Untitled Group';
        $group->description = isset($activity['summary']) ? Purify::clean($activity['summary']) : null;
        $group->is_private = false;
        $group->local_only = false;
        $group->metadata = [];
        $group->local = false;
        $group->remote_url = $activity['id'];
        $group->inbox_url = $activity['inbox'];
        $group->activitypub = true;
        $group->save();

        return $group;
    }

    public static function storeGroupPost($groupPost)
    {
        $groupUrl = self::getGroupFromPostActivity($groupPost);
        if (! $groupUrl) {
            return;
        }
        $group = self::fetchGroup($groupUrl, true);
        if (! $group) {
            return;
        }
        $actorUrl = self::getActorFromPostActivity($groupPost);
        $actor = Helpers::profileFetch($actorUrl);
        $caption = self::getCaptionFromPostActivity($groupPost);
        $sensitive = self::getSensitiveFromPostActivity($groupPost);
        $model = GroupPost::firstOrCreate(
            [
                'remote_url' => $groupPost['id'],
            ], [
                'group_id' => $group->id,
                'profile_id' => $actor->id,
                'type' => 'text',
                'caption' => $caption,
                'visibility' => 'public',
                'is_nsfw' => $sensitive,
            ]
        );

        return $model;
    }

    public static function storeGroupComment($groupPost)
    {
        $groupUrl = self::getGroupFromPostActivity($groupPost);
        if (! $groupUrl) {
            return;
        }
        $group = self::fetchGroup($groupUrl, true);
        if (! $group) {
            return;
        }
        $actorUrl = self::getActorFromPostActivity($groupPost);
        $actor = Helpers::profileFetch($actorUrl);
        $caption = self::getCaptionFromPostActivity($groupPost);
        $sensitive = self::getSensitiveFromPostActivity($groupPost);
        $parentPost = self::fetchGroupPost($groupPost['inReplyTo']);
        $model = GroupComment::firstOrCreate(
            [
                'remote_url' => $groupPost['id'],
            ], [
                'group_id' => $group->id,
                'profile_id' => $actor->id,
                'status_id' => $parentPost->id,
                'type' => 'text',
                'caption' => $caption,
                'visibility' => 'public',
                'is_nsfw' => $sensitive,
                'local' => $actor->private_key != null,
            ]
        );

        return $model;
    }
}
