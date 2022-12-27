<?php

/**
 * @author     Mike Cochrane <mikec@mikenz.geek.nz>
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Mike Cochrane, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 */

namespace App\Util\Lexer;

use Illuminate\Support\Str;
use App\Status;
use App\Services\AutolinkService;
use App\Services\TrendingHashtagService;

/**
 * Twitter Extractor Class.
 *
 * Parses tweets and extracts URLs, usernames, username/list pairs and
 * hashtags.
 *
 * Originally written by {@link http://github.com/mikenz Mike Cochrane}, this
 * is based on code by {@link http://github.com/mzsanford Matt Sanford} and
 * heavily modified by {@link http://github.com/ngnpope Nick Pope}.
 *
 * @author     Mike Cochrane <mikec@mikenz.geek.nz>
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Mike Cochrane, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 */
class Extractor extends Regex
{
    /**
     * @var bool
     */
    protected $extractURLWithoutProtocol = true;
    protected $activeUsersOnly = false;

    /**
     * Provides fluent method chaining.
     *
     * @param string $tweet The tweet to be converted.
     *
     * @see  __construct()
     *
     * @return Extractor
     */
    public static function create($tweet = null)
    {
        return new self($tweet);
    }

    public function setActiveUsersOnly($active)
    {
    	$this->activeUsersOnly = $active;
    	return $this;
    }

    /**
     * Reads in a tweet to be parsed and extracts elements from it.
     *
     * Extracts various parts of a tweet including URLs, usernames, hashtags...
     *
     * @param string $tweet The tweet to extract.
     */
    public function __construct($tweet = null)
    {
        parent::__construct($tweet);
    }

    /**
     * Extracts all parts of a tweet and returns an associative array containing
     * the extracted elements.
     *
     * @param string $tweet The tweet to extract.
     *
     * @return array The elements in the tweet.
     */
    public function extract($tweet = null)
    {
        if (is_null($tweet)) {
            $tweet = $this->tweet;
        }

        return [
            'hashtags'              => $this->extractHashtags($tweet),
            'urls'                  => $this->extractURLs($tweet),
            'mentions'              => $this->extractMentionedUsernames($tweet),
            'replyto'               => $this->extractRepliedUsernames($tweet),
            'hashtags_with_indices' => $this->extractHashtagsWithIndices($tweet),
            'urls_with_indices'     => $this->extractURLsWithIndices($tweet),
            'mentions_with_indices' => $this->extractMentionedUsernamesWithIndices($tweet),
        ];
    }

    /**
     * Extract URLs, @mentions, lists and #hashtag from a given text/tweet.
     *
     * @param string $tweet The tweet to extract.
     *
     * @return array list of extracted entities
     */
    public function extractEntitiesWithIndices($tweet = null)
    {
        if (is_null($tweet)) {
            $tweet = $this->tweet;
        }
        $entities = [];
        $entities = array_merge($entities, $this->extractURLsWithIndices($tweet));
        $entities = array_merge($entities, $this->extractHashtagsWithIndices($tweet, false));
        $entities = array_merge($entities, $this->extractMentionsOrListsWithIndices($tweet));
        $entities = $this->removeOverlappingEntities($entities);

        return $entities;
    }

    /**
     * Extracts all the hashtags from the tweet.
     *
     * @param string $tweet The tweet to extract.
     *
     * @return array The hashtag elements in the tweet.
     */
    public function extractHashtags($tweet = null)
    {
        $hashtagsOnly = [];
        $hashtagsWithIndices = $this->extractHashtagsWithIndices($tweet);

        foreach ($hashtagsWithIndices as $hashtagWithIndex) {
            $hashtagsOnly[] = $hashtagWithIndex['hashtag'];
        }

        return array_slice($hashtagsOnly, 0, Status::MAX_HASHTAGS);
    }

    /**
     * Extracts all the cashtags from the tweet.
     *
     * @param string $tweet The tweet to extract.
     *
     * @return array The cashtag elements in the tweet.
     */
    public function extractCashtags($tweet = null)
    {
        $cashtagsOnly = [];
        return $cashtagsOnly;
    }

