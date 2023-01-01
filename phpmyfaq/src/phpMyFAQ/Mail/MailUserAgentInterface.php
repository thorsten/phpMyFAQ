<?php

/**
 * MUA (Mail User Agent) interface.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-11
 */

namespace phpMyFAQ\Mail;

/**
 * Interface MailUserAgentInterface
 *
 * @package phpMyFAQ\Mail
 */
interface MailUserAgentInterface
{
    /**
     * Send the message using an e-mail.
     *
     * @param string $recipients Recipients of the e-mail as a comma-separated list of RFC 2822 compliant elements
     * @param array<string> $headers Headers of the e-mail
     * @param string $body Body of the e-mail
     * @return int The number of successful recipients. Can be 0 which indicates failure
     */
    public function send(string $recipients, array $headers, string $body): int;
}
