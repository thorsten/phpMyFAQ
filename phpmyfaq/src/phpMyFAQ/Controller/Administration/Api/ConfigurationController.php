<?php

/**
 * The Admin Configuration Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-26
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

final class ConfigurationController extends AbstractController
{
    /**
     * @throws Exception|\Exception
     */
    #[Route(
        'admin/api/configuration/send-test-mail',
        name: 'admin.api.configuration.send-test-mail',
        methods: ['POST'],
    )]
    public function sendTestMail(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken('configuration', $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $mail = $this->container->get(id: 'phpmyfaq.mail');
            $mail->addTo($this->configuration->getAdminEmail());
            $mail->setReplyTo($this->configuration->getNoReplyEmail());
            $mail->subject = $this->configuration->getTitle() . ': Mail test successful.';
            $mail->message = 'It works on my machine. 🚀';
            $result = $mail->send();

            return $this->json(['success' => $result], Response::HTTP_OK);
        } catch (Exception|TransportExceptionInterface $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route(
        'admin/api/configuration/activate-maintenance-mode',
        name: 'admin.api.configuration.activate-maintenance-mode',
        methods: ['POST'],
    )]
    public function activateMaintenanceMode(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken('activate-maintenance-mode', $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $this->configuration->set('main.maintenanceMode', 'true');

        return $this->json(['success' => Translation::get(key: 'healthCheckOkay')], Response::HTTP_OK);
    }
}
