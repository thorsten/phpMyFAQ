<?php

/**
 * Link management
 *
 * This class wraps the needs for managing an HTML anchor taking into account the HTML anchor creation with specific
 * handling for mod_rewrite phpMyFAQs native support
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */

namespace phpMyFAQ;

/**
 * Class Link
 *
 * @package phpMyFAQ
 */
class Link
{
    private const LINK_AMPERSAND = '&amp;';
    private const LINK_CATEGORY = 'category/';
    private const LINK_CONTENT = 'content/';
    private const LINK_EQUAL = '=';
    private const LINK_FRAGMENT_SEPARATOR = '#';
    private const LINK_HTML_SLASH = '/';
    private const LINK_NEWS = 'news/';
    private const LINK_SITEMAP = 'sitemap/';
    private const LINK_SLASH = '/';
    private const LINK_SEARCHPART_SEPARATOR = '?';
    private const LINK_TAGS = 'tags/';
    private const LINK_INDEX_HOME = '/index.php';

    private const LINK_GET_ACTION = 'action';
    private const LINK_GET_ARTLANG = 'artlang';
    private const LINK_GET_CATEGORY = 'cat';
    private const LINK_GET_HIGHLIGHT = 'highlight';
    private const LINK_GET_ID = 'id';
    private const LINK_GET_LANG = 'lang';
    private const LINK_GET_LETTER = 'letter';
    private const LINK_GET_NEWS_ID = 'newsid';
    private const LINK_GET_NEWS_LANG = 'newslang';
    private const LINK_GET_PAGE = 'seite';
    private const LINK_GET_SIDS = 'sid';
    private const LINK_GET_TAGGING_ID = 'tagging_id';
    private const LINK_GET_LANGS = 'langs';

    private const LINK_GET_ACTION_ADD = 'add';
    private const LINK_GET_ACTION_FAQ = 'faq';
    private const LINK_GET_ACTION_ASK = 'ask';
    private const LINK_GET_ACTION_CONTACT = 'contact';
    private const LINK_GET_ACTION_GLOSSARY = 'glossary';
    private const LINK_GET_ACTION_HELP = 'help';
    private const LINK_GET_ACTION_LOGIN = 'login';
    private const LINK_GET_ACTION_NEWS = 'news';
    private const LINK_GET_ACTION_OPEN = 'open-questions';
    private const LINK_GET_ACTION_SEARCH = 'search';
    private const LINK_GET_ACTION_SITEMAP = 'sitemap';
    private const LINK_GET_ACTION_SHOW = 'show';
    private const LINK_HTML_EXTENSION = '.html';

    private const LINK_HTML_ADDCONTENT = 'addcontent.html';
    private const LINK_HTML_ASK = 'ask.html';
    private const LINK_HTML_CONTACT = 'contact.html';
    private const LINK_HTML_GLOSSARY = 'glossary.html';
    private const LINK_HTML_HELP = 'help.html';
    private const LINK_HTML_LOGIN = 'login.html';
    private const LINK_HTML_OPEN = 'open-questions.html';
    private const LINK_HTML_SEARCH = 'search.html';
    private const LINK_HTML_SHOWCAT = 'show-categories.html';

    /**
     * @var int[] List of allowed action parameters
     */
    public static array $allowedActionParameters = [
        'add' => 1,
        'faq' => 1,
        'artikel' => 1, // @deprecated
        'ask' => 1,
        'attachment' => 1,
        'contact' => 1,
        'glossary' => 1,
        'help' => 1,
        'login' => 1,
        'mailsend2friend' => 1,
        'news' => 1,
        'open-questions' => 1,
        'overview' => 1,
        'password' => 1,
        'privacy' => 1,
        'register' => 1,
        'request-removal' => 1,
        'save' => 1,
        'savecomment' => 1,
        'savequestion' => 1,
        'savevoting' => 1,
        'search' => 1,
        'send2friend' => 1,
        'sendmail' => 1,
        'show' => 1,
        'sitemap' => 1,
        'thankyou' => 1,
        'twofactor' => 1,
        'ucp' => 1,
        'writecomment' => 1,
        '404' => 1
    ];

