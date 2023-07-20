<?php

/**
 * Bookmark handling.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2004-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-19
 */

namespace phpMyFAQ;

use phpMyFAQ\Configuration;
use phpMyFAQ\User\CurrentUser;

/**
 * Class Bookmark
 *
 * @package phpMyFAQ
 */
class Bookmark
{
    /**
     * Constructor.
     *
     * @param Configuration $config Configuration object
     * @param CurrentUser   $user   CurrentUser object
     */
    public function __construct(private Configuration $config, private CurrentUser $user){}

    /**
     * Returns true if a given Faq-Id is a bookmark of the current User.
     * Returns false if not.
     *
     * @param int $faqId Id of the Faq
     * @return bool
     */
    public function isFaqBookmark(int $faqId): bool
    {
        $bookmarks = $this->getAll();
        $success = false;
        foreach ($bookmarks as $object => $key) {
            if ((int) $key->faqid === $faqId) {
                $success = true;
            }
        }
        return $success;
    }

    /**
     * Saves a given Faq to the bookmark collection of the current user.
     *
     * @param int $faqId Id of the Faq
     */
    public function saveFaqAsBookmarkById(int $faqId)
    {
        $query = sprintf(
            "INSERT INTO %sfaqbookmarks(userid, faqid) VALUES (%d, %d)",
            Database::getTablePrefix(),
            $this->user->getUserId(),
            $faqId
        );
        return $this->config->getDb()->query($query);
    }

    /**
     * Gets all bookmarks from the current user.
     */
    public function getAll()
    {
        $query = sprintf(
            'SELECT faqid FROM %sfaqbookmarks WHERE userid=%d',
            Database::getTablePrefix(),
            $this->user->getUserId()
        );
        $result = $this->config->getDb()->query($query);
        return $this->config->getDb()->fetchAll($result);
    }

    /**
     * Removes a bookmark from the current user.
     *
     * @param int $faqId Id of the Faq
     */
    public function removeBookmark(int $faqId)
    {
        $query = sprintf(
            'DELETE FROM %sfaqbookmarks WHERE userid=%d AND faqid=%d',
            Database::getTablePrefix(),
            $this->user->getUserId(),
            $faqId
        );
        return $this->config->getDb()->query($query);
    }
}
