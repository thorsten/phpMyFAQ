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
 * @copyright 2005-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */

namespace phpMyFAQ;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class Link
 *
 * @package phpMyFAQ
 */
class Link
{
    private const string LINK_AMPERSAND = '&amp;';

    private const string LINK_CATEGORY = 'category/';

    private const string LINK_CONTENT = 'content/';

    private const string LINK_EQUAL = '=';

    private const string LINK_FRAGMENT_SEPARATOR = '#';

    private const string LINK_HTML_SLASH = '/';

    private const string LINK_NEWS = 'news/';

    private const string LINK_SITEMAP = 'sitemap/';

    private const string LINK_SLASH = '/';

    private const string LINK_SEARCHPART_SEPARATOR = '?';

    private const string LINK_TAGS = 'tags/';

    private const string LINK_INDEX_HOME = '/index.php';

    private const string LINK_GET_ACTION = 'action';

    private const string LINK_GET_ARTLANG = 'artlang';

    private const string LINK_GET_CATEGORY = 'cat';

    private const string LINK_GET_HIGHLIGHT = 'highlight';

    private const string LINK_GET_ID = 'id';

    private const string LINK_GET_LANG = 'lang';

    private const string LINK_GET_LETTER = 'letter';

    private const string LINK_GET_NEWS_ID = 'newsid';

    private const string LINK_GET_NEWS_LANG = 'newslang';

    private const string LINK_GET_PAGE = 'seite';

    private const string LINK_GET_SIDS = 'sid';

    private const string LINK_GET_TAGGING_ID = 'tagging_id';

    private const string LINK_GET_LANGS = 'langs';

    private const string LINK_GET_ACTION_ADD = 'add';

    private const string LINK_GET_ACTION_FAQ = 'faq';

    private const string LINK_GET_ACTION_ASK = 'ask';

    private const string LINK_GET_ACTION_CONTACT = 'contact';

    private const string LINK_GET_ACTION_GLOSSARY = 'glossary';

    private const string LINK_GET_ACTION_HELP = 'help';

    private const string LINK_GET_ACTION_LOGIN = 'login';

    private const string LINK_GET_ACTION_NEWS = 'news';

    private const string LINK_GET_ACTION_OPEN = 'open-questions';

    private const string LINK_GET_ACTION_SEARCH = 'search';

    private const string LINK_GET_ACTION_SITEMAP = 'sitemap';

    private const string LINK_GET_ACTION_SHOW = 'show';

    private const string LINK_GET_ACTION_BOOKMARKS = 'bookmarks';

    private const string LINK_GET_ACTION_REGISTER = 'register';

    private const string LINK_HTML_EXTENSION = '.html';

    private const string LINK_HTML_ADDCONTENT = 'add-faq.html';

    private const string LINK_HTML_ASK = 'add-question.html';

    private const string LINK_HTML_CONTACT = 'contact.html';

    private const string LINK_HTML_GLOSSARY = 'glossary.html';

    private const string LINK_HTML_HELP = 'help.html';

    private const string LINK_HTML_LOGIN = 'login';

    private const string LINK_HTML_OPEN = 'open-questions.html';

    private const string LINK_HTML_SEARCH = 'search.html';

    private const string LINK_HTML_SHOW_CATEGORIES = 'show-categories.html';

    private const string LINK_HTML_BOOKMARKS = 'user/bookmarks';

    private const string LINK_HTML_REGISTER = 'user/register';