    /**
     * CSS class.
     */
    public string $class = '';

    /**
     * Linktext.
     */
    public string $text = '';

    /**
     * Tooltip.
     *
     * @var string|null
     */
    public ?string $tooltip = '';

    /**
     * Target.
     */
    public string $target = '';

    /**
     * Name selector.
     */
    public string $name = '';

    /**
     * property specific to the SEO/SEF URLs.
     */
    public string $itemTitle = '';

    /**
     * id selector.
     */
    public string $id = '';

    /**
     * rel property.
     */
    protected string $rel = '';

    /**
     * Constructor.
     *
     * @param string $url URL
     */
    public function __construct(public string $url, private readonly Configuration $config)
    {
    }

    /**
     * @param string $rel rel property
     */
    public function setRelation(string $rel): void
    {
        $this->rel = $rel;
    }

    /**
     * Returns the system URI.
     * $_SERVER['HTTP_HOST'] is the name of the website or virtual host name (HTTP/1.1)
     * Precisely, it contains what the user has written in the Host request-header, see below.
     * RFC 2616: The Host request-header field specifies the Internet host and port number of the resource
     *           being requested, as obtained from the original URI given by the user or referring resource
     *
     * @param string|null $path
     */
    public function getSystemUri(string $path = null): string
    {
        $pattern = [];
        // Remove any ref to standard ports 80 and 443.
        $pattern[0] = '/:80$/'; // HTTP: port 80
        $pattern[1] = '/:443$/'; // HTTPS: port 443
        $sysUri = $this->getSystemScheme() . preg_replace($pattern, '', (string) $_SERVER['HTTP_HOST']);

        return $sysUri . self::getSystemRelativeUri($path);
    }

    /**
     * Returns the system scheme, http or https.
     */
    public function getSystemScheme(): string
    {
        if ($this->config->get('security.useSslOnly')) {
            return 'https://';
        }

        if (!self::isIISServer()) {
            // Apache, nginx, lighttpd
            if (isset($_SERVER['HTTPS']) && 'on' === strtolower((string) $_SERVER['HTTPS'])) {
                return 'https://';
            } else {
                return 'http://';
            }
        } else {
            // IIS Server
            if ('on' === strtolower((string) $_SERVER['HTTPS'])) {
                return 'https://';
            } else {
                return 'http://';
            }
        }
    }

    /**
     * Checks if webserver is an IIS Server.
     */
    public static function isIISServer(): bool
    {
        return (isset($_SERVER['ALL_HTTP']) || isset($_SERVER['COMPUTERNAME']) || isset($_SERVER['APP_POOL_ID']));
    }

    /**
     * Returns the relative URI.
     *
     * @param string|null $path
     */
    public static function getSystemRelativeUri(string $path = null): string
    {
        if (isset($path)) {
            return str_replace($path, '', (string) $_SERVER['SCRIPT_NAME']);
        }

        return str_replace('/src/Link.php', '', (string) $_SERVER['SCRIPT_NAME']);
    }

