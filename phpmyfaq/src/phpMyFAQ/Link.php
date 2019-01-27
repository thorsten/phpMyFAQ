<?php

namespace phpMyFAQ;

/**
 * Link management
 *
 * This class wrap the needs for managing an HTML anchor
 * taking into account also the HTML anchor creation
 * with specific handling for mod_rewrite PMF native support
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Link Class.
 *
 * @category  phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */
class Link
{
    /**
     * class constants.
     */
    const LINK_AMPERSAND = '&amp;';
    const LINK_CATEGORY = 'category/';
    const LINK_CONTENT = 'content/';
    const LINK_EQUAL = '=';
    const LINK_FRAGMENT_SEPARATOR = '#';
    const LINK_HTML_MINUS = '-';
    const LINK_HTML_UNDERSCORE = '_';
    const LINK_HTML_SLASH = '/';
    const LINK_HTML_TARGET_BLANK = '_blank';
    const LINK_HTML_TARGET_PARENT = '_parent';
    const LINK_HTML_TARGET_SELF = '_self';
    const LINK_HTML_TARGET_TOP = '_top';
    const LINK_NEWS = 'news/';
    const LINK_SITEMAP = 'sitemap/';
    const LINK_SLASH = '/';
    const LINK_SEARCHPART_SEPARATOR = '?';
    const LINK_TAGS = 'tags/';

    const LINK_INDEX_ADMIN = '/admin/index.php';
    const LINK_INDEX_HOME = '/index.php';

    const LINK_GET_ACTION = 'action';
    const LINK_GET_ARTLANG = 'artlang';
    const LINK_GET_CATEGORY = 'cat';
    const LINK_GET_HIGHLIGHT = 'highlight';
    const LINK_GET_ID = 'id';
    const LINK_GET_LANG = 'lang';
    const LINK_GET_LETTER = 'letter';
    const LINK_GET_NEWS_ID = 'newsid';
    const LINK_GET_NEWS_LANG = 'newslang';
    const LINK_GET_PAGE = 'seite';
    const LINK_GET_SIDS = 'SIDS';
    const LINK_GET_TAGGING_ID = 'tagging_id';
    const LINK_GET_LANGS = 'langs';

    const LINK_GET_ACTION_ADD = 'add';
    const LINK_GET_ACTION_FAQ = 'faq';
    const LINK_GET_ACTION_ASK = 'ask';
    const LINK_GET_ACTION_CONTACT = 'contact';
    const LINK_GET_ACTION_GLOSSARY = 'glossary';
    const LINK_GET_ACTION_HELP = 'help';
    const LINK_GET_ACTION_LOGIN = 'login';
    const LINK_GET_ACTION_NEWS = 'news';
    const LINK_GET_ACTION_OPEN = 'open';
    const LINK_GET_ACTION_PASSWORD = 'password';
    const LINK_GET_ACTION_REGISTER = 'register';
    const LINK_GET_ACTION_SEARCH = 'search';
    const LINK_GET_ACTION_SITEMAP = 'sitemap';
    const LINK_GET_ACTION_SHOW = 'show';

    const LINK_HTML_CATEGORY = 'category';
    const LINK_HTML_EXTENSION = '.html';
    const LINK_HTML_SITEMAP = 'sitemap';

    const LINK_HTML_ADDCONTENT = 'addcontent.html';
    const LINK_HTML_ASK = 'ask.html';
    const LINK_HTML_CONTACT = 'contact.html';
    const LINK_HTML_GLOSSARY = 'glossary.html';
    const LINK_HTML_HELP = 'help.html';
    const LINK_HTML_LOGIN = 'login.html';
    const LINK_HTML_OPEN = 'open.html';
    const LINK_HTML_PASSWORD = 'password.html';
    const LINK_HTML_REGISTER = 'register.html';
    const LINK_HTML_SEARCH = 'search.html';
    const LINK_HTML_SHOWCAT = 'showcat.html';

    /**
     * URL.
     *
     * @var string
     */
    public $url = '';

    /**
     * CSS class.
     *
     * @var string
     */
    public $class = '';

    /**
     * Linktext.
     *
     * @var string
     */
    public $text = '';

    /**
     * Tooltip.
     *
     * @var string
     */
    public $tooltip = '';

    /**
     * Target.
     *
     * @var string
     */
    public $target = '';

    /**
     * Name selector.
     *
     * @var string
     */
    public $name = '';

