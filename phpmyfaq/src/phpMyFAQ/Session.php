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
 * @copyright 2007-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-31
 */

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Enums\SessionActionType;
use phpMyFAQ\User\CurrentUser;
use Random\RandomException;
use stdClass;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Session
 *
 * @package phpMyFAQ
 */
class Session
{
    /** @var string Name of the "remember me" cookie */
    final public const PMF_COOKIE_NAME_REMEMBERME = 'pmf_rememberme';

    /** @var string Name of the session cookie */
    final public const PMF_COOKIE_NAME_SESSIONID = 'pmf_sid';

    /** @var string Name of the session GET parameter */
    final public const PMF_GET_KEY_NAME_SESSIONID = 'sid';

    /** @var string EntraID session key */
    final public const PMF_AZURE_AD_SESSIONKEY = 'phpmyfaq_aad_sessionkey';

    /** @var string */
    final public const PMF_AZURE_AD_OAUTH_VERIFIER = 'phpmyfaq_azure_ad_oauth_verifier';

    /** @var string */
    final public const PMF_AZURE_AD_JWT = 'phpmyfaq_azure_ad_jwt';

    private ?int $currentSessionId = null;

    private string $currentSessionKey;

    private ?CurrentUser $currentUser = null;

    public function __construct(private readonly Configuration $configuration)
    {
        $this->createCurrentSessionKey();
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
    public function setCurrentSessionId(int $currentSessionId): Session
    {
        $this->currentSessionId = $currentSessionId;
        return $this;
    }

    /**
     * Sets current User object
     */
    public function setCurrentUser(CurrentUser $currentUser): Session
    {
        $this->currentUser = $currentUser;
        return $this;
    }

    /**
     * Returns the current UUID session key
     */
    public function getCurrentSessionKey(): ?string
    {
        return $this->currentSessionKey ?? $this->get(self::PMF_AZURE_AD_SESSIONKEY);
    }

    /**
     * Sets the current UUID session key
     *
     * @throws Exception
     */
    public function setCurrentSessionKey(): Session
    {
        if (!isset($this->currentSessionKey)) {
            $this->createCurrentSessionKey();
        }

        $this->set(self::PMF_AZURE_AD_SESSIONKEY, $this->currentSessionKey);

        return $this;
    }

    /**
     * Creates the current UUID session key
     */
    public function createCurrentSessionKey(): void
    {
        $this->currentSessionKey = $this->uuid();
    }

    public function set(string $key, string $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key): string
    {
        return $_SESSION[$key] ?? '';
    }

    /**
     * Returns the timestamp of a session.
     *
     * @param int $sessionId Session ID
     */
    public function getTimeFromSessionId(int $sessionId): int
    {
        $timestamp = 0;

        $query = sprintf('SELECT time FROM %sfaqsessions WHERE sid = %d', Database::getTablePrefix(), $sessionId);

        $result = $this->configuration->getDb()->query($query);

        if ($result) {
            $res = $this->configuration->getDb()->fetchObject($result);
            $timestamp = $res->time;
        }

        return $timestamp;
    }

    /**
     * Returns all session from a date.
     *
     * @param int $firstHour First hour
     * @param int $lastHour Last hour
     *
     * @return array<int, string[]>
     */
    public function getSessionsByDate(int $firstHour, int $lastHour): array
    {
        $sessions = [];

        $query = sprintf(
            'SELECT sid, ip, time FROM %sfaqsessions WHERE time > %d AND time < %d ORDER BY time',
            Database::getTablePrefix(),
            $firstHour,
            $lastHour
        );

        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $sessions[$row->sid] = [
                'ip' => $row->ip,
                'time' => $row->time,
            ];
        }

        return $sessions;
    }

