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
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use JsonException;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\RegistrationHelper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    /**
     * @throws JsonException|Exception
     */
    #[Route(path: 'register', name: 'api.private.register', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $registrationHelper = new RegistrationHelper($this->configuration);

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!isset($data->realname)) {
            throw new Exception('Missing realname');
        }

        if (!isset($data->name)) {
            throw new Exception('Missing username');
        }

        if (!isset($data->email) || empty($data->email)) {
            throw new Exception('Missing or empty email');
        }

        if (isset($data->isVisible)) {
            throw new Exception('isVisible parameter not allowed');
        }

        $fullName = trim(strip_tags((string) $data->realname));
        $userName = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL));

        if (!$email) {
            throw new Exception('Invalid email address');
        }

        $isVisible = (bool) Filter::filterVar($data->isVisible ?? false, FILTER_SANITIZE_SPECIAL_CHARS) ?? false;

        if (!$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get(key: 'msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        if (!$registrationHelper->isDomainAllowed($email)) {
            return $this->json(['error' => 'The domain is not allowed.'], Response::HTTP_BAD_REQUEST);
        }

        if (
            $userName !== '' && $userName !== '0' && $email !== '' && $email !== '0' && (
                $fullName !== ''
                && $fullName !== '0'
            )
        ) {
            try {
                return $this->json(
                    $registrationHelper->createUser($userName, $fullName, $email, $isVisible),
                    Response::HTTP_CREATED,
                );
            } catch (Exception|TransportExceptionInterface $exception) {
                return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->json(['error' => Translation::get(key: 'err_sendMail')], Response::HTTP_BAD_REQUEST);
    }
}