    /**
     * property specific to the SEO/SEF URLs.
     *
     * @var string
     */
    public $itemTitle = '';

    /**
     * Item property for HTML5 microdata.
     *
     * @var string
     */
    protected $itemprop = '';

    /**
     * rel property.
     *
     * @var string
     */
    protected $rel = '';

    /**
     * id selector.
     *
     * @var string
     */
    public $id = '';

    /**
     * @var Configuration
     */
    private $config = null;

    /**
     * Constructor.
     *
     * @param string            $url    URL
     * @param Configuration $config
     */
    public function __construct($url, Configuration $config)
    {
        $this->url = $url;
        $this->config = $config;
    }

    /**
     * Checks if webserver is an IIS Server.
     *
     * @return bool
     */
    public static function isIISServer()
    {
        return (isset($_SERVER['ALL_HTTP']) || isset($_SERVER['COMPUTERNAME']) || isset($_SERVER['APP_POOL_ID']));
    }

    /**
     * Checks if the the current URL is the main index.php file.
     *
     * @return bool
     */
    protected function isHomeIndex()
    {
        if (!$this->isSystemLink()) {
            return false;
        }

        return !(false === strpos($this->url, self::LINK_INDEX_HOME));
    }

    /**
     * Checks if URL is an internal reference.
     *
     * @return bool
     */
    protected function isInternalReference()
    {
        if ($this->isRelativeSystemLink()) {
            return true;
        }
        if (false === strpos($this->url, '#')) {
            return false;
        }

        return (strpos($this->url, '#') == 0);
    }

    /**
     * Checks if URL is a relative system link.
     *
     * @return bool
     */
    protected function isRelativeSystemLink()
    {
        $slashIdx = strpos($this->url, self::LINK_SLASH);
        if (false === $slashIdx) {
            return false;
        }

        return ($slashIdx == 0);
    }

    /**
     * Checks if URL is a system link.
     *
     * @return bool
     */
    protected function isSystemLink()
    {
        // a. Is the url relative, starting with '/'?
        // b. Is the url related to the current running PMF system?
        if ($this->isRelativeSystemLink()) {
            return true;
        }
        // $_SERVER['HTTP_HOST'] is the name of the website or virtual host name
        return !(false === strpos($this->url, $_SERVER['HTTP_HOST']));
    }

    /**
     * @param string $itemprop Item property
     */
    public function setItemProperty($itemprop)
    {
        $this->itemprop = $itemprop;
    }

    /**
     * @param string $rel rel property
     */
    public function setRelation($rel)
    {
        $this->rel = $rel;
    }

    /**
     * Checks if URL contains a scheme.
     *
     * @return bool
     */
    protected function hasScheme()
    {
        $parsed = parse_url($this->url);

        return (!empty($parsed['scheme']));
    }

    /**
     * Returns a search engine optimized title.
     *
     * @param string $title
     *
     * @return string
     */
    public function getSEOItemTitle($title = '')
    {
        if ('' === $title) {
            $title = $this->itemTitle;
        }

        $itemTitle = trim($title);
        // Lower the case (aesthetic)
        $itemTitle = Strings::strtolower($itemTitle);
        // Use '_' for some other characters for:
        // 1. avoiding regexp match break;
        // 2. improving the reading.
        $itemTitle = str_replace(array('-', "'", '/', '&#39'), '_', $itemTitle);
        // 1. Remove any CR LF sequence
        // 2. Use a '-' for the words separation
        $itemTitle = Strings::preg_replace('/\s/m', '-', $itemTitle);
        // Hack: remove some chars for having a better readable title
        $itemTitle = str_replace(
            array('+', ',', ';', ':', '.', '?', '!', '"', '(', ')', '[', ']', '{', '}', '<', '>', '%'),
            '',
            $itemTitle
        );
        // Hack: move some chars to "similar" but plain ASCII chars
        $itemTitle = str_replace(
            array(
                'à', 'è', 'é', 'ì', 'ò', 'ù', 'ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü',
                'č', 'ę', 'ė', 'į', 'š', 'ų', 'ū', 'ž',
                ),
            array(
                'a', 'e', 'e', 'i', 'o', 'u', 'ae', 'oe', 'ue', 'ss', 'Ae', 'Oe', 'Ue',
                'c', 'e', 'e', 'i', 's', 'u', 'u', 'z',
            ),
            $itemTitle
        );
        // Clean up
        $itemTitle = Strings::preg_replace('/-[\-]+/m', '-', $itemTitle);

        return $itemTitle;
    }

