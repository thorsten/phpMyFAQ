<?php
/**
 * Service class for Twitter support
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Services
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-09-05
 */

/**
 * PMF_Services_Twitter
 *
 * @category  phpMyFAQ
 * @package   Services
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-09-05
 */
class PMF_Services_Twitter extends PMF_Services
{
    /**
     * @var TwitterOAuth
     */
    protected $connection = null;

    /**
     * Constructor
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
     * Adds a post to Twitter
     *
     * @param string $question Question
     * @param string $tags     String of tags
     * @param string $link     URL to FAQ
     *
     * @return void
     */
    public function addPost($question, $tags, $link)
    {
        $hashtags = '';
        
        if ($tags != '') {
            $hashtags = '#' . str_replace(',', ' #', $tags);
        }

        $message  = PMF_String::htmlspecialchars($question);
        $message .= ' ' . $hashtags;
        $message .= ' ' . $link;

        $this->connection->post('statuses/update', array('status' => $message));
    }
}