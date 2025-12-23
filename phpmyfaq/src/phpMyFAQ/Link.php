<?php

/**
 * Link management
 *
 * This class wraps the needs for managing an HTML anchor, taking into account the HTML anchor creation with specific
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

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Link\Strategy\FaqStrategy;
use phpMyFAQ\Link\Strategy\GenericPathStrategy;
use phpMyFAQ\Link\Strategy\NewsStrategy;
use phpMyFAQ\Link\Strategy\SearchStrategy;
use phpMyFAQ\Link\Strategy\ShowStrategy;
use phpMyFAQ\Link\Strategy\SitemapStrategy;
use phpMyFAQ\Link\Strategy\StrategyInterface;
use phpMyFAQ\Link\Strategy\StrategyRegistry;
use phpMyFAQ\Link\Util\LinkQueryParser;
use phpMyFAQ\Link\Util\TitleSlugifier;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Link
 *
 * @package phpMyFAQ
 */
class Link
{
    public const string LINK_AMPERSAND = '&amp;';

    public const string LINK_CATEGORY = 'category/';

    public const string LINK_CONTENT = 'content/';

    public const string LINK_EQUAL = '=';

    public const string LINK_FRAGMENT_SEPARATOR = '#';

    public const string LINK_HTML_SLASH = '/';

    public const string LINK_NEWS = 'news/';

    public const string LINK_SITEMAP = 'sitemap/';

    public const string LINK_SLASH = '/';

    public const string LINK_SEARCHPART_SEPARATOR = '?';

    public const string LINK_TAGS = 'tags/';

    public const string LINK_INDEX_HOME = '/index.php';

    public const string LINK_GET_ACTION = 'action';

    public const string LINK_GET_ARTLANG = 'artlang';

    public const string LINK_GET_CATEGORY = 'cat';

    public const string LINK_GET_HIGHLIGHT = 'highlight';

    public const string LINK_GET_ID = 'id';

    public const string LINK_GET_LANG = 'lang';

    public const string LINK_GET_LETTER = 'letter';

    public const string LINK_GET_NEWS_ID = 'newsid';

    public const string LINK_GET_NEWS_LANG = 'newslang';

    public const string LINK_GET_PAGE = 'seite';

    public const string LINK_GET_SIDS = 'sid';

    public const string LINK_GET_TAGGING_ID = 'tagging_id';

    public const string LINK_GET_LANGS = 'langs';

    public const string LINK_GET_ACTION_ADD = 'add';

    public const string LINK_GET_ACTION_FAQ = 'faq';

    public const string LINK_GET_ACTION_ASK = 'ask';

    public const string LINK_GET_ACTION_CONTACT = 'contact';

    public const string LINK_GET_ACTION_GLOSSARY = 'glossary';

    public const string LINK_GET_ACTION_HELP = 'help';

    public const string LINK_GET_ACTION_LOGIN = 'login';

    public const string LINK_GET_ACTION_NEWS = 'news';

    public const string LINK_GET_ACTION_OPEN = 'open-questions';

    public const string LINK_GET_ACTION_SEARCH = 'search';

    public const string LINK_GET_ACTION_SITEMAP = 'sitemap';

    public const string LINK_GET_ACTION_SHOW = 'show';

    public const string LINK_GET_ACTION_BOOKMARKS = 'bookmarks';

    /* @mago-expect lint:no-literal-password - false positive */
    public const string LINK_GET_ACTION_PASSWORD = 'password';

    public const string LINK_GET_ACTION_REGISTER = 'register';

    public const string LINK_HTML_EXTENSION = '.html';

    public const string LINK_HTML_ADDCONTENT = 'add-faq.html';

    public const string LINK_HTML_ASK = 'add-question.html';

    public const string LINK_HTML_CONTACT = 'contact.html';

    public const string LINK_HTML_GLOSSARY = 'glossary.html';

    public const string LINK_HTML_HELP = 'help.html';

    public const string LINK_HTML_LOGIN = 'login';

    public const string LINK_HTML_OPEN = 'open-questions.html';

    public const string LINK_HTML_SEARCH = 'search.html';

    public const string LINK_HTML_SHOW_CATEGORIES = 'show-categories.html';

    public const string LINK_HTML_BOOKMARKS = 'user/bookmarks';

    public const string LINK_HTML_REGISTER = 'user/register';

    /* @mago-expect lint:no-literal-password - false positive */
    public const string LINK_HTML_FORGOT_PASSWORD = 'forgot-password';

    public const string ATTR_FMT = ' %s="%s"';

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
        /* @mago-expect lint:no-literal-password - false positive */
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
        'bookmarks' => 1,
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
    private string $itemTitle = '';

    /**
     * href
     */
    private string $reference = '';

    /**
     * id selector.
     */
    public string $id = '';

    /**
     * The "rel" property.
     */
    protected string $rel = '';

    /** Registry holding all action strategies */
    private StrategyRegistry $strategyRegistry;

