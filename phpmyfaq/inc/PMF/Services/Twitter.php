<?php

/**
 * Service class for Twitter support.
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
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2010-09-05
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Services_Twitter.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2010-09-05
 */
class PMF_Services_Twitter extends PMF_Services
{
    /**
     * @var TwitterOAuth
     */
    protected $connection = null;

    /**
     * Constructor.
     *
     * @param TwitterOAuth $connection
     *
     * @return PMF_Services_Twitter
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
    public function addPost($question, $tags, $link)
    {
        $hashtags = '';

        if ($tags != '') {
            $hashtags = '#'.str_replace(',', ' #', $tags);
        }

        $message = PMF_String::htmlspecialchars($question);
        $message .= ' '.$hashtags;
        $message .= ' '.$link;

        $this->connection->post('statuses/update', array('status' => $message));
    }
}
