<?php

/**
 * Questions helper class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-26
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Mail;
use phpMyFAQ\Question;
use phpMyFAQ\User;

/**
 * Class QuestionHelper
 * @package phpMyFAQ\Helper
 */
class QuestionHelper
{
    /** @var Configuration */
    private Configuration $config;

    /** @var Category */
    private Category $category;

    /** @var array */
    private array $translation;

    /**
     * QuestionHelper constructor.
     * @param Configuration $config
     * @param Category $category
     */
    public function __construct(Configuration $config, Category $category)
    {
        global $PMF_LANG;
        $this->config = $config;
        $this->category = $category;
        $this->translation = $PMF_LANG;
    }

    /**
     * @param array $questionData
     * @param array $categories
     * @throws Exception
     */
    public function sendSuccessMail(array $questionData, array $categories): void
    {
        $questionObject = new Question($this->config);
        $questionObject->addQuestion($questionData);

        $questionMail = 'User: ' . $questionData['username'] .
            ', mailto:' . $questionData['email'] . "\n" . $this->translation['msgCategory'] .
            ': ' . $categories[$questionData['category_id']]['name'] . "\n\n" .
            wordwrap($questionData['question'], 72) . "\n\n" .
            $this->config->getDefaultUrl() . 'admin/';

        $userId = $this->category->getOwner($questionData['category_id']);
        $oUser = new User($this->config);
        $oUser->getUserById($userId);

        $userEmail = $oUser->getUserData('email');
        $mainAdminEmail = $this->config->getAdminEmail();

        $mailer = new Mail($this->config);
        $mailer->setReplyTo($questionData['email'], $questionData['username']);
        $mailer->addTo($mainAdminEmail);
        // Let the category owner get a copy of the message
        if (!empty($userEmail) && $mainAdminEmail != $userEmail) {
            $mailer->addCc($userEmail);
        }
        $mailer->subject = $this->config->getTitle() . ': New Question was added.';
        $mailer->message = $questionMail;
        $mailer->send();
        unset($mailer);
    }
}