    /**
     * Returns the HTTP GET parameters.
     * @return array
     */
    protected function getHttpGetParameters(): array
    {
        $query = $this->getQuery();
        $parameters = [];

        if (!empty($query)) {
            // Check fragment
            if (isset($query['fragment'])) {
                $parameters[self::LINK_FRAGMENT_SEPARATOR] = urldecode($query['fragment']);
            }

            // Check if query string contains &amp;
            if (!strpos($query['main'], '&amp;')) {
                $query['main'] = str_replace('&', '&amp;', $query['main']);
            }

            $params = explode(self::LINK_AMPERSAND, $query['main']);

            foreach ($params as $param) {
                if (!empty($param)) {
                    $couple = explode(self::LINK_EQUAL, $param);
                    list($key, $val) = $couple;
                    $parameters[$key] = urldecode($val);
                }
            }
        }

        return $parameters;
    }

    /**
     * Returns the query of an URL.
     * @return array
     */
    protected function getQuery(): array
    {
        $query = [];

        if (!empty($this->url)) {
            $parsed = parse_url($this->url);

            if (isset($parsed['query'])) {
                $query['main'] = filter_var($parsed['query'], FILTER_SANITIZE_STRIPPED);
            }
            if (isset($parsed['fragment'])) {
                $query['fragment'] = filter_var($parsed['fragment'], FILTER_SANITIZE_STRIPPED);
            }
        }

        return $query;
    }

    /**
     * Returns the default scheme.
     * @return string
     */
    protected function getDefaultScheme(): string
    {
        $scheme = 'http://';
        if ($this->isSystemLink()) {
            $scheme = $this->getSystemScheme();
        }

        return $scheme;
    }

    /**
     * Returns the system scheme, http or https.
     * @return string
     */
    public function getSystemScheme(): string
    {
        if ($this->config->get('security.useSslOnly')) {
            return 'https://';
        }

        if (!self::isIISServer()) {
            // Apache, nginx, lighttpd
            if (isset($_SERVER['HTTPS']) && 'on' === strtolower($_SERVER['HTTPS'])) {
                return 'https://';
            } else {
                return 'http://';
            }
        } else {
            // IIS Server
            if ('on' === strtolower($_SERVER['HTTPS'])) {
                return 'https://';
            } else {
                return 'http://';
            }
        }
    }

    /**
     * Returns the relative URI.
     * @param string $path
     * @return string
     */
    public static function getSystemRelativeUri(string $path = null): string
    {
        if (isset($path)) {
            return str_replace($path, '', $_SERVER['SCRIPT_NAME']);
        }

        return str_replace('/src/Link.php', '', $_SERVER['SCRIPT_NAME']);
    }

    /**
     * Returns the system URI.
     *
     * $_SERVER['HTTP_HOST'] is the name of the website or virtual host name (HTTP/1.1)
     * Precisely, it contains what the user has written in the Host request-header, see below.
     * RFC 2616: The Host request-header field specifies the Internet host and port number of the resource
     *           being requested, as obtained from the original URI given by the user or referring resource
     *
     * @param string $path
     * @return string
     */
    public function getSystemUri($path = null): string
    {
        // Remove any ref to standard ports 80 and 443.
        $pattern[0] = '/:80$/'; // HTTP: port 80
        $pattern[1] = '/:443$/'; // HTTPS: port 443
        $sysUri = $this->getSystemScheme().preg_replace($pattern, '', $_SERVER['HTTP_HOST']);

        return $sysUri.self::getSystemRelativeUri($path);
    }

    /**
     * Builds a HTML anchor.
     * @return string
     */
    public function toHtmlAnchor():string
    {
        // Sanitize the provided url
        $url = $this->toString();
        // Prepare HTML anchor element
        $htmlAnchor = '<a';
        if (!empty($this->class)) {
            $htmlAnchor .= sprintf(' class="%s"', $this->class);
        }
        if (!empty($this->id)) {
            $htmlAnchor .= ' id="'.$this->id.'"';
        }
        if (!empty($this->tooltip)) {
            $htmlAnchor .= sprintf(' title="%s"', addslashes($this->tooltip));
        }
        if (!empty($this->name)) {
            $htmlAnchor .= sprintf(' name="%s"', $this->name);
        } else {
            if (!empty($this->url)) {
                $htmlAnchor .= sprintf(' href="%s"', $url);
            }
            if (!empty($this->target)) {
                $htmlAnchor .= sprintf(' target="%s"', $this->target);
            }
        }
        if (!empty($this->itemprop)) {
            $htmlAnchor .= sprintf(' itemprop="%s"', $this->itemprop);
        }
        if (!empty($this->rel)) {
            $htmlAnchor .= sprintf(' rel="%s"', $this->rel);
        }
        $htmlAnchor .= '>';
        if (('0' == $this->text) || (!empty($this->text))) {
            $htmlAnchor .= $this->text;
        } else {
            if (!empty($this->name)) {
                $htmlAnchor .= $this->name;
            } else {
                $htmlAnchor .= $url;
            }
        }
        $htmlAnchor .= '</a>';

        return $htmlAnchor;
    }

