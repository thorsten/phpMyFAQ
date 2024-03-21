<?php

/**
 * The Registration Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-03
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\RegistrationHelper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegistrationController extends AbstractController
{
    /**
     * @throws \JsonException
     * @throws Exception
     */
    public function create(Request $request): JsonResponse
    {
        $registration = new RegistrationHelper($this->configuration);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $fullName = trim((string) Filter::filterVar($data->realname, FILTER_SANITIZE_SPECIAL_CHARS));
        $userName = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL));
        $isVisible = Filter::filterVar($data->isVisible, FILTER_SANITIZE_SPECIAL_CHARS) ?? false;

        if (!$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get('msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        if (!$registration->isDomainAllowed($email)) {
            return $this->json(['error' => 'The domain is not allowed.'], Response::HTTP_BAD_REQUEST);
        }

        if (!empty($userName) && !empty($email) && !empty($fullName)) {
            try {
                return $this->json(
                    $registration->createUser($userName, $fullName, $email, $isVisible),
                    Response::HTTP_CREATED
                );
            } catch (Exception | TransportExceptionInterface $exception) {
                return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json(['error' => Translation::get('err_sendMail')], Response::HTTP_BAD_REQUEST);
        }
    }
}