    /**
     * Extracts all the URLs from the tweet.
     *
     * @param string $tweet The tweet to extract.
     *
     * @return array The URL elements in the tweet.
     */
    public function extractURLs($tweet = null)
    {
        $urlsOnly = [];
        $urlsWithIndices = $this->extractURLsWithIndices($tweet);

        foreach ($urlsWithIndices as $urlWithIndex) {
            $urlsOnly[] = $urlWithIndex['url'];
        }

        return array_slice($urlsOnly, 0, Status::MAX_LINKS);
    }

    /**
     * Extract all the usernames from the tweet.
     *
     * A mention is an occurrence of a username anywhere in a tweet.
     *
     * @param string $tweet The tweet to extract.
     *
     * @return array The usernames elements in the tweet.
     */
    public function extractMentionedScreennames($tweet = null)
    {
        $usernamesOnly = [];
        $mentionsWithIndices = $this->extractMentionsOrListsWithIndices($tweet);

        foreach ($mentionsWithIndices as $mentionWithIndex) {
        	if($this->activeUsersOnly == true) {
        		if(!AutolinkService::mentionedUsernameExists($mentionWithIndex['screen_name'])) {
        			continue;
        		}
        	}

            $screen_name = mb_strtolower($mentionWithIndex['screen_name']);
            if (empty($screen_name) or in_array($screen_name, $usernamesOnly)) {
                continue;
            }
            $usernamesOnly[] = $screen_name;
        }

        return $usernamesOnly;
    }

    /**
     * Extract all the usernames from the tweet.
     *
     * A mention is an occurrence of a username anywhere in a tweet.
     *
     * @return array The usernames elements in the tweet.
     *
     * @deprecated since version 1.1.0
     */
    public function extractMentionedUsernames($tweet)
    {
        $this->tweet = $tweet;

        return $this->extractMentionedScreennames($tweet);
    }

    /**
     * Extract all the usernames replied to from the tweet.
     *
     * A reply is an occurrence of a username at the beginning of a tweet.
     *
     * @param string $tweet The tweet to extract.
     *
     * @return array The usernames replied to in a tweet.
     */
    public function extractReplyScreenname($tweet = null)
    {
        if (is_null($tweet)) {
            $tweet = $this->tweet;
        }
        $matched = preg_match(self::$patterns['valid_reply'], $tweet, $matches);
        // Check username ending in
        if ($matched && preg_match(self::$patterns['end_mention_match'], $matches[2])) {
            $matched = false;
        }

        return $matched ? $matches[1] : null;
    }

    /**
     * Extract all the usernames replied to from the tweet.
     *
     * A reply is an occurrence of a username at the beginning of a tweet.
     *
     * @return array The usernames replied to in a tweet.
     *
     * @deprecated since version 1.1.0
     */
    public function extractRepliedUsernames()
    {
        return $this->extractReplyScreenname();
    }

