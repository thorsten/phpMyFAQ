<?php

/**
 * The Share with Friends Controller
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
 * @since     2024-03-12
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Mail;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ShareController extends AbstractController
{
    /**
     * @throws \JsonException
     */
    public function create(Request $request): JsonResponse
    {
        $stopWords = new StopWords($this->configuration);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $author = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $attached = trim((string) Filter::filterVar($data->message, FILTER_SANITIZE_SPECIAL_CHARS));
        $mailto = Filter::filterArray($data->{'mailto[]'});
        $faqLanguage = trim((string) Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS));
        $faqId = trim((string) Filter::filterVar($data->faqId, FILTER_VALIDATE_INT));
        $categoryId = trim((string) Filter::filterVar($data->categoryId, FILTER_VALIDATE_INT));

        if (is_array($mailto) && count($mailto) > 5) {
            return $this->json(['error' => Translation::get('err_sendMail')], Response::HTTP_BAD_REQUEST);
        }

        if (
            !empty($author) && !empty($email) && is_array($mailto) &&
            $stopWords->checkBannedWord(Strings::htmlspecialchars($attached))
        ) {
            $send2friendLink = sprintf(
                '%sindex.php?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                $this->configuration->getDefaultUrl(),
                $categoryId,
                $faqId,
                urlencode($faqLanguage)
            );

            foreach ($mailto as $recipient) {
                $recipient = trim(strip_tags((string) $recipient));
                if ($recipient !== '' && $recipient !== '0') {
                    $mailer = new Mail($this->configuration);
                    try {
                        $mailer->setReplyTo($email, $author);
                        $mailer->addTo($recipient);
                    } catch (Exception $exception) {
                        return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
                    }

                    $mailer->subject = Translation::get('msgS2FMailSubject') . $author;
                    $mailer->message = sprintf(
                        "%s\r\n\r\n%s\r\n%s\r\n\r\n%s",
                        $this->configuration->get('main.send2friendText'),
                        Translation::get('msgS2FText2'),
                        $send2friendLink,
                        strip_tags($attached)
                    );

                    // Send the email
                    try {
                        $mailer->send();
                    } catch (TransportExceptionInterface | Exception $exception) {
                        return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
                    }
                    unset($mailer);
                }
            }

            return $this->json(['success' => Translation::get('msgS2FThx')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('err_sendMail')], Response::HTTP_BAD_REQUEST);
        }
    }
}
