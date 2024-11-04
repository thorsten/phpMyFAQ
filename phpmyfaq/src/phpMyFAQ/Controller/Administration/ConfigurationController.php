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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-26
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Mail;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('admin/api/configuration/send-test-mail')]
    public function sendTestMail(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->container->get('session'))->verifyToken('configuration', $data->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $mail = new Mail($this->configuration);
            $mail->addTo($this->configuration->getAdminEmail());
            $mail->setReplyTo($this->configuration->getNoReplyEmail());
            $mail->subject = $this->configuration->getTitle() . ': Mail test successful.';
            $mail->message = 'It works on my machine. 🚀';
            $result = $mail->send();

            return $this->json(['success' => $result], Response::HTTP_OK);
        } catch (Exception | TransportExceptionInterface $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
