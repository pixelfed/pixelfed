<?php

/**
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 */

namespace App\Util\Lexer;

/**
 * Twitter Validator Class.
 *
 * Performs "validation" on tweets.
 *
 * Originally written by {@link http://github.com/mikenz Mike Cochrane}, this
 * is based on code by {@link http://github.com/mzsanford Matt Sanford} and
 * heavily modified by {@link http://github.com/ngnpope Nick Pope}.
 *
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 */
class Validator extends Regex
{
    /**
     * The maximum length of a tweet.
     *
     * @var int
     */
    const MAX_LENGTH = 140;

    /**
     * The length of a short URL beginning with http:.
     *
     * @var int
     */
    protected $short_url_length = 23;

    /**
     * The length of a short URL beginning with http:.
     *
     * @var int
     */
    protected $short_url_length_https = 23;

    /**
     * @var Extractor
     */
    protected $extractor = null;

    /**
     * Provides fluent method chaining.
     *
     * @param string $tweet  The tweet to be validated.
     * @param mixed  $config Setup short URL length from Twitter API /help/configuration response.
     *
     * @see  __construct()
     *
     * @return Validator
     */
    public static function create($tweet = null, $config = null)
    {
        return new self($tweet, $config);
    }

    /**
     * Reads in a tweet to be parsed and validates it.
     *
     * @param string $tweet The tweet to validate.
     */
    public function __construct($tweet = null, $config = null)
    {
        parent::__construct($tweet);
        if (!empty($config)) {
            $this->setConfiguration($config);
        }
        $this->extractor = Extractor::create();
    }

    /**
     * Setup short URL length from Twitter API /help/configuration response.
     *
     * @param mixed $config
     *
     * @return Validator
     *
     * @link https://dev.twitter.com/docs/api/1/get/help/configuration
     */
    public function setConfiguration($config)
    {
        if (is_array($config)) {
            // setup from array
            if (isset($config['short_url_length'])) {
                $this->setShortUrlLength($config['short_url_length']);
            }
            if (isset($config['short_url_length_https'])) {
                $this->setShortUrlLengthHttps($config['short_url_length_https']);
            }
        } elseif (is_object($config)) {
            // setup from object
            if (isset($config->short_url_length)) {
                $this->setShortUrlLength($config->short_url_length);
            }
            if (isset($config->short_url_length_https)) {
                $this->setShortUrlLengthHttps($config->short_url_length_https);
            }
        }

        return $this;
    }

    /**
     * Set the length of a short URL beginning with http:.
     *
     * @param mixed $length
     *
     * @return Validator
     */
    public function setShortUrlLength($length)
    {
        $this->short_url_length = intval($length);

        return $this;
    }

    /**
     * Get the length of a short URL beginning with http:.
     *
     * @return int
     */
    public function getShortUrlLength()
    {
        return $this->short_url_length;
    }

    /**
     * Set the length of a short URL beginning with https:.
     *
     * @param mixed $length
     *
     * @return Validator
     */
    public function setShortUrlLengthHttps($length)
    {
        $this->short_url_length_https = intval($length);

        return $this;
    }

    /**
     * Get the length of a short URL beginning with https:.
     *
     * @return int
     */
    public function getShortUrlLengthHttps()
    {
        return $this->short_url_length_https;
    }

    /**
     * Check whether a tweet is valid.
     *
     * @param string $tweet The tweet to validate.
     *
     * @return bool Whether the tweet is valid.
     */
    public function isValidTweetText($tweet = null)
    {
        if (is_null($tweet)) {
            $tweet = $this->tweet;
        }
        $length = $this->getTweetLength($tweet);
        if (!$tweet || !$length) {
            return false;
        }
        if ($length > self::MAX_LENGTH) {
            return false;
        }
        if (preg_match(self::$patterns['invalid_characters'], $tweet)) {
            return false;
        }

        return true;
    }

    /**
     * Check whether a tweet is valid.
     *
     * @return bool Whether the tweet is valid.
     *
     * @deprecated since version 1.1.0
     */
    public function validateTweet()
    {
        return $this->isValidTweetText();
    }

    /**
     * Check whether a username is valid.
     *
     * @param string $username The username to validate.
     *
     * @return bool Whether the username is valid.
     */
    public function isValidUsername($username = null)
    {
        if (is_null($username)) {
            $username = $this->tweet;
        }
        $length = StringUtils::strlen($username);
        if (empty($username) || !$length) {
            return false;
        }
        $extracted = $this->extractor->extractMentionedScreennames($username);

        return count($extracted) === 1 && $extracted[0] === substr($username, 1);
    }

    /**
     * Check whether a username is valid.
     *
     * @return bool Whether the username is valid.
     *
     * @deprecated since version 1.1.0
     */
    public function validateUsername()
    {
        return $this->isValidUsername();
    }

