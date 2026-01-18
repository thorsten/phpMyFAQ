<?php

/**
 * The Contact Controller
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
 * @since     2024-03-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     *
     */
    #[Route(path: 'api/contact', name: 'api.private.contact', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());

        if (!$data) {
            throw new Exception('Invalid JSON data');
        }

        if (!isset($data->name)) {
            throw new Exception('Missing name');
        }

        if (!isset($data->question)) {
            throw new Exception('Missing question');
        }

        $author = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $question = trim((string) Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS));

        if (!$email) {
            throw new Exception('Invalid email address');
        }

        if ($question === '' || $question === '0') {
            throw new Exception('Empty question');
        }

        if (!$this->captchaCodeIsValid($request)) {
            throw new Exception('Invalid captcha');
        }

        $stopWords = $this->container->get(id: 'phpmyfaq.stop-words');

        if ($author !== '' && $author !== '0' && $email !== '' && $stopWords->checkBannedWord($question)) {
            $question = sprintf(
                '%s: %s<br>%s: %s<br><br>%s',
                Translation::get(key: 'msgNewContentName'),
                $author,
                Translation::get(key: 'msgNewContentMail'),
                $email,
                $question,
            );

            $mailer = $this->container->get(id: 'phpmyfaq.mail');
            try {
                $mailer->setReplyTo($email, $author);
                $mailer->addTo($this->configuration->getAdminEmail());
                $mailer->setReplyTo($this->configuration->getNoReplyEmail());
                $mailer->subject = Utils::resolveMarkers(
                    text: 'Feedback: %sitename%',
                    configuration: $this->configuration,
                );
                $mailer->message = $question;
                $mailer->send();
                unset($mailer);

                return $this->json(['success' => Translation::get(key: 'msgMailContact')], Response::HTTP_OK);
            } catch (Exception|TransportExceptionInterface $e) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->json(['error' => Translation::get(key: 'err_sendMail')], Response::HTTP_BAD_REQUEST);
    }
}
