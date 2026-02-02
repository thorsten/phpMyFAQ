<?php

/**
 * The Push Notification Controller for the Frontend API.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Entity\PushSubscriptionEntity;
use phpMyFAQ\Filter;
use phpMyFAQ\Push\PushSubscriptionRepository;
use phpMyFAQ\Push\WebPushService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PushController extends AbstractController
{
    /**
     * Returns the VAPID public key and whether push is enabled.
     */
    #[Route(path: 'push/vapid-public-key', name: 'api.private.push.vapid-public-key', methods: ['GET'])]
    public function getVapidPublicKey(): JsonResponse
    {
        /** @var WebPushService $webPushService */
        $webPushService = $this->container->get('phpmyfaq.push.web-push-service');

        return $this->json([
            'enabled' => $webPushService->isEnabled(),
            'vapidPublicKey' => $webPushService->getVapidPublicKey(),
        ], Response::HTTP_OK);
    }

    /**
     * Subscribes the current user to push notifications.
     *
     * @throws \JsonException
     */
    #[Route(path: 'push/subscribe', name: 'api.private.push.subscribe', methods: ['POST'])]
    public function subscribe(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        $endpoint = Filter::filterVar($data->endpoint ?? '', FILTER_SANITIZE_URL);
        $publicKey = Filter::filterVar($data->publicKey ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $authToken = Filter::filterVar($data->authToken ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $contentEncoding = Filter::filterVar($data->contentEncoding ?? 'aesgcm', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($endpoint === '' || $publicKey === '' || $authToken === '') {
            return $this->json(['error' => 'Missing required subscription data'], Response::HTTP_BAD_REQUEST);
        }

        $entity = new PushSubscriptionEntity();
        $entity
            ->setUserId($this->currentUser->getUserId())
            ->setEndpoint($endpoint)
            ->setEndpointHash(hash('sha256', $endpoint))
            ->setPublicKey($publicKey)
            ->setAuthToken($authToken)
            ->setContentEncoding($contentEncoding);

        /** @var PushSubscriptionRepository $repository */
        $repository = $this->container->get('phpmyfaq.push.subscription-repository');

        if ($repository->save($entity)) {
            return $this->json(['success' => true], Response::HTTP_CREATED);
        }

        return $this->json(['error' => 'Failed to save subscription'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Unsubscribes the current user from push notifications.
     *
     * @throws \JsonException
     */
    #[Route(path: 'push/unsubscribe', name: 'api.private.push.unsubscribe', methods: ['POST'])]
    public function unsubscribe(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        $endpoint = Filter::filterVar($data->endpoint ?? '', FILTER_SANITIZE_URL);

        if ($endpoint === '') {
            return $this->json(['error' => 'Missing endpoint'], Response::HTTP_BAD_REQUEST);
        }

        /** @var PushSubscriptionRepository $repository */
        $repository = $this->container->get('phpmyfaq.push.subscription-repository');
        $endpointHash = hash('sha256', $endpoint);

        if ($repository->deleteByEndpointHash($endpointHash)) {
            return $this->json(['success' => true], Response::HTTP_OK);
        }

        return $this->json(['error' => 'Failed to remove subscription'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Returns the subscription status for the current user.
     */
    #[Route(path: 'push/status', name: 'api.private.push.status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $this->userIsAuthenticated();

        /** @var PushSubscriptionRepository $repository */
        $repository = $this->container->get('phpmyfaq.push.subscription-repository');

        return $this->json([
            'subscribed' => $repository->hasSubscription($this->currentUser->getUserId()),
        ], Response::HTTP_OK);
    }
}
