<?php
/**
 * The notification class for phpMyFAQ
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Notification
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-08-30
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Notification
 *
 * @category  phpMyFAQ
 * @package   Notification
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-08-30
 */

class PMF_Notification
{
    /**
     * @var PMF_Configuration
     */
    private $config;

    /**
     * Mail object
     *
     * @var PMF_Mail
     */
    private $mail;

    /**
     * Constructor
     *
     * @param PMF_Configuration
     *
     * @return PMF_Notification
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Sets the Mail class
     *
     * @param \PMF_Mail $mail
     *
     * @return PMF_Notification
     */
    public function setMail($mail)
    {
        $this->mail = $mail;

        return $this;
    }

    /**
     * Returns the mail class
     *
     * @return \PMF_Mail
     */
    public function getMail()
    {
        return $this->mail;
    }
}