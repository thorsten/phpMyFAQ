<?php

/**
 * The Push Notification Controller for the Admin API.
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

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Push\WebPushService;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PushController extends AbstractAdministrationApiController
{
    /**
     * Generates a new VAPID key pair and saves it to configuration.
     */
    #[Route(path: 'push/generate-vapid-keys', name: 'admin.api.push.generate-vapid-keys', methods: ['POST'])]
    public function generateVapidKeys(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken('pmf-csrf-token', $data->csrf ?? '')) {
            return $this->json([
                'success' => false,
                'error' => Translation::get('msgNoPermission'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $keys = WebPushService::generateVapidKeys();

            $this->configuration->update(['push.vapidPublicKey' => $keys['publicKey']]);
            $this->configuration->update(['push.vapidPrivateKey' => $keys['privateKey']]);

            if (($this->configuration->get('push.vapidSubject') ?? '') === '') {
                $this->configuration->update([
                    'push.vapidSubject' => 'mailto:' . $this->configuration->getAdminEmail(),
                ]);
            }

            return $this->json([
                'success' => true,
                'publicKey' => $keys['publicKey'],
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
