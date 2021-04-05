<?php

/**
 * The notification class for phpMyFAQ.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-08-30
 */

namespace phpMyFAQ;

/**
 * Class Notification
 *
 * @package phpMyFAQ
 */
class Notification
{
    /** @var Configuration */
    private $config;

    /** @var Mail */
    private $mail;

    /** @var array<string> */
    private $translation;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        global $PMF_LANG;

        $this->config = $config;
        $this->translation = $PMF_LANG;
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
     */
    public function sendOpenQuestionAnswered(string $email, string $userName, string $url): void
    {
        $this->mail->addTo($email, $userName);
        $this->mail->subject = $this->config->getTitle() . ' - ' . $this->translation['msgQuestionAnswered'];
        $this->mail->message = sprintf(
            $this->translation['msgMessageQuestionAnswered'],
            $this->config->getTitle()
        ) . "\n\r" . $url;
        $this->mail->send();
    }

    /**
     * Sends mails to FAQ admin and other given users about a newly added FAQ.
     *
     * @param array<string> $emails
     * @param int $faqId
     * @param string $faqLanguage
     */
    public function sendNewFaqAdded(array $emails, int $faqId, string $faqLanguage): void
    {
        $this->mail->addTo($this->config->getAdminEmail());
        foreach ($emails as $email) {
            $this->mail->addCc($email);
        }
        $this->mail->subject = $this->config->getTitle() . ': New FAQ was added.';
        $this->mail->message = html_entity_decode(
            $this->translation['msgMailCheck']
        ) . "\n\n" . $this->config->getTitle() . ': ' . $this->config->getDefaultUrl(
        ) . 'admin/?action=editentry&id=' . $faqId . '&lang=' . $faqLanguage;
        $this->mail->send();
    }
}