    /**
     * Builds an HTML anchor.
     */
    public function toHtmlAnchor(): string
    {
        // Sanitize the provided url
        $url = $this->toString();
        // Prepare HTML anchor element
        $htmlAnchor = '<a';

        if (!empty($this->class)) {
            $htmlAnchor .= sprintf(' class="%s"', $this->class);
        }

        if (!empty($this->id)) {
            $htmlAnchor .= ' id="' . $this->id . '"';
        }

        if (!empty($this->tooltip)) {
            $htmlAnchor .= sprintf(' title="%s"', Strings::htmlentities($this->tooltip));
        }

        if (!empty($this->name)) {
            $htmlAnchor .= sprintf(' name="%s"', Strings::htmlentities($this->name));
        } else {
            if (!empty($this->url)) {
                $htmlAnchor .= sprintf(' href="%s"', $url);
            }
            if (!empty($this->target)) {
                $htmlAnchor .= sprintf(' target="%s"', $this->target);
            }
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

        return $htmlAnchor . '</a>';
    }

    /**
     * Rewrites a URL string. Checks mod_rewrite support and 'rewrite'
     * the passed (system) uri according to the rewrite rules written
     * in .htaccess
     *
     * @param bool $removeSessionFromUrl Remove session from URL
     */
    public function toString(bool $removeSessionFromUrl = false): string
    {
        $url = $this->toUri();

        if ($this->config->get('main.enableRewriteRules')) {
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
                            $url .= self::LINK_CONTENT .
                                $getParams[self::LINK_GET_CATEGORY] .
                                self::LINK_HTML_SLASH .
                                $getParams[self::LINK_GET_ID] .
                                self::LINK_HTML_SLASH .
                                $getParams[self::LINK_GET_ARTLANG] .
                                self::LINK_SLASH .
                                $this->getSEOItemTitle() .
                                self::LINK_HTML_EXTENSION;
                            if (isset($getParams[self::LINK_GET_HIGHLIGHT])) {
                                $url .= self::LINK_SEARCHPART_SEPARATOR .
                                    self::LINK_GET_HIGHLIGHT . '=' .
                                    $getParams[self::LINK_GET_HIGHLIGHT];
                            }
                            if (isset($getParams[self::LINK_FRAGMENT_SEPARATOR])) {
                                $url .= self::LINK_FRAGMENT_SEPARATOR .
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

                        case self::LINK_GET_ACTION_LOGIN:
                            $url .= self::LINK_HTML_LOGIN;
                            break;

                        case self::LINK_GET_ACTION_SEARCH:
                            if (
                                !isset($getParams[self::LINK_GET_ACTION_SEARCH])
                                && isset($getParams[self::LINK_GET_TAGGING_ID])
                            ) {
                                $url .= self::LINK_TAGS . $getParams[self::LINK_GET_TAGGING_ID];
                                if (isset($getParams[self::LINK_GET_PAGE])) {
                                    $url .= self::LINK_HTML_SLASH . $getParams[self::LINK_GET_PAGE];
                                }
                                $url .= self::LINK_SLASH .
                                    $this->getSEOItemTitle() .
                                    self::LINK_HTML_EXTENSION;
                            } elseif (isset($getParams[self::LINK_GET_ACTION_SEARCH])) {
                                $url .= self::LINK_HTML_SEARCH;
                                $url .= self::LINK_SEARCHPART_SEPARATOR .
                                    self::LINK_GET_ACTION_SEARCH . '=' .
                                    $getParams[self::LINK_GET_ACTION_SEARCH];
                                if (isset($getParams[self::LINK_GET_PAGE])) {
                                    $url .= self::LINK_AMPERSAND . self::LINK_GET_PAGE . '=' .
                                        $getParams[self::LINK_GET_PAGE];
                                }
                            }
                            if (isset($getParams[self::LINK_GET_LANGS])) {
                                $url .= self::LINK_AMPERSAND .
                                    self::LINK_GET_LANGS . '=' .
                                    $getParams[self::LINK_GET_LANGS];
                            }
                            break;

                        case self::LINK_GET_ACTION_SITEMAP:
                            if (isset($getParams[self::LINK_GET_LETTER])) {
                                $url .= self::LINK_SITEMAP .
                                    $getParams[self::LINK_GET_LETTER] .
                                    self::LINK_HTML_SLASH .
                                    $getParams[self::LINK_GET_LANG] .
                                    self::LINK_HTML_EXTENSION;
                            } else {
                                $url .= self::LINK_SITEMAP . 'A' .
                                    self::LINK_HTML_SLASH .
                                    $getParams[self::LINK_GET_LANG] .
                                    self::LINK_HTML_EXTENSION;
                            }
                            break;

                        case self::LINK_GET_ACTION_SHOW:
                            if (
                                !isset($getParams[self::LINK_GET_CATEGORY])
                                || (isset($getParams[self::LINK_GET_CATEGORY])
                                    && (0 == $getParams[self::LINK_GET_CATEGORY]))
                            ) {
                                $url .= self::LINK_HTML_SHOWCAT;
                            } else {
                                $url .= self::LINK_CATEGORY .
                                    $getParams[self::LINK_GET_CATEGORY];
                                if (isset($getParams[self::LINK_GET_PAGE])) {
                                    $url .= self::LINK_HTML_SLASH .
                                        $getParams[self::LINK_GET_PAGE];
                                }
                                $url .= self::LINK_HTML_SLASH .
                                    $this->getSEOItemTitle() .
                                    self::LINK_HTML_EXTENSION;
                            }
                            break;

                        case self::LINK_GET_ACTION_NEWS:
                            $url .= self::LINK_NEWS .
                                $getParams[self::LINK_GET_NEWS_ID] .
                                self::LINK_HTML_SLASH .
                                $getParams[self::LINK_GET_NEWS_LANG] .
                                self::LINK_SLASH .
                                $this->getSEOItemTitle() .
                                self::LINK_HTML_EXTENSION;
                            break;
                    }

                    if (isset($getParams[self::LINK_GET_SIDS])) {
                        $url = $this->appendSids($url, $getParams[self::LINK_GET_SIDS]);
                    }

                    if (isset($getParams['fragment'])) {
                        $url .= self::LINK_FRAGMENT_SEPARATOR . $getParams['fragment'];
                    }

                    if ($removeSessionFromUrl) {
                        $url = strtok($url, '?');
                    }
                }
            }
        } else {
            if ($removeSessionFromUrl) {
                $getParams = $this->getHttpGetParameters();
                if (isset($getParams[self::LINK_GET_ACTION])) {
                    $url = substr($url, 0, strpos($url, self::LINK_INDEX_HOME) + 1) . 'index.php?';
                    foreach ($getParams as $key => $value) {
                        if ($key !== self::LINK_GET_SIDS) {
                            $url .= sprintf('%s=%s&', $key, $value);
                        }
                    }
                    $url = substr($url, 0, -1); // Remove last &
                }
            }
        }

