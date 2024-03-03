<?php

/**
 * The notification class for phpMyFAQ.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-08-30
 */

namespace phpMyFAQ;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Class Notification
 *
 * @package phpMyFAQ
 */
class Notification
{
    private readonly Mail $mail;

    private readonly Faq $faq;

    /**
     * Constructor.
     *
     * @throws Core\Exception
     */
    public function __construct(private readonly Configuration $configuration)
    {
        $this->mail = new Mail($this->configuration);
        $this->faq = new Faq($this->configuration);
        $this->mail->setReplyTo(
            $this->configuration->getAdminEmail(),
            $this->configuration->getTitle()
        );
    }

    /**
     * Sends a mail to user who added a question.
     *
     * @param string $email Email address of the user
     * @param string $userName Name of the user
     * @param string $url URL of answered FAQ
     * @throws Core\Exception|TransportExceptionInterface
     */
    public function sendOpenQuestionAnswered(string $email, string $userName, string $url): void
    {
        if ($this->configuration->get('main.enableNotifications')) {
            $this->mail->addTo($email, $userName);
            $this->mail->subject = $this->configuration->getTitle() . ' - ' . Translation::get('msgQuestionAnswered');
            $this->mail->message = sprintf(
                Translation::get('msgMessageQuestionAnswered'),
                $this->configuration->getTitle()
            ) . "\n\r" . $url;
            $this->mail->send();
        }
    }

    /**
     * Sends mails to FAQ admin and other given users about a newly added FAQ.
     *
     * @param array<string> $emails
     * @throws Core\Exception|TransportExceptionInterface
     */
    public function sendNewFaqAdded(array $emails, int $faqId, string $faqLanguage): void
    {
        if ($this->configuration->get('main.enableNotifications')) {
            $this->mail->addTo($this->configuration->getAdminEmail());
            foreach ($emails as $email) {
                if ($email !== $this->config->getAdminEmail()) {
                    $this->mail->addCc($email);
                }
            }

            $this->mail->subject = $this->configuration->getTitle() . ': New FAQ was added.';
            $this->faq->getRecord($faqId, null, true);

            $url = sprintf(
                '%sadmin/?action=editentry&id=%d&lang=%s',
                $this->configuration->getDefaultUrl(),
                $faqId,
                $faqLanguage
            );
            $link = new Link($url, $this->configuration);
            $link->itemTitle = $this->faq->getRecordTitle($faqId);

            $this->mail->message = html_entity_decode((string) Translation::get('msgMailCheck')) .
                "<p><strong>" . Translation::get('msgAskYourQuestion') . ":</strong> " .
                $this->faq->getRecordTitle($faqId) . "</p>" .
                "<p><strong>" . Translation::get('msgNewContentArticle') . ":</strong> " .
                $this->faq->faqRecord['content'] . "</p>" .
                "<hr>" .
                $this->configuration->getTitle() .
                ': <a target="_blank" href="' . $link->toString() . '">' . $link->toString() . '</a>';

            $this->mail->contentType = 'text/html';

            $this->mail->send();
        }
    }
}
