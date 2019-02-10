<?php

/**
 * MUA (Mail User Agent) interface.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-11
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Mail_IMUA.
 *
 * @category  phpMyFAQ
 *
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-11
 */
interface PMF_Mail_IMUA
{
    /**
     * Send the message using an e-mail.
     *
     * @param string $recipients Recipients of the e-mail as a comma-separated list
     *                           of RFC 2822 compliant elements
     * @param array  $headers    Headers of the e-mail
     * @param string $body       Body of the e-mail
     *
     * @return bool True if successful, false otherwise.     
     */
    public function send($recipients, Array $headers, $body);
}
