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
 * @copyright 2007-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-31
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\User\CurrentUser;
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

    /** @var string Azure AD session key */
    final public const PMF_AZURE_AD_SESSIONKEY = 'phpmyfaq_aad_sessionkey';

    /** @var string */
    final public const PMF_AZURE_AD_OAUTH_VERIFIER = 'phpmyfaq_azure_ad_oauth_verifier';

    /** @var string */
    final public const PMF_AZURE_AD_JWT = 'phpmyfaq_azure_ad_jwt';

    private ?int $currentSessionId = null;

    private string $currentSessionKey;

    private readonly string $currentToken;

    private ?CurrentUser $currentUser = null;

    /** @var string[] List of bots we don't track */
    private array $botIgnoreList = [
        'nustcrape',
        'webpost',
        'GoogleBot',
        'msnbot',
        'crawler',
        'scooter',
        'bravobrian',
        'archiver',
        'w3c',
        'control',
        'wget',
        'bot',
        'spider',
        'Yahoo! Slurp',
        'htdig',
        'gsa-crawler',
        'AirControl'
    ];

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
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
        return $this->currentSessionKey ?? $_SESSION[self::PMF_AZURE_AD_SESSIONKEY];
    }

    public function getCurrentToken(): ?string
    {
        return $this->currentToken ?? $_SESSION[self::PMF_AZURE_AD_JWT];
    }

    /**
     * Sets the current UUID session key
     */
    public function setCurrentSessionKey(): Session
    {
        if (!isset($this->currentSessionKey)) {
            $this->createCurrentSessionKey();
        }

        $_SESSION[self::PMF_AZURE_AD_SESSIONKEY] = $this->getCurrentSessionKey();
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
        return $_SESSION[$key];
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

        $result = $this->config->getDb()->query($query);

        if ($result) {
            $res = $this->config->getDb()->fetchObject($result);
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

        $result = $this->config->getDb()->query($query);
        while ($row = $this->config->getDb()->fetchObject($result)) {
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

        $result = $this->config->getDb()->query($query);
        if ($result) {
            $row = $this->config->getDb()->fetchObject($result);
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

        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Deletes all entries in the table.
     */
    public function deleteAllSessions(): mixed
    {
        $query = sprintf('DELETE FROM %sfaqsessions', Database::getTablePrefix());

        return $this->config->getDb()->query($query);
    }

    /**
     * Checks the Session ID.
     *
     * @param int $sessionIdToCheck Session ID
     * @param string $ip IP
     * @throws Exception
     */
    public function checkSessionId(int $sessionIdToCheck, string $ip): void
    {
        $query = sprintf(
            "SELECT sid FROM %sfaqsessions WHERE sid = %d AND ip = '%s' AND time > %d",
            Database::getTablePrefix(),
            $sessionIdToCheck,
            $ip,
            $_SERVER['REQUEST_TIME'] - 86400
        );
        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) == 0) {
            $this->userTracking('old_session', $sessionIdToCheck);
        } else {
            // Update global session id
            $this->setCurrentSessionId($sessionIdToCheck);
            // Update db tracking
            $query = sprintf(
                "UPDATE %sfaqsessions SET time = %d, user_id = %d WHERE sid = %d AND ip = '%s'",
                Database::getTablePrefix(),
                $_SERVER['REQUEST_TIME'],
                $this->currentUser->getUserId(),
                $sessionIdToCheck,
                $ip
            );
            $this->config->getDb()->query($query);
        }
    }

    /**
     * Tracks the user and log what he did.
     *
     * @param string          $action Action string
     * @param int|string|null $data
     * @throws Exception
     */
    public function userTracking(string $action, int|string $data = null): void
    {
        if ($this->config->get('main.enableUserTracking')) {
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
            if ($action === 'old_session') {
                $this->setCurrentSessionId(0);
            }

            foreach ($this->botIgnoreList as $bot) {
                if (Strings::strstr($request->headers->get('user-agent'), $bot)) {
                    ++$bots;
                }
            }

            // if we're running behind a reverse proxy like nginx/varnish, fix the client IP
            $remoteAddress = Request::createFromGlobals()->getClientIp();
            $localAddresses = ['127.0.0.1', '::1'];

            if (in_array($remoteAddress, $localAddresses) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $remoteAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

            // clean up as well
            $remoteAddress = preg_replace('([^0-9a-z:\.]+)i', '', (string) $remoteAddress);

            // Anonymize IP address
            $remoteAddress = IpUtils::anonymize($remoteAddress);

            $network = new Network($this->config);
            if ($network->isBanned($remoteAddress)) {
                $banned = true;
            }

            if (0 === $bots && false === $banned) {
                if (!isset($this->currentSessionId)) {
                    $this->currentSessionId = $this->config->getDb()->nextId(
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
                        $_SERVER['REQUEST_TIME']
                    );

                    $this->config->getDb()->query($query);
                }

                $data = $this->getCurrentSessionId() . ';' .
                    str_replace(';', ',', $action) . ';' .
                    $data . ';' .
                    $remoteAddress . ';' .
                    str_replace(';', ',', $_SERVER['QUERY_STRING'] ?? '') . ';' .
                    str_replace(';', ',', $_SERVER['HTTP_REFERER'] ?? '') . ';' .
                    str_replace(';', ',', urldecode((string) $_SERVER['HTTP_USER_AGENT'])) . ';' .
                    $_SERVER['REQUEST_TIME'] . ";\n";

                $file = PMF_ROOT_DIR . '/data/tracking' . date('dmY');

                if (!is_file($file)) {
                    touch($file);
                }

                if (is_writeable($file)) {
                    file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
                } else {
                    throw new Exception('Cannot write to ' . $file);
                }
            }
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
    public function setCookie(string $name, int|string|null $sessionId, int $timeout = 3600): bool
    {
        $secure = false;
        if (isset($_SERVER['HTTPS']) && strtoupper((string) $_SERVER['HTTPS']) === 'ON') {
            $secure = true;
        }

        return setcookie(
            $name,
            $sessionId ?? '',
            [
                'expires' => $_SERVER['REQUEST_TIME'] + $timeout,
                'path' => dirname((string) $_SERVER['SCRIPT_NAME']),
                'domain' => parse_url($this->config->getDefaultUrl(), PHP_URL_HOST),
                'samesite' => 'strict',
                'secure' => $secure,
                'httponly' => true,
            ]
        );
    }

    /**
     * Returns the number of anonymous users and registered ones.
     * These are the numbers of unique users who have performed
     * some activities within the last five minutes.
     *
     * @param int $activityTimeWindow Optionally set the time window size in sec.
     *                                Default: 300sec, 5 minutes
     *
     * @return array<int>
     */
    public function getUsersOnline(int $activityTimeWindow = 300): array
    {
        $users = [0, 0];

        if ($this->config->get('main.enableUserTracking')) {
            $timeNow = ($_SERVER['REQUEST_TIME'] - $activityTimeWindow);

            if (!$this->config->get('security.enableLoginOnly')) {
                // Count all sids within the time window for public installations
                $query = sprintf(
                    'SELECT count(sid) AS anonymous_users FROM %sfaqsessions WHERE user_id = -1 AND time > %d',
                    Database::getTablePrefix(),
                    $timeNow
                );

                $result = $this->config->getDb()->query($query);

                if (isset($result)) {
                    $row = $this->config->getDb()->fetchObject($result);
                    $users[0] = $row->anonymous_users;
                }
            }

            // Count all faq user records within the time window
            $query = sprintf(
                'SELECT count(session_id) AS registered_users FROM %sfaquser WHERE session_timestamp > %d',
                Database::getTablePrefix(),
                $timeNow
            );

            $result = $this->config->getDb()->query($query);

            if (isset($result)) {
                $row = $this->config->getDb()->fetchObject($result);
                $users[1] = $row->registered_users;
            }
        }

        return $users;
    }

    /**
     * Calculates the number of visits per day the last 30 days.
     *
     * @return array<int, stdClass>
     */
    public function getLast30DaysVisits(): array
    {
        $stats = $visits = $completeData = [];

        $startDate = strtotime('-1 month');
        $endDate = $_SERVER['REQUEST_TIME'];

        $query = sprintf(
            'SELECT time FROM %sfaqsessions WHERE time > %d AND time < %d;',
            Database::getTablePrefix(),
            $startDate,
            $endDate
        );
        $result = $this->config->getDb()->query($query);

        while ($row = $this->config->getDb()->fetchObject($result)) {
            $visits[] = $row->time;
        }

        for ($date = $startDate; $date <= $endDate; $date += 86400) {
            $stats[date('Y-m-d', $date)] = 0;
        }

        foreach ($visits as $visitDate) {
            isset($stats[date('Y-m-d', $visitDate)]) ? $stats[date('Y-m-d', $visitDate)]++ : null;
        }

        foreach ($stats as $date => $stat) {
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
    }
}
