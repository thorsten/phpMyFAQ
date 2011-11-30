<?php
/**
 * MUA (Mail User Agent) implementation using the PHP built-in mail() function.
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Mail
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-11
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Mail_Builtin
 *
 * @category  phpMyFAQ
 * @package   PMF_Mail
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-11
  */ 
class PMF_Mail_Builtin implements PMF_Mail_IMUA
{
    /**
     * Send the message using an e-mail through the PHP built-in mail() function.
     *
     * @param  string $recipients Recipients of the e-mail as a comma-separated list
     *                            of RFC 2822 compliant items
     * @param  array  $headers    Headers of the e-mail
     * @param  string $body       Body of the e-mail
     *
     * @return bool   True if successful, false otherwise.     
     */
    public function send($recipients, Array $headers, $body)
    {
        // Get the subject of the e-mail, RFC 2047 compliant
        $subject            = $headers['Subject'];
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
            $mailHeaders .= $key . ': ' . $value . PHP_EOL;
        }

        // Send the e-mail
        if (empty($sender)) {
            return mail($recipients, $subject, $body, $mailHeaders);
        } else {
            return mail($recipients, $subject, $body, $mailHeaders, '-f' . $sender);
        }
    }
}