    /**
     * @var int[] List of allowed action parameters
     */
    public static array $allowedActionParameters = [
        'add' => 1,
        'faq' => 1,
        'ask' => 1,
        'attachment' => 1,
        'contact' => 1,
        'glossary' => 1,
        'help' => 1,
        'login' => 1,
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
        'sendmail' => 1,
        'show' => 1,
        'sitemap' => 1,
        'thankyou' => 1,
        'twofactor' => 1,
        'ucp' => 1,
        '404' => 1,
        'bookmarks' => 1
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
    public function __construct(public string $url, private readonly Configuration $configuration)
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
     * HTTP_HOST is the name of the website or virtual host name (HTTP/1.1)
     * Precisely, it contains what the user has written in the Host request-header, see below.
     * RFC 2616: The Host request-header field specifies the Internet host and port number of the resource
     *           being requested, as obtained from the original URI given by the user or referring resource
     */
    public function getSystemUri(string|null $path = null): string
    {
        $request = Request::createFromGlobals();
        $host = $request->getHost();
        $host = preg_replace(['/:80$/', '/:443$/'], '', $host);

        $sysUri = $this->getSystemScheme() . $host;

        return $sysUri . self::getSystemRelativeUri($path);
    }

    /**
     * Returns the system scheme, http or https.
     */
    public function getSystemScheme(): string
    {
        $request = Request::createFromGlobals();

        if ($this->configuration->get('security.useSslOnly')) {
            return 'https://';
        }

        return $request->isSecure() ? 'https://' : 'http://';
    }

    /**
     * Returns the relative URI.
     */
    public static function getSystemRelativeUri(string|null $path = null): string
    {
        $request = Request::createFromGlobals();
        $scriptName = $request->getScriptName();

        if (isset($path)) {
            return str_replace($path, '', $scriptName);
        }

        return str_replace('/src/Link.php', '', $scriptName);
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

        if ($this->class !== '' && $this->class !== '0') {
            $htmlAnchor .= sprintf(' class="%s"', $this->class);
        }

        if ($this->id !== '' && $this->id !== '0') {
            $htmlAnchor .= ' id="' . $this->id . '"';
        }

        if ($this->tooltip !== null && $this->tooltip !== '' && $this->tooltip !== '0') {
            $htmlAnchor .= sprintf(' title="%s"', Strings::htmlentities($this->tooltip));
        }

        if ($this->name !== '' && $this->name !== '0') {
            $htmlAnchor .= sprintf(' name="%s"', Strings::htmlentities($this->name));
        } else {
            if ($this->url !== '' && $this->url !== '0') {
                $htmlAnchor .= sprintf(' href="%s"', $url);
            }

            if ($this->target !== '' && $this->target !== '0') {
                $htmlAnchor .= sprintf(' target="%s"', $this->target);
            }
        }

        if ($this->rel !== '' && $this->rel !== '0') {
            $htmlAnchor .= sprintf(' rel="%s"', $this->rel);
        }

        $htmlAnchor .= '>';
        if ($this->text !== '') {
            $htmlAnchor .= $this->text;
        } elseif ($this->name !== '' && $this->name !== '0') {
            $htmlAnchor .= $this->name;
        } else {
            $htmlAnchor .= $url;
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

                    case self::LINK_GET_ACTION_BOOKMARKS:
                        $url .= self::LINK_HTML_BOOKMARKS;
                        break;

                    case self::LINK_GET_ACTION_REGISTER:
                        $url .= self::LINK_HTML_REGISTER;
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
                            $url .= self::LINK_HTML_SHOW_CATEGORIES;
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
                    $url = $this->appendSids($url, (int)$getParams[self::LINK_GET_SIDS]);
                }

                if (isset($getParams['fragment'])) {
                    $url .= self::LINK_FRAGMENT_SEPARATOR . $getParams['fragment'];
                }

                if ($removeSessionFromUrl) {
                    $url = strtok($url, '?');
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
        if ($this->url !== '' && $this->url !== '0' && ((!$this->hasScheme()) && (!$this->isInternalReference()))) {
            return $this->getDefaultScheme() . $this->url;
        }

        return $this->url;
    }

    /**
     * Checks if URL contains a scheme.
     */
    private function hasScheme(): bool
    {
        $parsed = parse_url($this->url);

        return (isset($parsed['scheme']) && ($parsed['scheme'] !== '' && $parsed['scheme'] !== '0'));
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
        if ($this->isSystemLink()) {
            return $this->getSystemScheme();
        }

        return 'https://';
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

        // HTTP_HOST is the name of the website or virtual host name
        return str_contains($this->url, Request::createFromGlobals()->getHost());
    }

    /**
     * Checks if the current URL is the main index.php file.
     */
    protected function isHomeIndex(): bool
    {
        if (!$this->isSystemLink()) {
            return false;
        }

        return str_contains($this->url, self::LINK_INDEX_HOME);
    }

    /**
     * Returns the HTTP GET parameters.
     * @return array<string, string>
     */
    protected function getHttpGetParameters(): array
    {
        $query = $this->getQuery();
        $parameters = [];

        if ($query !== []) {
            // Check fragment
            if (isset($query['fragment'])) {
                $parameters[self::LINK_FRAGMENT_SEPARATOR] = urldecode($query['fragment']);
            }

            // Check if query string contains &amp;
            $query['main'] = str_replace(['&amp;', '#38;', 'amp;'], '&', $query['main']);

            $params = explode('&', $query['main']);
            foreach ($params as $param) {
                if ($param !== '' && $param !== '0') {
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

        if ($this->url !== '' && $this->url !== '0') {
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
        // 1. Avoiding regexp match break;
        // 2. Improving the reading.
        $itemTitle = str_replace(['-', "'", '/', '&#39'], '_', $itemTitle);
        // 1. Remove any CR LF sequence
        // 2. Use a '-' for the word separation
        $itemTitle = Strings::preg_replace('/\s/m', '-', $itemTitle);
        // Hack: remove some chars for having a better readable title
        $itemTitle = str_replace(
            ['+', ',', ';', ':', '.', '?', '!', '"', '(', ')', '[', ']', '{', '}', '<', '>', '%'],
            '',
            (string) $itemTitle
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
        $separator = (str_contains($url, self::LINK_SEARCHPART_SEPARATOR))
            ?
            self::LINK_AMPERSAND
            :
            self::LINK_SEARCHPART_SEPARATOR;

        return $url . $separator . self::LINK_GET_SIDS . self::LINK_EQUAL . $sids;
    }
}
