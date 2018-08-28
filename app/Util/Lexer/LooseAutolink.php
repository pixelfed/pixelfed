<?php

/**
 * @author     Mike Cochrane <mikec@mikenz.geek.nz>
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @author     Takashi Nojima
 * @copyright  Copyright 2014 Mike Cochrane, Nick Pope, Takashi Nojima
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 */

namespace App\Util\Lexer;

/**
 * Twitter LooseAutolink Class.
 *
 * Parses tweets and generates HTML anchor tags around URLs, usernames,
 * username/list pairs and hashtags.
 *
 * Originally written by {@link http://github.com/mikenz Mike Cochrane}, this
 * is based on code by {@link http://github.com/mzsanford Matt Sanford} and
 * heavily modified by {@link http://github.com/ngnpope Nick Pope}.
 *
 * @author     Mike Cochrane <mikec@mikenz.geek.nz>
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @author     Takashi Nojima
 * @copyright  Copyright 2014 Mike Cochrane, Nick Pope, Takashi Nojima
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 *
 * @since      1.8.0
 * @deprecated since version 1.9.0
 */
class LooseAutolink extends Autolink
{
    /**
     * Auto-link hashtags, URLs, usernames and lists.
     *
     * @param  string The tweet to be converted
     *
     * @return string that auto-link HTML added
     *
     * @deprecated since version 1.9.0
     */
    public function autoLink($tweet = null)
    {
        if (!is_null($tweet)) {
            $this->tweet = $tweet;
        }

        return $this->addLinks();
    }

    /**
     * Auto-link the @username and @username/list references in the provided text. Links to @username references will
     * have the usernameClass CSS classes added. Links to @username/list references will have the listClass CSS class
     * added.
     *
     * @return string that auto-link HTML added
     */
    public function autoLinkUsernamesAndLists($tweet = null)
    {
        if (!is_null($tweet)) {
            $this->tweet = $tweet;
        }

        return $this->addLinksToUsernamesAndLists();
    }

    /**
     * Auto-link #hashtag references in the provided Tweet text. The #hashtag links will have the hashtagClass CSS class
     * added.
     *
     * @return string that auto-link HTML added
     */
    public function autoLinkHashtags($tweet = null)
    {
        if (!is_null($tweet)) {
            $this->tweet = $tweet;
        }

        return $this->addLinksToHashtags();
    }

    /**
     * Auto-link URLs in the Tweet text provided.
     * <p/>
     * This only auto-links URLs with protocol.
     *
     * @return string that auto-link HTML added
     */
    public function autoLinkURLs($tweet = null)
    {
        if (!is_null($tweet)) {
            $this->tweet = $tweet;
        }

        return $this->addLinksToURLs();
    }

    /**
     * Auto-link $cashtag references in the provided Tweet text. The $cashtag links will have the cashtagClass CSS class
     * added.
     *
     * @return string that auto-link HTML added
     */
    public function autoLinkCashtags($tweet = null)
    {
        if (!is_null($tweet)) {
            $this->tweet = $tweet;
        }

        return $this->addLinksToCashtags();
    }

    /**
     * Adds links to all elements in the tweet.
     *
     * @return string The modified tweet.
     *
     * @deprecated since version 1.9.0
     */
    public function addLinks()
    {
        $original = $this->tweet;
        $this->tweet = $this->addLinksToURLs();
        $this->tweet = $this->addLinksToHashtags();
        $this->tweet = $this->addLinksToCashtags();
        $this->tweet = $this->addLinksToUsernamesAndLists();
        $modified = $this->tweet;
        $this->tweet = $original;

        return $modified;
    }

    /**
     * Adds links to hashtag elements in the tweet.
     *
     * @return string The modified tweet.
     */
    public function addLinksToHashtags()
    {
        return preg_replace_callback(
            self::$patterns['valid_hashtag'],
            [$this, '_addLinksToHashtags'],
            $this->tweet
        );
    }

    /**
     * Adds links to cashtag elements in the tweet.
     *
     * @return string The modified tweet.
     */
    public function addLinksToCashtags()
    {
        return preg_replace_callback(
            self::$patterns['valid_cashtag'],
            [$this, '_addLinksToCashtags'],
            $this->tweet
        );
    }

    /**
     * Adds links to URL elements in the tweet.
     *
     * @return string The modified tweet
     */
    public function addLinksToURLs()
    {
        return preg_replace_callback(self::$patterns['valid_url'], [$this, '_addLinksToURLs'], $this->tweet);
    }

    /**
     * Adds links to username/list elements in the tweet.
     *
     * @return string The modified tweet.
     */
    public function addLinksToUsernamesAndLists()
    {
        return preg_replace_callback(
            self::$patterns['valid_mentions_or_lists'],
            [$this, '_addLinksToUsernamesAndLists'],
            $this->tweet
        );
    }

