<?php

/**
 * Service class for Twitter support.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-09-05
 */

namespace phpMyFAQ\Services;

use Abraham\TwitterOAuth\TwitterOAuth;
use phpMyFAQ\Strings;

/**
 * Class Twitter
 *
 * @package phpMyFAQ\Services
 */
class Twitter
{
    /**
     * Constructor.
     */
    public function __construct(protected TwitterOAuth $connection)
    {
    }

    /**
     * Adds a post to Twitter.
     *
     * @param string $question Question
     * @param string $tags     String of tags
     * @param string $link     URL to FAQ
     */
    public function addPost(string $question, string $tags, string $link): void
    {
        $hashtags = '';

        if ($tags != '') {
            $hashtags = '#' . str_replace(',', ' #', $tags);
        }

        $message = Strings::htmlspecialchars($question);
        $message .= ' ' . $hashtags;
        $message .= ' ' . $link;

        $this->connection->post('statuses/update', ['status' => $message]);
    }
}
