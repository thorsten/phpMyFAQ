<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\SessionActionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Network;
use phpMyFAQ\Strings;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

class Tracking
{
    private static ?Tracking $instance = null;

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
        if (null === self::$instance) {
            self::$instance = new self($configuration, $request, $userSession);
        }

        return self::$instance;
    }

    /**
     * @throws Exception
     */
    public function log(string $action, int|string|null $data = null): void
    {
        if (!$this->configuration->get('main.enableUserTracking')) {
            return;
        }

        $bots = 0;
        $banned = false;
        $this->currentSessionId = Filter::filterVar(
            $this->request->query->get(UserSession::KEY_NAME_SESSION_ID),
            FILTER_VALIDATE_INT
        );
        $cookieId = Filter::filterVar(
            $this->request->query->get(UserSession::COOKIE_NAME_SESSION_ID),
            FILTER_VALIDATE_INT
        );

        if (!is_null($cookieId)) {
            $this->userSession->setCurrentSessionId($cookieId);
        }

        if ($action === SessionActionType::OLD_SESSION->value) {
            $this->userSession->setCurrentSessionId(0);
        }

        foreach ($this->getBotIgnoreList() as $bot) {
            if (Strings::strstr($this->request->headers->get('user-agent'), $bot)) {
                ++$bots;
            }
        }

        // if we're running behind a reverse proxy like nginx/varnish, fix the client IP
        $remoteAddress = $this->request->getClientIp();
        $localAddresses = ['127.0.0.1', '::1'];

        if (in_array($remoteAddress, $localAddresses) && $this->request->headers->has('X-Forwarded-For')) {
            $remoteAddress = $this->request->headers->get('X-Forwarded-For');
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
    }

    /**
     * Returns the botIgnoreList as an array.
     * @return array<string>
     */
    private function getBotIgnoreList(): array
    {
        return explode(',', (string) $this->configuration->get('main.botIgnoreList'));
    }
}
