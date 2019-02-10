<?php

/**
 * MUA (Mail User Agent) implementation using the PHP built-in mail() function.
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
 * PMF_Mail_Builtin.
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
class PMF_Mail_Builtin implements PMF_Mail_IMUA
{
    /**
     * Send the message using an e-mail through the PHP built-in mail() function.
     *
     * @param string $recipients Recipients of the e-mail as a comma-separated list
     *                           of RFC 2822 compliant items
     * @param array  $headers    Headers of the e-mail
     * @param string $body       Body of the e-mail
     *
     * @return bool True if successful, false otherwise.     
     */
    public function send($recipients, Array $headers, $body)
    {
        // Get the subject of the e-mail, RFC 2047 compliant
        $subject = $headers['Subject'];
        $headers['Subject'] = null;
        unset($headers['Subject']);

        $sender = '';
        if (('WIN' !== strtoupper(substr(PHP_OS, 0, 3))) && !ini_get('safe_mode')) {
            $sender = str_replace(
                array('<', '>'),
                '',
                $headers['Return-Path']
            );
            unset($headers['Return-Path']);
        }

        // Prepare the headers for the e-mail
        $mailHeaders = '';
        foreach ($headers as $key => $value) {
            $mailHeaders .= $key.': '.$value.PHP_EOL;
        }

        // Send the e-mail
        if (empty($sender)) {
            return mail($recipients, $subject, $body, $mailHeaders);
        } else {
            return mail($recipients, $subject, $body, $mailHeaders, '-f'.$sender);
        }
    }
}
