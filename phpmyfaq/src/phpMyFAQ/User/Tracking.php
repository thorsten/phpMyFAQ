<?php

/**
 * Class for User tracking handling.
 *
 * This class handles all operations around creating, saving and getting the secret
 * for a CurrentUser for two-factor-authentication. It also validates given tokens in
 * comparison to a given secret and returns a QR-code for transmitting a secret to
 * the authenticator-app.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-10-29
 */

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\SessionActionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Network;
use phpMyFAQ\Strings;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

class Tracking
{
    private static ?Tracking $tracking = null;

    private ?int $currentSessionId = null;

    private function __construct(
        private readonly Configuration $configuration,
        private readonly Request $request,
        private readonly ?UserSession $userSession = null
    ) {
    }

    public static function getInstance(
        Configuration $configuration,
        Request $request,
        UserSession $userSession
    ): Tracking {
        if (!self::$tracking instanceof Tracking) {
            self::$tracking = new self($configuration, $request, $userSession);
        }

        return self::$tracking;
    }

    /**
     * @throws Exception
     */
    public function log(string $action, int|string|null $data = null): bool
    {
        if (!$this->configuration->get('main.enableUserTracking')) {
            return false;
        }

        $this->initializeSessionId();
        $cookieId = $this->getCookieId();

        if (!is_null($cookieId)) {
            $this->userSession->setCurrentSessionId($cookieId);
        }

        if ($action === SessionActionType::OLD_SESSION->value) {
            $this->userSession->setCurrentSessionId(0);
        }

        $bots = $this->countBots();
        $remoteAddress = $this->getRemoteAddress();
        $banned = $this->isBanned($remoteAddress);

        if (0 === $bots && false === $banned) {
            $this->handleSession($cookieId, $remoteAddress, $action, $data);
        }

        return true;
    }

    private function initializeSessionId(): void
    {
        $sessionId = $this->request->query->get(UserSession::KEY_NAME_SESSION_ID);
        if ($sessionId) {
            $this->currentSessionId = $sessionId;
        }
    }

    private function getCookieId(): ?int
    {
        return Filter::filterVar(
            $this->request->query->get(UserSession::COOKIE_NAME_SESSION_ID),
            FILTER_VALIDATE_INT
        );
    }

    private function countBots(): int
    {
        $bots = 0;
        foreach ($this->getBotIgnoreList() as $bot) {
            if (Strings::strstr($this->getRequestHeaders()->get('user-agent') ?? 1, $bot)) {
                ++$bots;
            }
        }

        return $bots;
    }

    public function getRemoteAddress(): string
    {
        $remoteAddress = $this->request->getClientIp();
        $localAddresses = ['127.0.0.1', '::1'];

        if (in_array($remoteAddress, $localAddresses) && $this->getRequestHeaders()->has('X-Forwarded-For')) {
            $remoteAddress = $this->getRequestHeaders()->get('X-Forwarded-For');
        }

        return preg_replace('([^0-9a-z:.]+)i', '', (string)$remoteAddress);
    }

    private function isBanned(string $remoteAddress): bool
    {
        $network = new Network($this->configuration);
        return $network->isBanned(IpUtils::anonymize($remoteAddress));
    }

    /**
     * @throws Exception
     */
    private function handleSession(?int $cookieId, string $remoteAddress, string $action, int|string|null $data): void
    {
        if ($this->currentSessionId === null) {
            $this->currentSessionId = $this->configuration->getDb()->nextId(
                Database::getTablePrefix() . 'faqsessions',
                'sid'
            );

            if (!is_null($cookieId) && (!$cookieId != $this->userSession->getCurrentSessionId())) {
                $this->userSession->setCookie(
                    UserSession::COOKIE_NAME_SESSION_ID,
                    $this->userSession->getCurrentSessionId()
                );
            }

            $query = sprintf(
                "INSERT INTO %sfaqsessions (sid, user_id, ip, time) VALUES (%d, %d, '%s', %d)",
                Database::getTablePrefix(),
                $this->userSession->getCurrentSessionId(),
                CurrentUser::getCurrentUser($this->configuration)->getUserId(),
                $remoteAddress,
                $this->request->server->get('REQUEST_TIME')
            );

            $this->configuration->getDb()->query($query);
        }

        $this->writeTrackingData($action, $data, $remoteAddress);
    }

    private function writeTrackingData(string $action, int|string|null $data, string $remoteAddress): void
    {
        $data = $this->userSession->getCurrentSessionId() . ';' .
            str_replace(';', ',', $action) . ';' .
            $data . ';' .
            $remoteAddress . ';' .
            str_replace(';', ',', $this->request->server->get('QUERY_STRING') ?? '') . ';' .
            str_replace(';', ',', $this->request->server->get('HTTP_REFERER') ?? '') . ';' .
            str_replace(';', ',', urldecode((string) $this->request->server->get('HTTP_USER_AGENT'))) . ';' .
            $this->request->server->get('REQUEST_TIME') . ";\n";

        $file = PMF_ROOT_DIR . '/content/core/data/tracking' . date('dmY');

        if (!is_file($file)) {
            touch($file);
        }

        if (!is_writable($file)) {
            $this->configuration->getLogger()->error('Cannot write to ' . $file);
        }

        file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
    }

    /**
     * Returns the botIgnoreList as an array.
     * @return array<string>
     */
    private function getBotIgnoreList(): array
    {
        return explode(',', (string) $this->configuration->get('main.botIgnoreList'));
    }

    private function getRequestHeaders(): HeaderBag
    {
        return $this->request->headers;
    }
}