    /**
     * Check whether a list is valid.
     *
     * @param string $list The list name to validate.
     *
     * @return bool Whether the list is valid.
     */
    public function isValidList($list = null)
    {
        if (is_null($list)) {
            $list = $this->tweet;
        }
        $length = StringUtils::strlen($list);
        if (empty($list) || !$length) {
            return false;
        }
        preg_match(self::$patterns['valid_mentions_or_lists'], $list, $matches);
        $matches = array_pad($matches, 5, '');

        return isset($matches) && $matches[1] === '' && $matches[4] && !empty($matches[4]) && $matches[5] === '';
    }

    /**
     * Check whether a list is valid.
     *
     * @return bool Whether the list is valid.
     *
     * @deprecated since version 1.1.0
     */
    public function validateList()
    {
        return $this->isValidList();
    }

    /**
     * Check whether a hashtag is valid.
     *
     * @param string $hashtag The hashtag to validate.
     *
     * @return bool Whether the hashtag is valid.
     */
    public function isValidHashtag($hashtag = null)
    {
        if (is_null($hashtag)) {
            $hashtag = $this->tweet;
        }
        $length = StringUtils::strlen($hashtag);
        if (empty($hashtag) || !$length) {
            return false;
        }
        $extracted = $this->extractor->extractHashtags($hashtag);

        return count($extracted) === 1 && $extracted[0] === substr($hashtag, 1);
    }

    /**
     * Check whether a hashtag is valid.
     *
     * @return bool Whether the hashtag is valid.
     *
     * @deprecated since version 1.1.0
     */
    public function validateHashtag()
    {
        return $this->isValidHashtag();
    }

    /**
     * Check whether a URL is valid.
     *
     * @param string $url              The url to validate.
     * @param bool   $unicode_domains  Consider the domain to be unicode.
     * @param bool   $require_protocol Require a protocol for valid domain?
     *
     * @return bool Whether the URL is valid.
     */
    public function isValidURL($url = null, $unicode_domains = true, $require_protocol = true)
    {
        if (is_null($url)) {
            $url = $this->tweet;
        }
        $length = StringUtils::strlen($url);
        if (empty($url) || !$length) {
            return false;
        }
        preg_match(self::$patterns['validate_url_unencoded'], $url, $matches);
        $match = array_shift($matches);
        if (!$matches || $match !== $url) {
            return false;
        }
        list($scheme, $authority, $path, $query, $fragment) = array_pad($matches, 5, '');
        // Check scheme, path, query, fragment:
        if (($require_protocol && !(
            self::isValidMatch($scheme, self::$patterns['validate_url_scheme']) && preg_match('/^https?$/i', $scheme))
            ) || !self::isValidMatch($path, self::$patterns['validate_url_path']) || !self::isValidMatch($query, self::$patterns['validate_url_query'], true)
            || !self::isValidMatch($fragment, self::$patterns['validate_url_fragment'], true)) {
            return false;
        }
        // Check authority:
        $authority_pattern = $unicode_domains ? 'validate_url_unicode_authority' : 'validate_url_authority';

        return self::isValidMatch($authority, self::$patterns[$authority_pattern]);
    }

    /**
     * Check whether a URL is valid.
     *
     * @param bool $unicode_domains  Consider the domain to be unicode.
     * @param bool $require_protocol Require a protocol for valid domain?
     *
     * @return bool Whether the URL is valid.
     *
     * @deprecated since version 1.1.0
     */
    public function validateURL($unicode_domains = true, $require_protocol = true)
    {
        return $this->isValidURL(null, $unicode_domains, $require_protocol);
    }

    /**
     * Determines the length of a tweet.  Takes shortening of URLs into account.
     *
     * @param string $tweet The tweet to validate.
     *
     * @return int the length of a tweet.
     */
    public function getTweetLength($tweet = null)
    {
        if (is_null($tweet)) {
            $tweet = $this->tweet;
        }
        $length = StringUtils::strlen($tweet);
        $urls_with_indices = $this->extractor->extractURLsWithIndices($tweet);
        foreach ($urls_with_indices as $x) {
            $length += $x['indices'][0] - $x['indices'][1];
            $length += stripos($x['url'], 'https://') === 0 ? $this->short_url_length_https : $this->short_url_length;
        }

        return $length;
    }

    /**
     * Determines the length of a tweet.  Takes shortening of URLs into account.
     *
     * @return int the length of a tweet.
     *
     * @deprecated since version 1.1.0
     */
    public function getLength()
    {
        return $this->getTweetLength();
    }

    /**
     * A helper function to check for a valid match.  Used in URL validation.
     *
     * @param string $string   The subject string to test.
     * @param string $pattern  The pattern to match against.
     * @param bool   $optional Whether a match is compulsory or not.
     *
     * @return bool Whether an exact match was found.
     */
    protected static function isValidMatch($string, $pattern, $optional = false)
    {
        $found = preg_match($pattern, $string, $matches);
        if (!$optional) {
            return ($string || $string === '') && $found && $matches[0] === $string;
        } else {
            return !(($string || $string === '') && (!$found || $matches[0] !== $string));
        }
    }
}
