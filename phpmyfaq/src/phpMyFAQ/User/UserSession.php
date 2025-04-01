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
 * @copyright 2007-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-31
 */

namespace phpMyFAQ\User;

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
    final public const COOKIE_NAME_REMEMBER_ME = 'pmf-remember-me';

    /** @var string Name of the session cookie */
    final public const COOKIE_NAME_SESSION_ID = 'pmf-sid';

    /** @var string Name of the session GET parameter */
    final public const KEY_NAME_SESSION_ID = 'sid';

    private ?int $currentSessionId = null;

    private ?CurrentUser $currentUser = null;

    public function __construct(private readonly Configuration $configuration)
    {
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
        $query = sprintf(
            "SELECT sid FROM %sfaqsessions WHERE sid = %d AND ip = '%s' AND time > %d",
            Database::getTablePrefix(),
            $sessionIdToCheck,
            $ipAddress,
            Request::createFromGlobals()->server->get('REQUEST_TIME') - 86400
        );
        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) == 0) {
            $this->userTracking(SessionActionType::OLD_SESSION->value, $sessionIdToCheck);
        } else {
            // Update global session id
            $this->setCurrentSessionId($sessionIdToCheck);
            // Update db tracking
            $query = sprintf(
                "UPDATE %sfaqsessions SET time = %d, user_id = %d WHERE sid = %d AND ip = '%s'",
                Database::getTablePrefix(),
                Request::createFromGlobals()->server->get('REQUEST_TIME'),
                $this->currentUser->getUserId(),
                $sessionIdToCheck,
                $ipAddress
            );
            $this->configuration->getDb()->query($query);
        }
    }

    /**
     * Returns the botIgnoreList as an array.
     *
     * @return array<string>
     */
    private function getBotIgnoreList(): array
    {
        return explode(',', (string) $this->configuration->get('main.botIgnoreList'));
    }

    /**
     * Tracks the user and log what he did.
     *
     * @param string          $action Action string
     * @param int|string|null $data
     */
    public function userTracking(string $action, int|string|null $data = null): void
    {
        if (!$this->configuration->get('main.enableUserTracking')) {
            return;
        }

        $request = Request::createFromGlobals();
        $bots = 0;
        $banned = false;
        $this->currentSessionId = Filter::filterVar(
            $request->query->get(self::KEY_NAME_SESSION_ID),
            FILTER_VALIDATE_INT
        );
        $cookieId = Filter::filterVar($request->query->get(self::COOKIE_NAME_SESSION_ID), FILTER_VALIDATE_INT);

        if (!is_null($cookieId)) {
            $this->setCurrentSessionId($cookieId);
        }

        if ($action === SessionActionType::OLD_SESSION->value) {
            $this->setCurrentSessionId(0);
        }

        foreach ($this->getBotIgnoreList() as $bot) {
            if (Strings::strstr($request->headers->get('user-agent'), $bot)) {
                ++$bots;
            }
        }

        // if we're running behind a reverse proxy like nginx/varnish, fix the client IP
        $remoteAddress = Request::createFromGlobals()->getClientIp();
        $localAddresses = ['127.0.0.1', '::1'];

        if (in_array($remoteAddress, $localAddresses) && $request->headers->has('X-Forwarded-For')) {
            $remoteAddress = $request->headers->get('X-Forwarded-For');
        }

        // clean up as well
        $remoteAddress = preg_replace('([^0-9a-z:.]+)i', '', (string) $remoteAddress);

        // Anonymize IP address
        $remoteAddress = IpUtils::anonymize($remoteAddress);

        $network = new Network($this->configuration);
        if ($network->isBanned($remoteAddress)) {
            $banned = true;
        }

        if (0 === $bots && false === $banned) {
            if ($this->currentSessionId === null) {
                $this->currentSessionId = $this->configuration->getDb()->nextId(
                    Database::getTablePrefix() . 'faqsessions',
                    'sid'
                );
                // Check: force the session cookie to contains the current $sid
                if (!is_null($cookieId) && (!$cookieId != $this->getCurrentSessionId())) {
                    self::setCookie(self::COOKIE_NAME_SESSION_ID, $this->getCurrentSessionId());
                }

                $query = sprintf(
                    "INSERT INTO %sfaqsessions (sid, user_id, ip, time) VALUES (%d, %d, '%s', %d)",
                    Database::getTablePrefix(),
                    $this->getCurrentSessionId(),
                    isset($this->currentUser) ? $this->currentUser->getUserId() : 0,
                    $remoteAddress,
                    $request->server->get('REQUEST_TIME')
                );

                $this->configuration->getDb()->query($query);
            }

            $data = $this->getCurrentSessionId() . ';' .
                str_replace(';', ',', $action) . ';' .
                $data . ';' .
                $remoteAddress . ';' .
                str_replace(';', ',', $request->server->get('QUERY_STRING') ?? '') . ';' .
                str_replace(';', ',', $request->server->get('HTTP_REFERER') ?? '') . ';' .
                str_replace(';', ',', urldecode((string) $request->server->get('HTTP_USER_AGENT'))) . ';' .
                $request->server->get('REQUEST_TIME') . ";\n";

            $file = PMF_ROOT_DIR . '/content/core/data/tracking' . date('dmY');

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
     * Store the Session ID into a persistent cookie expiring
     * 3600 seconds after the page request.
     *
     * @param string          $name Cookie name
     * @param int|string|null $sessionId Session ID
     * @param int             $timeout Cookie timeout
     */
    public function setCookie(string $name, int|string|null $sessionId, int $timeout = 3600, bool $strict = true): void
    {
        $request = Request::createFromGlobals();

        setcookie($name, $sessionId ?? '', [
            'expires' => $request->server->get('REQUEST_TIME') + $timeout,
            'path' => dirname($request->server->get('SCRIPT_NAME')),
            'domain' => parse_url($this->configuration->getDefaultUrl(), PHP_URL_HOST),
            'secure' => $request->isSecure(),
            'httponly' => true,
            'samesite' => $strict ? 'strict' : '',
        ]);
    }
}
