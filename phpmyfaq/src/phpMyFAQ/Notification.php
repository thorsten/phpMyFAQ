<?php

/**
 * The notification class for phpMyFAQ.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2023 phpMyFAQ Team
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

    /**
     * Constructor.
     *
     * @throws Core\Exception
     */
    public function __construct(private readonly Configuration $config)
    {
        $this->mail = new Mail($this->config);
        $this->mail->setReplyTo(
            $this->config->getAdminEmail(),
            $this->config->getTitle()
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
        if ($this->config->get('main.enableNotifications')) {
            $this->mail->addTo($email, $userName);
            $this->mail->subject = $this->config->getTitle() . ' - ' . Translation::get('msgQuestionAnswered');
            $this->mail->message = sprintf(
                Translation::get('msgMessageQuestionAnswered'),
                $this->config->getTitle()
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
        if ($this->config->get('main.enableNotifications')) {
            $this->mail->addTo($this->config->getAdminEmail());
            foreach ($emails as $email) {
                $this->mail->addCc($email);
            }
            $this->mail->subject = $this->config->getTitle() . ': New FAQ was added.';
            $this->mail->message = html_entity_decode(
                Translation::get('msgMailCheck')
            ) . "\n\n" . $this->config->getTitle() . ': ' . $this->config->getDefaultUrl(
            ) . 'admin/?action=editentry&id=' . $faqId . '&lang=' . $faqLanguage;

            $this->mail->send();
        }
    }
}
