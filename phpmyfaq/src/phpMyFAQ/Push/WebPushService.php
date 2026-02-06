<?php

/**
 * Web Push notification service.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Push
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Push;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\PushSubscriptionEntity;

readonly class WebPushService
{
    public function __construct(
        private Configuration $configuration,
        private PushSubscriptionRepository $repository,
    ) {
    }

    /**
     * Checks if Web Push is enabled and configured.
     */
    public function isEnabled(): bool
    {
        $enableWebPush = $this->configuration->get('push.enableWebPush');
        // Handle null, string 'true', and boolean true
        $isEnabled = $enableWebPush !== null && ($enableWebPush === 'true' || $enableWebPush === true);

        $vapidPublicKey = $this->configuration->get('push.vapidPublicKey');
        $vapidPrivateKey = $this->configuration->get('push.vapidPrivateKey');

        return $isEnabled && !empty($vapidPublicKey) && !empty($vapidPrivateKey);
    }

    /**
     * Returns the VAPID public key.
     */
    public function getVapidPublicKey(): string
    {
        return $this->configuration->get('push.vapidPublicKey') ?? '';
    }

    /**
     * Generates a new VAPID key pair.
     *
     * @return array{publicKey: string, privateKey: string}
     */
    public static function generateVapidKeys(): array
    {
        return VAPID::createVapidKeys();
    }

    /**
     * Sends a push notification to all subscribers.
     */
    public function sendToAll(string $title, string $body, string $url = '', string $tag = ''): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $subscriptions = $this->repository->getAll();
        if ($subscriptions === []) {
            return;
        }

        $this->sendToSubscriptions($subscriptions, $title, $body, $url, $tag);
    }

    /**
     * Sends a push notification to a specific user.
     */
    public function sendToUser(int $userId, string $title, string $body, string $url = '', string $tag = ''): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $subscriptions = $this->repository->getByUserId($userId);
        if ($subscriptions === []) {
            return;
        }

        $this->sendToSubscriptions($subscriptions, $title, $body, $url, $tag);
    }

    /**
     * Sends a push notification to multiple specific users.
     *
     * @param int[] $userIds
     */
    public function sendToUsers(array $userIds, string $title, string $body, string $url = '', string $tag = ''): void
    {
        if (!$this->isEnabled() || $userIds === []) {
            return;
        }

        $subscriptions = $this->repository->getByUserIds($userIds);
        if ($subscriptions === []) {
            return;
        }

        $this->sendToSubscriptions($subscriptions, $title, $body, $url, $tag);
    }

    /**
     * @param PushSubscriptionEntity[] $subscriptions
     */
    private function sendToSubscriptions(
        array $subscriptions,
        string $title,
        string $body,
        string $url,
        string $tag,
    ): void {
        $auth = [
            'VAPID' => [
                'subject' =>
                    $this->configuration->get('push.vapidSubject') !== ''
                    && $this->configuration->get('push.vapidSubject') !== null
                        ? $this->configuration->get('push.vapidSubject')
                        : 'mailto:' . $this->configuration->getAdminEmail(),
                'publicKey' => $this->configuration->get('push.vapidPublicKey'),
                'privateKey' => $this->configuration->get('push.vapidPrivateKey'),
            ],
        ];

        try {
            $webPush = new WebPush($auth);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'tag' => $tag,
                'icon' => $this->configuration->getDefaultUrl() . 'assets/img/phpmyfaq.svg',
            ], JSON_THROW_ON_ERROR);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(Subscription::create([
                    'endpoint' => $subscription->getEndpoint(),
                    'publicKey' => $subscription->getPublicKey(),
                    'authToken' => $subscription->getAuthToken(),
                    'contentEncoding' => $subscription->getContentEncoding() ?? 'aesgcm',
                ]), $payload);
            }

            foreach ($webPush->flush() as $report) {
                if (!$report->isSubscriptionExpired()) {
                    continue;
                }

                $this->repository->deleteByEndpoint($report->getEndpoint());
            }
        } catch (\Throwable $exception) {
            $this->configuration->getLogger()->error('Web Push notification failed: ' . $exception->getMessage());
        }
    }
}