        return $url;
    }

    /**
     * Transforms a URI.
     */
    public function toUri(): string
    {
        $url = $this->url;
        if (!empty($this->url)) {
            if ((!$this->hasScheme()) && (!$this->isInternalReference())) {
                $url = $this->getDefaultScheme() . $this->url;
            }
        }

        return $url;
    }

    /**
     * Checks if URL contains a scheme.
     */
    private function hasScheme(): bool
    {
        $parsed = parse_url($this->url);

        return (!empty($parsed['scheme']));
    }

    /**
     * Checks if URL is an internal reference.
     */
    protected function isInternalReference(): bool
    {
        if ($this->isRelativeSystemLink()) {
            return true;
        }
        if (!str_contains($this->url, '#')) {
            return false;
        }

        return (str_starts_with($this->url, '#'));
    }

    /**
     * Checks if URL is a relative system link.
     */
    private function isRelativeSystemLink(): bool
    {
        $slashIdx = strpos($this->url, self::LINK_SLASH);
        if (false === $slashIdx) {
            return false;
        }

        return ($slashIdx == 0);
    }

    /**
     * Returns the default scheme.
     */
    protected function getDefaultScheme(): string
    {
        $scheme = 'https://';
        if ($this->isSystemLink()) {
            $scheme = $this->getSystemScheme();
        }

        return $scheme;
    }

    /**
     * Checks if URL is a system link.
     */
    protected function isSystemLink(): bool
    {
        // 1. Is the url relative, starting with '/'?
        // 2. Is the url related to the current running phpMyFAQ installation?
        if ($this->isRelativeSystemLink()) {
            return true;
        }
        // $_SERVER['HTTP_HOST'] is the name of the website or virtual host name
        return !(!str_contains($this->url, (string) $_SERVER['HTTP_HOST']));
    }

    /**
     * Checks if the current URL is the main index.php file.
     */
    protected function isHomeIndex(): bool
    {
        if (!$this->isSystemLink()) {
            return false;
        }

        return !(!str_contains($this->url, self::LINK_INDEX_HOME));
    }

    /**
     * Returns the HTTP GET parameters.
     * @return array<string, string>
     */
    protected function getHttpGetParameters(): array
    {
        $query = $this->getQuery();
        $parameters = [];

        if (!empty($query)) {
            // Check fragment
            if (isset($query['fragment'])) {
                $parameters[self::LINK_FRAGMENT_SEPARATOR] = urldecode((string) $query['fragment']);
            }

            // Check if query string contains &amp;
            $query['main'] = str_replace(['&amp;', '#38;', 'amp;'], '&', (string) $query['main']);

            $params = explode('&', $query['main']);
            foreach ($params as $param) {
                if (!empty($param)) {
                    [$key, $val] = explode(self::LINK_EQUAL, $param);
                    $parameters[$key] = urldecode($val);
                }
            }
        }

        return $parameters;
    }

    /**
     * Returns the query of a URL.
     * @return array<string, string>
     */
    protected function getQuery(): array
    {
        $query = [];

        if (!empty($this->url)) {
            $parsed = parse_url($this->url);

            if (isset($parsed['query'])) {
                $query['main'] = filter_var($parsed['query'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
            if (isset($parsed['fragment'])) {
                $query['fragment'] = filter_var($parsed['fragment'], FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        return $query;
    }

    /**
     * Returns a search engine optimized title.
     */
    public function getSEOItemTitle(string $title = ''): string
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
        $itemTitle = str_replace(['-', "'", '/', '&#39'], '_', $itemTitle);
        // 1. Remove any CR LF sequence
        // 2. Use a '-' for the word separation
        $itemTitle = Strings::preg_replace('/\s/m', '-', $itemTitle);
        // Hack: remove some chars for having a better readable title
        $itemTitle = str_replace(
            ['+', ',', ';', ':', '.', '?', '!', '"', '(', ')', '[', ']', '{', '}', '<', '>', '%'],
            '',
            $itemTitle
        );
        // Hack: move some chars to "similar" but plain ASCII chars
        $itemTitle = str_replace(
            [
                'à',
                'è',
                'é',
                'ì',
                'ò',
                'ù',
                'ä',
                'ö',
                'ü',
                'ß',
                'Ä',
                'Ö',
                'Ü',
                'č',
                'ę',
                'ė',
                'į',
                'š',
                'ų',
                'ū',
                'ž',
            ],
            [
                'a',
                'e',
                'e',
                'i',
                'o',
                'u',
                'ae',
                'oe',
                'ue',
                'ss',
                'Ae',
                'Oe',
                'Ue',
                'c',
                'e',
                'e',
                'i',
                's',
                'u',
                'u',
                'z',
            ],
            $itemTitle
        );

        // Clean up
        return Strings::preg_replace('/-[\-]+/m', '-', $itemTitle);
    }

    /**
     * Appends the session id.
     *
     * @param string $url URL
     * @param int $sids Session ID
     */
    private function appendSids(string $url, int $sids): string
    {
        $separator = (!str_contains($url, self::LINK_SEARCHPART_SEPARATOR))
            ?
            self::LINK_SEARCHPART_SEPARATOR
            :
            self::LINK_AMPERSAND;

        return $url . $separator . self::LINK_GET_SIDS . self::LINK_EQUAL . $sids;
    }

    /**
     * Returns the current URL.
     */
    public function getCurrentUrl(): string
    {
        $defaultUrl = $this->config->getDefaultUrl();
        $url = Filter::filterVar($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        $parsedUrl = parse_url((string) $url);

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $parameters);

            if (isset($parameters['action']) && !isset(self::$allowedActionParameters[$parameters['action']])) {
                return $defaultUrl;
            }

            return $defaultUrl . Strings::htmlspecialchars(substr((string) $url, 1));
        }

        return $defaultUrl;
    }
}