    /**
     * Constructor.
     *
     * @param string $url URL
     */
    public function __construct(
        public string $url,
        private readonly Configuration $configuration,
        ?StrategyRegistry $strategyRegistry = null,
    ) {
        if ($strategyRegistry === null) {
            // default registry population (previous behavior)
            $strategyRegistry = new StrategyRegistry([
                self::LINK_GET_ACTION_FAQ => new FaqStrategy(),
                self::LINK_GET_ACTION_SEARCH => new SearchStrategy(),
                self::LINK_GET_ACTION_SITEMAP => new SitemapStrategy(),
                self::LINK_GET_ACTION_SHOW => new ShowStrategy(),
                self::LINK_GET_ACTION_NEWS => new NewsStrategy(),
                // Simple path-based strategies
                self::LINK_GET_ACTION_ADD => new GenericPathStrategy(self::LINK_HTML_ADDCONTENT),
                self::LINK_GET_ACTION_ASK => new GenericPathStrategy(self::LINK_HTML_ASK),
                self::LINK_GET_ACTION_CONTACT => new GenericPathStrategy(self::LINK_HTML_CONTACT),
                self::LINK_GET_ACTION_GLOSSARY => new GenericPathStrategy(self::LINK_HTML_GLOSSARY),
                self::LINK_GET_ACTION_HELP => new GenericPathStrategy(self::LINK_HTML_HELP),
                self::LINK_GET_ACTION_OPEN => new GenericPathStrategy(self::LINK_HTML_OPEN),
                self::LINK_GET_ACTION_LOGIN => new GenericPathStrategy(self::LINK_HTML_LOGIN),
                self::LINK_GET_ACTION_PASSWORD => new GenericPathStrategy(self::LINK_HTML_FORGOT_PASSWORD),
                self::LINK_GET_ACTION_BOOKMARKS => new GenericPathStrategy(self::LINK_HTML_BOOKMARKS),
                self::LINK_GET_ACTION_REGISTER => new GenericPathStrategy(self::LINK_HTML_REGISTER),
            ]);
        } else {
            // Merge missing default strategies when a custom registry is injected (non-destructive)
            $this->ensureDefaultStrategies($strategyRegistry);
        }
        $this->strategyRegistry = $strategyRegistry;
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
    public function getSystemUri(?string $path = null): string
    {
        $request = Request::createFromGlobals();
        $host = $request->getHost();
        $host = preg_replace(pattern: ['/:80$/', '/:443$/'], replacement: '', subject: $host);

        $sysUri = $this->getSystemScheme() . $host;

        return $sysUri . self::getSystemRelativeUri($path);
    }

    /**
     * Returns the system scheme, http or https.
     */
    public function getSystemScheme(): string
    {
        $request = Request::createFromGlobals();

        if ($this->configuration->get(item: 'security.useSslOnly')) {
            return 'https://';
        }

        return $request->isSecure() ? 'https://' : 'http://';
    }

    /**
     * Returns the relative URI.
     */
    public static function getSystemRelativeUri(?string $path = null): string
    {
        $request = Request::createFromGlobals();
        $scriptName = $request->getScriptName();

        if (isset($path)) {
            return str_replace(search: $path, replace: '', subject: $scriptName);
        }

        return str_replace(search: '/src/Link.php', replace: '', subject: $scriptName);
    }

    /**
     * Builds an HTML anchor.
     */
    public function toHtmlAnchor(): string
    {
        $url = $this->toString();
        $html = '<a';

        $add = function (string $attribute, string $value) use (&$html): void {
            if ($value !== '' && $value !== '0') {
                $html .= sprintf(self::ATTR_FMT, $attribute, $this->escapeAttr($value));
            }
        };

        $add(attribute: 'class', value: $this->class);
        $add(attribute: 'id', value: $this->id);
        $add(attribute: 'title', value: $this->tooltip ?? '');
        $add(attribute: 'name', value: $this->name);

        if (($this->name === '' || $this->name === '0') && $this->url !== '' && $this->url !== '0') {
            $html .= sprintf(' href="%s"', $this->escapeUrl($url));
            $add(attribute: 'target', value: $this->target);
        }
        $add(attribute: 'rel', value: $this->rel);

        $html .= '>';

        $body = $this->text;

        if ($body === '' || $body === '0') {
            $body = $this->name !== '' && $this->name !== '0' ? $this->name : $url;
        }
        $html .= $body === $url ? $this->escapeUrl($body) : $this->escapeAttr($body);

        return $html . '</a>';
    }

    private function escapeAttr(string $value): string
    {
        return Strings::htmlentities($value);
    }

    private function escapeUrl(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8');
    }

    /**
     * Rewrites a URL string. Checks mod_rewrite support and 'rewrite'
     * the passed (system) uri according to the rewrite rules written
     * in .htaccess
     */
    public function toString(): string
    {
        $url = $this->toUri();

        if (!$this->isHomeIndex()) {
            return $url;
        }

        $p = $this->getHttpGetParameters();
        if (!isset($p[self::LINK_GET_ACTION])) {
            return $url;
        }

        $url = substr($url, offset: 0, length: strpos($url, self::LINK_INDEX_HOME) + 1);
        $action = $p[self::LINK_GET_ACTION];

        $built = $this->buildActionUrl($action, $p);
        if ($built !== null) {
            $url .= $built;
        }

        if (isset($p[self::LINK_GET_SIDS])) {
            $url = $this->appendSessionId($url, (int) $p[self::LINK_GET_SIDS]);
        }

        if (isset($p['fragment'])) {
            $url .= self::LINK_FRAGMENT_SEPARATOR . $p['fragment'];
        }

        return $url;
    }

    /**
     * Returns URL without query string (legacy behavior when $removeSessionFromUrl was true).
     */
    public function toStringWithoutSession(): string
    {
        $url = $this->toString();
        /* @mago-expect lint:no-literal-password - false positive */
        return strtok($url, token: '?');
    }

    /**
     * Dispatcher for action-specific URL components.
     *
     * @param array<string,string> $p
     */
    private function buildActionUrl(string $action, array $p): ?string
    {
        $strategy = $this->strategyRegistry->get($action);
        if ($strategy) {
            return $strategy->build($p, $this);
        }
        return null; // unknown action returns null -> original URL retained
    }

    /**
     * Transforms a URI.
     */
    public function toUri(): string
    {
        if ($this->url !== '' && $this->url !== '0' && (!$this->hasScheme() && !$this->isInternalReference())) {
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

        return isset($parsed['scheme']) && ($parsed['scheme'] !== '' && $parsed['scheme'] !== '0');
    }

    /**
     * Checks if URL is an internal reference.
     */
    protected function isInternalReference(): bool
    {
        if ($this->isRelativeSystemLink()) {
            return true;
        }

        if (!str_contains($this->url, needle: '#')) {
            return false;
        }

        return str_starts_with($this->url, '#');
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

        return $slashIdx === 0;
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
        return LinkQueryParser::parse($this->url);
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

    public function setTitle(string $title): Link
    {
        $this->itemTitle = $title;
        return $this;
    }

    /**
     * Returns a search engine optimized title.
     */
    public function getSEOTitle(string $title = ''): string
    {
        if ($title === '') {
            $title = $this->itemTitle;
        }
        return TitleSlugifier::slug($title);
    }

    /**
     * Appends the session id.
     *
     * @param string $url URL
     * @param int $sids Session ID
     */
    private function appendSessionId(string $url, int $sids): string
    {
        $separator = str_contains($url, self::LINK_SEARCHPART_SEPARATOR)
            ? self::LINK_AMPERSAND
            : self::LINK_SEARCHPART_SEPARATOR;

        return $url . $separator . self::LINK_GET_SIDS . self::LINK_EQUAL . $sids;
    }

    /**
     * Returns the injected StrategyRegistry instance.
     */
    public function getStrategyRegistry(): StrategyRegistry
    {
        return $this->strategyRegistry;
    }

    /**
     * Registers or overrides a strategy at runtime (plugin extension point).
     */
    public function registerStrategy(string $action, StrategyInterface $strategy): void
    {
        $this->strategyRegistry->register($action, $strategy);
    }

    /**
     * Ensures that all default strategies exist in the provided registry without overriding existing entries.
     */
    private function ensureDefaultStrategies(StrategyRegistry $registry): void
    {
        $defaults = [
            self::LINK_GET_ACTION_FAQ => static fn() => new FaqStrategy(),
            self::LINK_GET_ACTION_SEARCH => static fn() => new SearchStrategy(),
            self::LINK_GET_ACTION_SITEMAP => static fn() => new SitemapStrategy(),
            self::LINK_GET_ACTION_SHOW => static fn() => new ShowStrategy(),
            self::LINK_GET_ACTION_NEWS => static fn() => new NewsStrategy(),
            // Simple path-based strategies
            self::LINK_GET_ACTION_ADD => static fn() => new GenericPathStrategy(self::LINK_HTML_ADDCONTENT),
            self::LINK_GET_ACTION_ASK => static fn() => new GenericPathStrategy(self::LINK_HTML_ASK),
            self::LINK_GET_ACTION_CONTACT => static fn() => new GenericPathStrategy(self::LINK_HTML_CONTACT),
            self::LINK_GET_ACTION_GLOSSARY => static fn() => new GenericPathStrategy(self::LINK_HTML_GLOSSARY),
            self::LINK_GET_ACTION_HELP => static fn() => new GenericPathStrategy(self::LINK_HTML_HELP),
            self::LINK_GET_ACTION_OPEN => static fn() => new GenericPathStrategy(self::LINK_HTML_OPEN),
            self::LINK_GET_ACTION_LOGIN => static fn() => new GenericPathStrategy(self::LINK_HTML_LOGIN),
            self::LINK_GET_ACTION_PASSWORD => static fn() => new GenericPathStrategy(self::LINK_HTML_FORGOT_PASSWORD),
            self::LINK_GET_ACTION_BOOKMARKS => static fn() => new GenericPathStrategy(self::LINK_HTML_BOOKMARKS),
            self::LINK_GET_ACTION_REGISTER => static fn() => new GenericPathStrategy(self::LINK_HTML_REGISTER),
        ];
        foreach ($defaults as $action => $factory) {
            if ($registry->has($action)) {
                continue;
            }

            $registry->register($action, $factory());
        }
    }
}
