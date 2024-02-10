<?php

/**
 * Questions helper class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-26
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Mail;
use phpMyFAQ\Question;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Class QuestionHelper
 * @package phpMyFAQ\Helper
 */
class QuestionHelper
{
    /**
     * QuestionHelper constructor.
     */
    public function __construct(private readonly Configuration $configuration, private readonly Category $category)
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function sendSuccessMail(array $questionData, array $categories): void
    {
        $questionMail = Translation::get('msgNewQuestionAdded') . "\n\n User: " .
            $questionData['username'] .
            ', ' . $questionData['email'] . "\n" . Translation::get('msgCategory') .
            ': ' . $categories[$questionData['category_id']]['name'] . "\n\n" .
            Translation::get('msgAskYourQuestion') . ': ' .
            wordwrap((string) $questionData['question'], 72) . "\n\n" .
            $this->configuration->getDefaultUrl() . 'admin/';

        $userId = $this->category->getOwner($questionData['category_id']);
        $oUser = new User($this->configuration);
        $oUser->getUserById($userId);

        $userEmail = $oUser->getUserData('email');
        $mainAdminEmail = $this->configuration->getAdminEmail();

        $mail = new Mail($this->configuration);
        $mail->setReplyTo($questionData['email'], $questionData['username']);
        $mail->addTo($mainAdminEmail);
        // Let the category owner get a copy of the message
        if (!empty($userEmail) && $mainAdminEmail != $userEmail) {
            $mail->addCc($userEmail);
        }

        $mail->subject = $this->configuration->getTitle() . ': New Question was added.';
        $mail->message = $questionMail;
        $mail->send();
        unset($mail);
    }
}