    /**
     * Wraps a tweet element in an HTML anchor tag using the provided URL.
     *
     * This is a helper function to perform the generation of the link.
     *
     * @param string $url     The URL to use as the href.
     * @param string $class   The CSS class(es) to apply (space separated).
     * @param string $element The tweet element to wrap.
     *
     * @return string The tweet element with a link applied.
     *
     * @deprecated since version 1.1.0
     */
    protected function wrap($url, $class, $element)
    {
        $link = '<a';
        if ($class) {
            $link .= ' class="'.$class.'"';
        }
        $link .= ' href="'.$url.'"';
        $rel = [];
        if ($this->external) {
            $rel[] = 'external';
        }
        if ($this->nofollow) {
            $rel[] = 'nofollow';
        }
        if (!empty($rel)) {
            $link .= ' rel="'.implode(' ', $rel).'"';
        }
        if ($this->target) {
            $link .= ' target="'.$this->target.'"';
        }
        $link .= '>'.$element.'</a>';

        return $link;
    }

    /**
     * Wraps a tweet element in an HTML anchor tag using the provided URL.
     *
     * This is a helper function to perform the generation of the hashtag link.
     *
     * @param string $url     The URL to use as the href.
     * @param string $class   The CSS class(es) to apply (space separated).
     * @param string $element The tweet element to wrap.
     *
     * @return string The tweet element with a link applied.
     */
    protected function wrapHash($url, $class, $element)
    {
        $title = preg_replace('/＃/u', '#', $element);
        $link = '<a';
        $link .= ' href="'.$url.'"';
        $link .= ' title="'.$title.'"';
        if ($class) {
            $link .= ' class="'.$class.'"';
        }
        $rel = [];
        if ($this->external) {
            $rel[] = 'external';
        }
        if ($this->nofollow) {
            $rel[] = 'nofollow';
        }
        if (!empty($rel)) {
            $link .= ' rel="'.implode(' ', $rel).'"';
        }
        if ($this->target) {
            $link .= ' target="'.$this->target.'"';
        }
        $link .= '>'.$element.'</a>';

        return $link;
    }

    /**
     * Callback used by the method that adds links to hashtags.
     *
     * @see  addLinksToHashtags()
     *
     * @param array $matches The regular expression matches.
     *
     * @return string The link-wrapped hashtag.
     */
    protected function _addLinksToHashtags($matches)
    {
        list($all, $before, $hash, $tag, $after) = array_pad($matches, 5, '');
        if (preg_match(self::$patterns['end_hashtag_match'], $after)
            || (!preg_match('!\A["\']!', $before) && preg_match('!\A["\']!', $after)) || preg_match('!\A</!', $after)) {
            return $all;
        }
        $replacement = $before;
        $element = $hash.$tag;
        $url = $this->url_base_hash.$tag;
        $class_hash = $this->class_hash;
        if (preg_match(self::$patterns['rtl_chars'], $element)) {
            $class_hash .= ' rtl';
        }
        $replacement .= $this->wrapHash($url, $class_hash, $element);

        return $replacement;
    }

    /**
     * Callback used by the method that adds links to cashtags.
     *
     * @see  addLinksToCashtags()
     *
     * @param array $matches The regular expression matches.
     *
     * @return string The link-wrapped cashtag.
     */
    protected function _addLinksToCashtags($matches)
    {
        list($all, $before, $cash, $tag, $after) = array_pad($matches, 5, '');
        if (preg_match(self::$patterns['end_cashtag_match'], $after)
            || (!preg_match('!\A["\']!', $before) && preg_match('!\A["\']!', $after)) || preg_match('!\A</!', $after)) {
            return $all;
        }
        $replacement = $before;
        $element = $cash.$tag;
        $url = $this->url_base_cash.$tag;
        $replacement .= $this->wrapHash($url, $this->class_cash, $element);

        return $replacement;
    }

    /**
     * Callback used by the method that adds links to URLs.
     *
     * @see  addLinksToURLs()
     *
     * @param array $matches The regular expression matches.
     *
     * @return string The link-wrapped URL.
     */
    protected function _addLinksToURLs($matches)
    {
        list($all, $before, $url, $protocol, $domain, $path, $query) = array_pad($matches, 7, '');
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8', false);
        if (!$protocol) {
            return $all;
        }

        return $before.$this->wrap($url, $this->class_url, $url);
    }

    /**
     * Callback used by the method that adds links to username/list pairs.
     *
     * @see  addLinksToUsernamesAndLists()
     *
     * @param array $matches The regular expression matches.
     *
     * @return string The link-wrapped username/list pair.
     */
    protected function _addLinksToUsernamesAndLists($matches)
    {
        list($all, $before, $at, $username, $slash_listname, $after) = array_pad($matches, 6, '');
        // If $after is not empty, there is an invalid character.
        if (!empty($slash_listname)) {
            // Replace the list and username
            $element = $username.$slash_listname;
            $class = $this->class_list;
            $url = $this->url_base_list.$element;
        } else {
            if (preg_match(self::$patterns['end_mention_match'], $after)) {
                return $all;
            }
            // Replace the username
            $element = $username;
            $class = $this->class_user;
            $url = $this->url_base_user.$element;
        }
        // XXX: Due to use of preg_replace_callback() for multiple replacements in a
        //      single tweet and also as only the match is replaced and we have to
        //      use a look-ahead for $after because there is no equivalent for the
        //      $' (dollar apostrophe) global from Ruby, we MUST NOT append $after.
        return $before.$at.$this->wrap($url, $class, $element);
    }
}
