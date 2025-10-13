<?php

declare(strict_types=1);

/**
 * The User Controller for unauthorized users
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-07-08
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Mail;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UnauthorizedUserController
{
    protected ?Configuration $configuration = null;

    /**
     * Check if the FAQ should be secured.
     */
    public function __construct()
    {
        $this->configuration = Configuration::getConfigurationInstance();
    }

    /**
     * @throws Exception
     */
    #[Route('api/user/password/update', methods: ['PUT'])]
    public function updatePassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());

        $username = trim((string) Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL));

        if ($username !== '' && $username !== '0' && ($email !== '' && $email !== '0')) {
            $user = CurrentUser::getCurrentUser($this->configuration);
            $loginExist = $user->getUserByLogin($username);

            if ($loginExist && $email == $user->getUserData('email')) {
                try {
                    $newPassword = $user->createPassword();
                } catch (\Exception $exception) {
                    return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
                }

                try {
                    $user->changePassword($newPassword);
                } catch (\Exception $exception) {
                    return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
                }

                $text =
                    Translation::get('lostpwd_text_1')
                    . sprintf('<br><br>Username: %s', $username)
                    . sprintf('<br>New Password: %s<br><br>', $newPassword)
                    . Translation::get('lostpwd_text_2');

                $mail = new Mail($this->configuration);
                try {
                    $mail->addTo($email);
                } catch (Exception $exception) {
                    return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
                }

                $mail->subject = Utils::resolveMarkers(
                    '[%sitename%] Username / password request',
                    $this->configuration,
                );
                $mail->message = $text;
                try {
                    $mail->send();
                } catch (Exception|TransportExceptionInterface $exception) {
                    return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
                }

                unset($mail);
                // Trust that the email has been sent
                return $this->json(['success' => Translation::get('lostpwd_mail_okay')], Response::HTTP_OK);
            }

            return $this->json(['error' => Translation::get('lostpwd_err_1')], Response::HTTP_CONFLICT);
        }

        return $this->json(['error' => Translation::get('lostpwd_err_2')], Response::HTTP_CONFLICT);
    }

    /**
     * Returns a JsonResponse that uses json_encode().
     *
     * @param string[] $headers
     */
    public function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}