    /**
     * Appends the session id.
     * @param string $url  URL
     * @param int    $sids Session Id
     * @return string
     */
    protected function appendSids(string $url, int $sids): string
    {
        $separator = (false === strpos($url, self::LINK_SEARCHPART_SEPARATOR))
                     ?
                     self::LINK_SEARCHPART_SEPARATOR
                     :
                     self::LINK_AMPERSAND;

        return $url.$separator.self::LINK_GET_SIDS.self::LINK_EQUAL.$sids;
    }

    /**
     * Rewrites a URL string.
     * @param bool $forceNoModRewriteSupport Force no rewrite support
     * @return string
     */
    public function toString(bool $forceNoModRewriteSupport = false): string
    {
        $url = $this->toUri();
        // Check mod_rewrite support and 'rewrite' the passed (system) uri
        // according to the rewrite rules written in .htaccess
        if ((!$forceNoModRewriteSupport) && ($this->config->get('main.enableRewriteRules'))) {
            if ($this->isHomeIndex()) {
                $getParams = $this->getHttpGetParameters();
                if (isset($getParams[self::LINK_GET_ACTION])) {
                    // Get the part of the url 'till the '/' just before the pattern
                    $url = substr($url, 0, strpos($url, self::LINK_INDEX_HOME) + 1);
                    // Build the Url according to .htaccess rules
                    switch ($getParams[self::LINK_GET_ACTION]) {

                        case self::LINK_GET_ACTION_ADD:
                            $url .= self::LINK_HTML_ADDCONTENT;
                            break;

                        case self::LINK_GET_ACTION_FAQ:
                            $url .= self::LINK_CONTENT.
                                    $getParams[self::LINK_GET_CATEGORY].
                                    self::LINK_HTML_SLASH.
                                    $getParams[self::LINK_GET_ID].
                                    self::LINK_HTML_SLASH.
                                    $getParams[self::LINK_GET_ARTLANG].
                                    self::LINK_SLASH.
                                    $this->getSEOItemTitle().
                                    self::LINK_HTML_EXTENSION;
                            if (isset($getParams[self::LINK_GET_HIGHLIGHT])) {
                                $url .= self::LINK_SEARCHPART_SEPARATOR.
                                        self::LINK_GET_HIGHLIGHT.'='.
                                        $getParams[self::LINK_GET_HIGHLIGHT];
                            }
                            if (isset($getParams[self::LINK_FRAGMENT_SEPARATOR])) {
                                $url .= self::LINK_FRAGMENT_SEPARATOR.
                                        $getParams[self::LINK_FRAGMENT_SEPARATOR];
                            }
                            break;

                        case self::LINK_GET_ACTION_ASK:
                            $url .= self::LINK_HTML_ASK;
                            break;

                        case self::LINK_GET_ACTION_CONTACT:
                            $url .= self::LINK_HTML_CONTACT;
                            break;

                        case self::LINK_GET_ACTION_GLOSSARY:
                            $url .= self::LINK_HTML_GLOSSARY;
                            break;

                        case self::LINK_GET_ACTION_HELP:
                            $url .= self::LINK_HTML_HELP;
                            break;

                        case self::LINK_GET_ACTION_OPEN:
                            $url .= self::LINK_HTML_OPEN;
                            break;

                        case self::LINK_GET_ACTION_SEARCH:
                            if (!isset($getParams[self::LINK_GET_ACTION_SEARCH]) &&
                                isset($getParams[self::LINK_GET_TAGGING_ID])) {
                                $url .= self::LINK_TAGS.$getParams[self::LINK_GET_TAGGING_ID];
                                if (isset($getParams[self::LINK_GET_PAGE])) {
                                    $url .= self::LINK_HTML_SLASH.$getParams[self::LINK_GET_PAGE];
                                }
                                $url .= self::LINK_SLASH.
                                        $this->getSEOItemTitle().
                                        self::LINK_HTML_EXTENSION;
                            } elseif (isset($getParams[self::LINK_GET_ACTION_SEARCH])) {
                                $url .= self::LINK_HTML_SEARCH;
                                $url .= self::LINK_SEARCHPART_SEPARATOR.
                                        self::LINK_GET_ACTION_SEARCH.'='.
                                        $getParams[self::LINK_GET_ACTION_SEARCH];
                                if (isset($getParams[self::LINK_GET_PAGE])) {
                                    $url .= self::LINK_AMPERSAND.self::LINK_GET_PAGE.'='.
                                            $getParams[self::LINK_GET_PAGE];
                                }
                            }
                            if (isset($getParams[self::LINK_GET_LANGS])) {
                                $url .= self::LINK_AMPERSAND.
                                        self::LINK_GET_LANGS.'='.
                                        $getParams[self::LINK_GET_LANGS];
                            }
                            break;

                        case self::LINK_GET_ACTION_SITEMAP:
                            if (isset($getParams[self::LINK_GET_LETTER])) {
                                $url .= self::LINK_SITEMAP.
                                        $getParams[self::LINK_GET_LETTER].
                                        self::LINK_HTML_SLASH.
                                        $getParams[self::LINK_GET_LANG].
                                        self::LINK_HTML_EXTENSION;
                            } else {
                                $url .= self::LINK_SITEMAP.'A'.
                                        self::LINK_HTML_SLASH.
                                        $getParams[self::LINK_GET_LANG].
                                        self::LINK_HTML_EXTENSION;
                            }
                            break;

                        case self::LINK_GET_ACTION_SHOW:
                            if (!isset($getParams[self::LINK_GET_CATEGORY]) ||
                                (isset($getParams[self::LINK_GET_CATEGORY]) &&
                                (0 == $getParams[self::LINK_GET_CATEGORY]))) {
                                $url .= self::LINK_HTML_SHOWCAT;
                            } else {
                                $url .= self::LINK_CATEGORY.
                                        $getParams[self::LINK_GET_CATEGORY];
                                if (isset($getParams[self::LINK_GET_PAGE])) {
                                    $url .= self::LINK_HTML_SLASH.
                                            $getParams[self::LINK_GET_PAGE];
                                }
                                $url .= self::LINK_HTML_SLASH.
                                        $this->getSEOItemTitle().
                                        self::LINK_HTML_EXTENSION;
                            }
                            break;

                        case self::LINK_GET_ACTION_NEWS:
                            $url .= self::LINK_NEWS.
                                    $getParams[self::LINK_GET_NEWS_ID].
                                    self::LINK_HTML_SLASH.
                                    $getParams[self::LINK_GET_NEWS_LANG].
                                    self::LINK_SLASH.
                                    $this->getSEOItemTitle().
                                    self::LINK_HTML_EXTENSION;
                            break;
                    }

                    if (isset($getParams[self::LINK_GET_SIDS])) {
                        $url = $this->appendSids($url, $getParams[self::LINK_GET_SIDS]);
                    }

                    if (isset($getParams['fragment'])) {
                        $url .= self::LINK_FRAGMENT_SEPARATOR.$getParams['fragment'];
                    }
                }
            }
        }

        return $url;
    }

    /**
     * Transforms a URI.
     * @return string
     */
    public function toUri(): string 
    {
        $url = $this->url;
        if (!empty($this->url)) {
            if ((!$this->hasScheme()) && (!$this->isInternalReference())) {
                $url = $this->getDefaultScheme().$this->url;
            }
        }

        return $url;
    }

    /**
     * Returns the current URL.
     * @return string
     */
    public function getCurrentUrl(): string
    {
        return $this->config->getDefaultUrl().Strings::htmlentities(substr($_SERVER['REQUEST_URI'], 1));
    }

    /**
     * Static method to generate simple HTML anchors.
     * @static
     * @param string $url    URL
     * @param string $text   Text
     * @param bool   $active Add CSS class named "active"?
     * @return string
     */
    public static function renderNavigationLink(string $url, string $text, bool $active = false): string
    {
        return printf(
            '<a %s href="%s">%s</a>',
            (true === $active ? 'class="active"' : ''),
            $url,
            $text
        );
    }
}
