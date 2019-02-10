<?php

/**
 * The notification class for phpMyFAQ.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2012-08-30
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Notification.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2012-08-30
 */
class PMF_Notification
{
    /**
     * @var PMF_Configuration
     */
    private $config;

    /**
     * Mail object.
     *
     * @var PMF_Mail
     */
    private $mail;

    /**
     * Language strings.
     *
     * @var string
     */
    private $pmfStr;

    /**
     * Constructor.
     *
     * @param PMF_Configuration
     *
     * @return PMF_Notification
     */
    public function __construct(PMF_Configuration $config)
    {
        global $PMF_LANG;

        $this->config = $config;
        $this->pmfStr = $PMF_LANG;
        $this->mail = new PMF_Mail($this->config);
        $this->mail->setReplyTo(
            $this->config->get('main.administrationMail'),
            $this->config->get('main.titleFAQ')
        );
    }

    /**
     * Sends a notification to user who added a question.
     *
     * @param string $email    Email address of the user
     * @param string $userName Name of the user
     * @param string $url      URL of answered FAQ
     */
    public function sendOpenQuestionAnswered($email, $userName, $url)
    {
        $this->mail->addTo($email, $userName);
        $this->mail->subject = $this->config->get('main.titleFAQ').' - '.$this->pmfStr['msgQuestionAnswered'];
        $this->mail->message = sprintf(
            $this->pmfStr['msgMessageQuestionAnswered'],
            $this->config->get('main.titleFAQ')
        )."\n\r".$url;
        $this->mail->send();
    }
}
