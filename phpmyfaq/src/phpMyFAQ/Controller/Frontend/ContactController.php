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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-09
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Mail;
use phpMyFAQ\StopWords;
use phpMyFAQ\Translation;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('api/contact', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $configuration = Configuration::getConfigurationInstance();
        $stopWords = new StopWords($configuration);

        $data = json_decode($request->getContent());

        $author = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $question = trim((string) Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS));

        // If e-mail address is set to optional
        if (!$configuration->get('main.optionalMailAddress') && is_null($email)) {
            $email = $configuration->getAdminEmail();
        }

        if (
            $author !== '' &&
            $author !== '0' &&
            !empty($email) &&
            ($question !== '' &&
            $question !== '0') &&
            $stopWords->checkBannedWord($question)
        ) {
            $question = sprintf(
                "%s: %s<br>%s: %s<br><br>%s",
                Translation::get('msgNewContentName'),
                $author,
                Translation::get('msgNewContentMail'),
                $email,
                $question
            );

            $mailer = new Mail($configuration);
            try {
                $mailer->setReplyTo($email, $author);
                $mailer->addTo($configuration->getAdminEmail());
                $mailer->subject = Utils::resolveMarkers('Feedback: %sitename%', $configuration);
                $mailer->message = $question;
                $mailer->send();
                unset($mailer);

                return $this->json(['success' => Translation::get('msgMailContact')], Response::HTTP_OK);
            } catch (Exception | TransportExceptionInterface $e) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json(['error' => Translation::get('err_sendMail')], Response::HTTP_BAD_REQUEST);
        }
    }
}
