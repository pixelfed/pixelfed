<?php

namespace App\Jobs\InboxPipeline\Concerns;

/**
 * Matches the key owner against the actor an activity claims to be from.
 *
 * A same-host check is not enough on a multi-user instance: every local or
 * remote account on a host shares the same domain, so comparing only the host
 * lets one account sign activities on behalf of another on the same host. The
 * owner of the signing key has to be the exact actor the activity names.
 */
trait MatchesActorUrl
{
    /**
     * Exact actor identity match. Scheme and host are case-insensitive,
     * path is not, a single trailing slash is ignored. Query and fragment
     * are part of the comparison so they cannot be used to alias an actor.
     */
    protected static function sameActorUrl($a, $b): bool
    {
        $a = self::normalizeUrl($a);
        $b = self::normalizeUrl($b);

        return $a !== null && $b !== null && $a === $b;
    }

    protected static function normalizeUrl($url)
    {
        if (! is_string($url) || $url === '') {
            return null;
        }
        $p = parse_url($url);
        if (! $p || empty($p['scheme']) || empty($p['host'])) {
            return null;
        }
        $out = strtolower($p['scheme']).'://'.strtolower($p['host']);
        if (isset($p['port'])) {
            $out .= ':'.$p['port'];
        }
        $out .= rtrim($p['path'] ?? '', '/');
        if (isset($p['query'])) {
            $out .= '?'.$p['query'];
        }
        if (isset($p['fragment'])) {
            $out .= '#'.$p['fragment'];
        }

        return $out;
    }
}
