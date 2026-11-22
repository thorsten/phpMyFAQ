<?php

/**
 * The main Session class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-31
 */

declare(strict_types=1);

namespace phpMyFAQ\User;

use Closure;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\SessionActionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Network;
use phpMyFAQ\Strings;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Session
 *
 * @package phpMyFAQ
 */
class UserSession
{
    /** @var string Name of the "remember me" cookie */
    final public const string COOKIE_NAME_REMEMBER_ME = 'pmf-remember-me';

    /** @var string Name of the session cookie */
    final public const string COOKIE_NAME_SESSION_ID = 'pmf-sid';

    /** @var string Name of the session GET parameter */
    final public const string KEY_NAME_SESSION_ID = 'sid';

    /** @var int Lifetime of the visitor session cookie in seconds, renewed with every tracked request */
    private const int SESSION_ID_COOKIE_LIFETIME = 3600;

    private ?int $currentSessionId = null;

    /** @var int|null Visitor session resolved for this request, so several tracked actions share one session */
    private ?int $trackedSessionId = null;

    private ?CurrentUser $currentUser = null;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly ?Request $request = null,
        /** @var (Closure(Configuration): Network)|null */
        private readonly ?Closure $networkFactory = null,
        private readonly ?Closure $cookieSetter = null,
        private readonly ?string $trackingDirectory = null,
    ) {
    }

    /**
     * Returns the current session ID.
     */
    public function getCurrentSessionId(): ?int
    {
        return $this->currentSessionId;
    }

    /**
     * Sets the current session ID.
     */
    public function setCurrentSessionId(int $currentSessionId): UserSession
    {
        $this->currentSessionId = $currentSessionId;
        return $this;
    }

    /**
     * Sets current User object
     */
    public function setCurrentUser(CurrentUser $currentUser): UserSession
    {
        $this->currentUser = $currentUser;
        return $this;
    }

    /**
     * Checks the Session ID.
     *
     * @param int    $sessionIdToCheck Session ID
     * @param string $ipAddress IP
     */
    public function checkSessionId(int $sessionIdToCheck, string $ipAddress): void
    {
        $request = $this->getRequest();
        $query = sprintf(
            "SELECT sid FROM %sfaqsessions WHERE sid = %d AND ip = '%s' AND time > %d",
            Database::getTablePrefix(),
            $sessionIdToCheck,
            $this->configuration->getDb()->escape($ipAddress),
            (int) $request->server->get('REQUEST_TIME') - 86_400,
        );
        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) === 0) {
            $this->userTracking(SessionActionType::OLD_SESSION->value, $sessionIdToCheck);
            return;
        }

        // Update global session id
        $this->setCurrentSessionId($sessionIdToCheck);
        // Update db tracking
        $query = sprintf(
            "UPDATE %sfaqsessions SET time = %d, user_id = %d WHERE sid = %d AND ip = '%s'",
            Database::getTablePrefix(),
            (int) $request->server->get('REQUEST_TIME'),
            $this->currentUser instanceof CurrentUser ? $this->currentUser->getUserId() : 0,
            $sessionIdToCheck,
            $this->configuration->getDb()->escape($ipAddress),
        );
        $this->configuration->getDb()->query($query);
    }

    /**
     * Returns the botIgnoreList as an array.
     *
     * @return array<string>
     */
    private function getBotIgnoreList(): array
    {
        return explode(',', (string) $this->configuration->get(item: 'main.botIgnoreList'));
    }

    /**
     * Tracks the user and log what he did.
     */
    public function userTracking(string $action, int|string|null $data = null): void
    {
        if (!$this->configuration->get(item: 'main.enableUserTracking')) {
            return;
        }

        $request = $this->getRequest();
        $bots = 0;
        $banned = false;

        $userAgent = (string) $request->headers->get('user-agent');
        foreach ($this->getBotIgnoreList() as $bot) {
            if (!Strings::strstr($userAgent, $bot)) {
                continue;
            }

            ++$bots;
        }

        // if we're running behind a reverse proxy like nginx/varnish, fix the client IP
        $remoteAddress = $request->getClientIp();
        $localAddresses = ['127.0.0.1', '::1'];

        if (in_array($remoteAddress, $localAddresses, strict: true) && $request->headers->has('X-Forwarded-For')) {
            $remoteAddress = $request->headers->get('X-Forwarded-For');
        }

        // clean up as well
        $remoteAddress = preg_replace('([^0-9a-z:.]+)i', replacement: '', subject: (string) $remoteAddress);

        if (
            !is_string($remoteAddress)
            || $remoteAddress === ''
            || filter_var($remoteAddress, FILTER_VALIDATE_IP) === false
        ) {
            $remoteAddress = '127.0.0.1';
        }

        // Anonymize IP address
        $remoteAddress = IpUtils::anonymize($remoteAddress);

        $network = $this->createNetwork();
        if ($network->isBanned($remoteAddress)) {
            $banned = true;
        }

        if (0 === $bots && false === $banned) {
            if ($action === SessionActionType::OLD_SESSION->value) {
                $this->setCurrentSessionId(0);
            }

            if ($action !== SessionActionType::OLD_SESSION->value) {
                $this->resumeOrStartSession($request, $remoteAddress);
            }

            $redactor = new TrackingDataRedactor();

            $data =
                (string) $this->getCurrentSessionId()
                . ';'
                . $redactor->flattenField($action)
                . ';'
                . $redactor->flattenField((string) $data)
                . ';'
                . $remoteAddress
                . ';'
                . $redactor->flattenField($redactor->redactQueryString(
                    (string) ($request->server->get('QUERY_STRING') ?? ''),
                ))
                . ';'
                . $redactor->flattenField($redactor->redactUrl((string) ($request->server->get('HTTP_REFERER') ?? '')))
                . ';'
                . $redactor->flattenField(urldecode(string: (string) $request->server->get('HTTP_USER_AGENT')))
                . ';'
                . (int) $request->server->get('REQUEST_TIME')
                . ";\n";

            $file = $this->getTrackingDirectory() . '/tracking' . date(format: 'dmY');

            if (!is_file($file)) {
                touch($file);
            }

            if (!is_writable($file)) {
                $this->configuration->getLogger()->error('Cannot write to ' . $file);
            }

            file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Resolves the visitor session of this request: the session named by the "pmf-sid" cookie (or the legacy
     * "sid" query parameter) is resumed when checkSessionId() accepts it, otherwise a new session is started.
     * The cookie is renewed either way, so the next request of this visitor resumes the same session.
     */
    private function resumeOrStartSession(Request $request, string $remoteAddress): void
    {
        if ($this->trackedSessionId !== null) {
            $this->currentSessionId = $this->trackedSessionId;
            return;
        }

        $this->currentSessionId = null;
        // all() instead of get(): a cookie sent as an array must not turn the page view into a "400 Bad Request"
        $knownSessionId = Filter::filterVar(
            $request->cookies->all()[self::COOKIE_NAME_SESSION_ID] ?? null,
            FILTER_VALIDATE_INT,
        ) ?? Filter::filterVar($request->query->get(self::KEY_NAME_SESSION_ID), FILTER_VALIDATE_INT);

        if (is_int($knownSessionId)) {
            // Sets the current session ID to the known one, or to 0 when it is unknown, expired or from another IP
            $this->checkSessionId($knownSessionId, $remoteAddress);
        }

        $resumedSessionId = $this->getCurrentSessionId();
        if ($resumedSessionId === null || $resumedSessionId === 0) {
            $this->startSession($request, $remoteAddress);
        }

        $this->trackedSessionId = $this->currentSessionId;
        $this->sendSessionIdCookie($request);
    }

    private function startSession(Request $request, string $remoteAddress): void
    {
        $this->currentSessionId = $this->configuration->getDb()->nextId(
            Database::getTablePrefix() . 'faqsessions',
            column: 'sid',
        );

        $query = sprintf(
            "INSERT INTO %sfaqsessions (sid, user_id, ip, time) VALUES (%d, %d, '%s', %d)",
            Database::getTablePrefix(),
            $this->currentSessionId,
            $this->currentUser instanceof CurrentUser ? $this->currentUser->getUserId() : 0,
            $remoteAddress,
            (int) $request->server->get('REQUEST_TIME'),
        );

        $this->configuration->getDb()->query($query);
    }

    /**
     * Hands the visitor session ID to the browser. The path is the one of the FAQ itself rather than the one of
     * the running script, so the pages and the public API under /api share one cookie. SameSite is "lax": with
     * "strict" a visitor following a link from another site would arrive without the cookie and be counted twice.
     */
    private function sendSessionIdCookie(Request $request): void
    {
        $defaultUrl = $this->configuration->getDefaultUrl();
        $path = rtrim((string) parse_url($defaultUrl, PHP_URL_PATH), characters: '/');

        $options = [
            'expires' => (int) $request->server->get('REQUEST_TIME') + self::SESSION_ID_COOKIE_LIFETIME,
            'path' => $path === '' ? '/' : $path,
            'domain' => (string) parse_url($defaultUrl, PHP_URL_HOST),
            'secure' => $request->isSecure(),
            'httponly' => true,
            'samesite' => 'lax',
        ];

        if ($this->cookieSetter instanceof Closure) {
            ($this->cookieSetter)(self::COOKIE_NAME_SESSION_ID, (string) $this->currentSessionId, $options);
            return;
        }

        // The page views are still counted when the cookie cannot be sent, e.g. on the command line
        if (headers_sent()) {
            return;
        }

        setcookie(self::COOKIE_NAME_SESSION_ID, (string) $this->currentSessionId, $options);
    }

    /**
     * Store the Session ID into a persistent cookie expiring
     * 3600 seconds after the page request.
     *
     * @param string          $name Cookie name
     * @param int|string|null $sessionId Session ID
     * @param int             $timeout Cookie timeout
     */
    public function setCookie(string $name, int|string|null $sessionId, int $timeout = 3600, bool $strict = true): void
    {
        $request = $this->getRequest();

        $options = [
            'expires' => (int) $request->server->get('REQUEST_TIME') + $timeout,
            'path' => dirname((string) $request->server->get('SCRIPT_NAME')),
            'domain' => parse_url($this->configuration->getDefaultUrl(), PHP_URL_HOST),
            'secure' => $request->isSecure(),
            'httponly' => true,
            'samesite' => $strict ? 'strict' : '',
        ];

        if ($this->cookieSetter instanceof Closure) {
            ($this->cookieSetter)($name, (string) $sessionId, $options);
            return;
        }

        setcookie($name, (string) $sessionId, $options);
    }

    private function getRequest(): Request
    {
        return $this->request ?? Request::createFromGlobals();
    }

    private function createNetwork(): Network
    {
        if ($this->networkFactory instanceof Closure) {
            return ($this->networkFactory)($this->configuration);
        }

        return new Network($this->configuration);
    }

    private function getTrackingDirectory(): string
    {
        return $this->trackingDirectory ?? (string) PMF_ROOT_DIR . '/content/core/data';
    }
}