    /**
     * Extracts all the hashtags and the indices they occur at from the tweet.
     *
     * @param string $tweet           The tweet to extract.
     * @param bool   $checkUrlOverlap if true, check if extracted hashtags overlap URLs and remove overlapping ones
     *
     * @return array The hashtag elements in the tweet.
     */
    public function extractHashtagsWithIndices($tweet = null, $checkUrlOverlap = true)
    {
        if (is_null($tweet)) {
            $tweet = $this->tweet;
        }

        if (!preg_match('/[#＃]/iu', $tweet)) {
            return [];
        }

        $bannedTags = config('app.env') === 'production' ? TrendingHashtagService::getBannedHashtagNames() : [];

        preg_match_all(self::$patterns['valid_hashtag'], $tweet, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $tags = [];

        foreach ($matches as $match) {
            list($all, $before, $hash, $hashtag, $outer) = array_pad($match, 3, ['', 0]);
            $start_position = $hash[1] > 0 ? StringUtils::strlen(substr($tweet, 0, $hash[1])) : $hash[1];
            $end_position = $start_position + StringUtils::strlen($hash[0].$hashtag[0]);

            if (preg_match(self::$patterns['end_hashtag_match'], $outer[0])) {
                continue;
            }
            if (count($bannedTags)) {
                if(in_array(strtolower($hashtag[0]), array_map('strtolower', $bannedTags))) {
                    continue;
                }
            }
            if (mb_strlen($hashtag[0]) > 124) {
                continue;
            }
            $tags[] = [
                'hashtag' => $hashtag[0],
                'indices' => [$start_position, $end_position],
            ];
        }

        if (!$checkUrlOverlap) {
            return array_slice($tags, 0, Status::MAX_HASHTAGS);
        }

        // check url overlap
        $urls = $this->extractURLsWithIndices($tweet);
        $entities = $this->removeOverlappingEntities(array_merge($tags, $urls));

        $validTags = [];
        foreach ($entities as $entity) {
            if (empty($entity['hashtag'])) {
                continue;
            }
            $validTags[] = $entity;
        }

        return array_slice($validTags, 0, Status::MAX_HASHTAGS);
    }

    /**
     * Extracts all the cashtags and the indices they occur at from the tweet.
     *
     * @param string $tweet The tweet to extract.
     *
     * @return array The cashtag elements in the tweet.
     */
    public function extractCashtagsWithIndices($tweet = null)
    {
    }

    /**
     * Extracts all the URLs and the indices they occur at from the tweet.
     *
     * @param string $tweet The tweet to extract.
     *
     * @return array The URLs elements in the tweet.
     */
    public function extractURLsWithIndices($tweet = null)
    {
        if (is_null($tweet)) {
            $tweet = $this->tweet;
        }

        $needle = $this->extractURLWithoutProtocol() ? '.' : ':';
        if (strpos($tweet, $needle) === false) {
            return [];
        }

        $urls = [];
        preg_match_all(self::$patterns['valid_url'], $tweet, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            list($all, $before, $url, $protocol, $domain, $port, $path, $query) = array_pad($match, 8, ['']);
            $start_position = $url[1] > 0 ? StringUtils::strlen(substr($tweet, 0, $url[1])) : $url[1];
            $end_position = $start_position + StringUtils::strlen($url[0]);

            $all = $all[0];
            $before = $before[0];
            $url = $url[0];
            $protocol = $protocol[0];
            $domain = $domain[0];
            $port = $port[0];
            $path = $path[0];
            $query = $query[0];

            // If protocol is missing and domain contains non-ASCII characters,
            // extract ASCII-only domains.
            if (empty($protocol)) {
                if (!$this->extractURLWithoutProtocol || preg_match(self::$patterns['invalid_url_without_protocol_preceding_chars'], $before)) {
                    continue;
                }

                $last_url = null;
                $ascii_end_position = 0;

                if (preg_match(self::$patterns['valid_ascii_domain'], $domain, $asciiDomain)) {
                    $asciiDomain[0] = preg_replace('/'.preg_quote($domain, '/').'/u', $asciiDomain[0], $url);
                    $ascii_start_position = StringUtils::strpos($domain, $asciiDomain[0], $ascii_end_position);
                    $ascii_end_position = $ascii_start_position + StringUtils::strlen($asciiDomain[0]);
                    $last_url = [
                        'url'     => $asciiDomain[0],
                        'indices' => [$start_position + $ascii_start_position, $start_position + $ascii_end_position],
                    ];
                    if (!empty($path)
                        || preg_match(self::$patterns['valid_special_short_domain'], $asciiDomain[0])
                        || !preg_match(self::$patterns['invalid_short_domain'], $asciiDomain[0])) {
                        $urls[] = $last_url;
                    }
                }

                // no ASCII-only domain found. Skip the entire URL
                if (empty($last_url)) {
                    continue;
                }

                // $last_url only contains domain. Need to add path and query if they exist.
                if (!empty($path)) {
                    // last_url was not added. Add it to urls here.
                    $last_url['url'] = preg_replace('/'.preg_quote($domain, '/').'/u', $last_url['url'], $url);
                    $last_url['indices'][1] = $end_position;
                }
            } else {
                // In the case of t.co URLs, don't allow additional path characters
                if (preg_match(self::$patterns['valid_tco_url'], $url, $tcoUrlMatches)) {
                    $url = $tcoUrlMatches[0];
                    $end_position = $start_position + StringUtils::strlen($url);
                }
                $urls[] = [
                    'url'     => $url,
                    'indices' => [$start_position, $end_position],
                ];
            }
        }

        return array_slice($urls, 0, Status::MAX_LINKS);
    }

    /**
     * Extracts all the usernames and the indices they occur at from the tweet.
     *
     * @param string $tweet The tweet to extract.
     *
     * @return array The username elements in the tweet.
     */
    public function extractMentionedScreennamesWithIndices($tweet = null)
    {
        if (is_null($tweet)) {
            $tweet = $this->tweet;
        }

        $usernamesOnly = [];
        $mentions = $this->extractMentionsOrListsWithIndices($tweet);
        foreach ($mentions as $mention) {
            if (isset($mention['list_slug'])) {
                unset($mention['list_slug']);
            }
            $usernamesOnly[] = $mention;
        }

        return array_slice($usernamesOnly, 0, Status::MAX_MENTIONS);
    }

    /**
     * Extracts all the usernames and the indices they occur at from the tweet.
     *
     * @return array The username elements in the tweet.
     *
     * @deprecated since version 1.1.0
     */
    public function extractMentionedUsernamesWithIndices()
    {
        return $this->extractMentionedScreennamesWithIndices();
    }

    /**
     * Extracts all the usernames and the indices they occur at from the tweet.
     *
     * @param string $tweet The tweet to extract.
     *
     * @return array The username elements in the tweet.
     */
    public function extractMentionsOrListsWithIndices($tweet = null)
    {
        if (is_null($tweet)) {
            $tweet = $this->tweet;
        }

        if (!preg_match('/[@＠]/iu', $tweet)) {
            return [];
        }

        preg_match_all(self::$patterns['valid_mentions_or_lists'], $tweet, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $results = [];

        foreach ($matches as $match) {
            list($all, $before, $at, $username, $list_slug, $outer) = array_pad($match, 6, ['', 0]);
            $start_position = $at[1] > 0 ? StringUtils::strlen(substr($tweet, 0, $at[1])) : $at[1];
            $end_position = $start_position + StringUtils::strlen($at[0]) + StringUtils::strlen($username[0]);
            $screenname = trim($all[0]) == '@'.$username[0] ? $username[0] : trim($all[0]);

            if($this->activeUsersOnly == true) {
        		if(!AutolinkService::mentionedUsernameExists($screenname)) {
        			continue;
        		}
        	}

            $entity = [
                'screen_name' => $screenname,
                'list_slug'   => $list_slug[0],
                'indices'     => [$start_position, $end_position],
            ];

            if (preg_match(self::$patterns['end_mention_match'], $outer[0])) {
                continue;
            }

            if (!empty($list_slug[0])) {
                $entity['indices'][1] = $end_position + StringUtils::strlen($list_slug[0]);
            }

            $results[] = $entity;
        }

        return array_slice($results, 0, Status::MAX_MENTIONS);
    }

    /**
     * Extracts all the usernames and the indices they occur at from the tweet.
     *
     * @return array The username elements in the tweet.
     *
     * @deprecated since version 1.1.0
     */
    public function extractMentionedUsernamesOrListsWithIndices()
    {
        return $this->extractMentionsOrListsWithIndices();
    }

    /**
     * setter/getter for extractURLWithoutProtocol.
     *
     * @param bool $flag
     *
     * @return Extractor
     */
    public function extractURLWithoutProtocol($flag = null)
    {
        if (is_null($flag)) {
            return $this->extractURLWithoutProtocol;
        }
        $this->extractURLWithoutProtocol = (bool) $flag;

        return $this;
    }

    /**
     * Remove overlapping entities.
     * This returns a new array with no overlapping entities.
     *
     * @param array $entities
     *
     * @return array
     */
    public function removeOverlappingEntities($entities)
    {
        $result = [];
        usort($entities, [$this, 'sortEntites']);

        $prev = null;
        foreach ($entities as $entity) {
            if (isset($prev) && $entity['indices'][0] < $prev['indices'][1]) {
                continue;
            }
            $prev = $entity;
            $result[] = $entity;
        }

        return $result;
    }

    /**
     * sort by entity start index.
     *
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    protected function sortEntites($a, $b)
    {
        if ($a['indices'][0] == $b['indices'][0]) {
            return 0;
        }

        return ($a['indices'][0] < $b['indices'][0]) ? -1 : 1;
    }
}
