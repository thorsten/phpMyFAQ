<?php

/**
 * MUA (Mail User Agent) implementation using the PHP built-in mail() function.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-11
 */

namespace phpMyFAQ\Mail;

/**
 * Class Builtin
 *
 * @package phpMyFAQ\Mail
 */
class Builtin implements MailUserAgentInterface
{
    /**
     * Send the message using an email through the PHP built-in mail() function.
     *
     * @param string $recipients Recipients of the e-mail as a comma-separated list of RFC 2822 compliant items
     * @param array<string> $headers Headers of the e-mail
     * @param string $body Body of the e-mail
     * @return int
     */
    public function send(string $recipients, array $headers, string $body): int
    {
        // Get the subject of the e-mail, RFC 2047 compliant
        $subject = $headers['Subject'];
        $headers['Subject'] = null;
        unset($headers['Subject']);

        $sender = '';
        if (('WIN' !== strtoupper(substr(PHP_OS, 0, 3))) && !ini_get('safe_mode')) {
            $sender = str_replace(
                ['<', '>'],
                '',
                $headers['Return-Path']
            );
            unset($headers['Return-Path']);
        }

        // Prepare the headers for the email
        $mailHeaders = '';
        foreach ($headers as $key => $value) {
            $mailHeaders .= $key . ': ' . $value . PHP_EOL;
        }

        // Send the email
        if (empty($sender)) {
            return (int)mail($recipients, $subject, $body, $mailHeaders);
        } else {
            return (int)mail($recipients, $subject, $body, $mailHeaders, '-f' . $sender);
        }
    }
}