    /**
     * Returns the number of sessions.
     */
    public function getNumberOfSessions(): int
    {
        $num = 0;

        $query = sprintf('SELECT COUNT(sid) as num_sessions FROM %sfaqsessions', Database::getTablePrefix());

        $result = $this->configuration->getDb()->query($query);
        if ($result) {
            $row = $this->configuration->getDb()->fetchObject($result);
            $num = $row->num_sessions;
        }

        return $num;
    }

    /**
     * Deletes the sessions for a given timespan.
     *
     * @param int $first First session ID
     * @param int $last Last session ID
     */
    public function deleteSessions(int $first, int $last): bool
    {
        $query = sprintf(
            'DELETE FROM %sfaqsessions WHERE time >= %d AND time <= %d',
            Database::getTablePrefix(),
            $first,
            $last
        );

        $this->configuration->getDb()->query($query);

        return true;
    }

    /**
     * Deletes all entries in the table.
     */
    public function deleteAllSessions(): mixed
    {
        $query = sprintf('DELETE FROM %sfaqsessions', Database::getTablePrefix());

        return $this->configuration->getDb()->query($query);
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
            $request->query->get(self::PMF_GET_KEY_NAME_SESSIONID),
            FILTER_VALIDATE_INT
        );
        $cookieId = Filter::filterVar($request->query->get(self::PMF_COOKIE_NAME_SESSIONID), FILTER_VALIDATE_INT);

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
                    self::setCookie(self::PMF_COOKIE_NAME_SESSIONID, $this->getCurrentSessionId());
                }

                $query = sprintf(
                    "INSERT INTO %sfaqsessions (sid, user_id, ip, time) VALUES (%d, %d, '%s', %d)",
                    Database::getTablePrefix(),
                    $this->getCurrentSessionId(),
                    $this->currentUser->getUserId(),
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
    public function setCookie(string $name, int|string|null $sessionId, int $timeout = 3600, bool $strict = true): bool
    {
        $request = Request::createFromGlobals();

        return setcookie(
            $name,
            $sessionId ?? '',
            [
                'expires' => $request->server->get('REQUEST_TIME') + $timeout,
                'path' => dirname($request->server->get('SCRIPT_NAME')),
                'domain' => parse_url($this->configuration->getDefaultUrl(), PHP_URL_HOST),
                'samesite' => $strict ? 'strict' : '',
                'secure' => $request->isSecure(),
                'httponly' => true,
            ]
        );
    }

    /**
     * Returns the value of a cookie.
     *
     * @param string $name Cookie name
     */
    public function getCookie(string $name): string
    {
        $request = Request::createFromGlobals();
        return $request->cookies->get($name, '');
    }

    /**
     * Calculates the number of visits per day the last 30 days.
     *
     * @return array<int, stdClass>
     */
    public function getLast30DaysVisits(): array
    {
        $stats = [];
        $visits = [];
        $completeData = [];
        $startDate = strtotime('-1 month');
        $endDate = Request::createFromGlobals()->server->get('REQUEST_TIME');

        $query = sprintf(
            'SELECT time FROM %sfaqsessions WHERE time > %d AND time < %d;',
            Database::getTablePrefix(),
            $startDate,
            $endDate
        );
        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $visits[] = $row->time;
        }

        for ($date = $startDate; $date <= $endDate; $date += 86400) {
            $stats[date('Y-m-d', $date)] = 0;
        }

        foreach ($visits as $visitDate) {
            if (isset($stats[date('Y-m-d', $visitDate)])) {
                ++$stats[date('Y-m-d', $visitDate)];
            }
        }

        foreach (array_keys($stats) as $date) {
            $visit = new stdClass();
            $visit->date = $date;
            $visit->number = $stats[$date];
            $completeData[] = $visit;
        }

        return $completeData;
    }

    /**
     * Returns a UUID Version 4 compatible universally unique identifier.
     */
    public function uuid(): string
    {
        try {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff)
            );
        } catch (RandomException $e) {
            $this->configuration->getLogger()->error('Cannot generate UUID: ' . $e->getMessage());
            return '';
        }
    }
}
