<?php

namespace App\Federation\Validators;

use App\Models\Profile;
use App\Util\ActivityPub\Helpers;

class DirectMessageValidator
{
    public const PUBLIC_URIS = [
        'https://www.w3.org/ns/activitystreams#Public',
        'as:Public',
        'Public',
    ];

    /**
     * Everyone the object is addressed to, as a flat list of ids.
     *
     * @return array<int, string>
     */
    public static function audience(array $object): array
    {
        $audience = [];

        foreach (['to', 'cc'] as $field) {
            $value = $object[$field] ?? [];

            if (is_string($value)) {
                $value = [$value];
            }

            if (! is_array($value)) {
                continue;
            }

            foreach ($value as $entry) {
                if (is_array($entry)) {
                    $entry = $entry['id'] ?? null;
                }

                if (is_string($entry) && $entry !== '') {
                    $audience[] = $entry;
                }
            }
        }

        return array_values(array_unique($audience));
    }

    /**
     * A Note is direct when it is addressed to people and nothing else: not
     * the public collection and not anyone's followers.
     *
     * This has to be decided before a Note is considered as a post or a
     * reply. Those paths store anything that is not public as followers-only,
     * which would show a private message to the sender's local followers.
     */
    public static function isDirect(array $object, Profile $actor): bool
    {
        if (($object['type'] ?? null) !== 'Note') {
            return false;
        }

        $audience = self::audience($object);

        if (empty($audience)) {
            return false;
        }

        foreach ($audience as $uri) {
            if (in_array($uri, self::PUBLIC_URIS, true)) {
                return false;
            }

            if (self::isFollowersCollection($uri, $actor)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Followers collections do not share a URL shape across software, but
     * every known implementation has a /followers segment in it.
     */
    public static function isFollowersCollection(string $uri, Profile $actor): bool
    {
        if ($actor->followers_url && $uri === $actor->followers_url) {
            return true;
        }

        return str_contains(strtolower($uri), '/followers');
    }

    /**
     * The object itself is well formed. Whether the sender is allowed to
     * speak for it is checked by the inbox before this runs.
     */
    public static function validate(array $object): bool
    {
        $id = Helpers::pluckval($object['id'] ?? null);

        if (! is_string($id) || strlen($id) > 1024 || ! Helpers::validateUrl($id)) {
            return false;
        }

        if (isset($object['content']) && ! is_string($object['content'])) {
            return false;
        }

        if (isset($object['attachment']) && ! is_array($object['attachment'])) {
            return false;
        }

        $hasContent = isset($object['content']) && trim(strip_tags($object['content'])) !== '';
        $hasAttachment = ! empty($object['attachment']);

        if (! $hasContent && ! $hasAttachment) {
            return false;
        }

        if (isset($object['published'])) {
            $published = Helpers::pluckval($object['published']);

            if (! is_string($published) || ! Helpers::validateTimestamp($published)) {
                return false;
            }
        }

        return true;
    }
}
