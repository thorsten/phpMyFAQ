<?php

/**
 * Service class for Twitter support.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     * @var TwitterOAuth
     */
    protected $connection = null;

    /**
     * Constructor.
     *
     * @param TwitterOAuth $connection
     */
    public function __construct(TwitterOAuth $connection)
    {
        $this->connection = $connection;
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

        $this->connection->post('statuses/update', array('status' => $message));
    }
}
