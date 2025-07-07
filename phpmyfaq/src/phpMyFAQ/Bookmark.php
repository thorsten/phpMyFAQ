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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-19
 */

namespace phpMyFAQ;

use phpMyFAQ\User\CurrentUser;

/**
 * Class Bookmark
 *
 * @package phpMyFAQ
 */
readonly class Bookmark
{
    /**
     * Constructor.
     *
     * @param Configuration $configuration Configuration object
     * @param CurrentUser $currentUser CurrentUser object
     */
    public function __construct(private Configuration $configuration, private CurrentUser $currentUser)
    {
    }

    /**
     * Returns true if a given FAQ ID is a bookmark of the current User.
     * Returns false if not.
     *
     * @param int $faqId ID of the Faq
     */
    public function isFaqBookmark(int $faqId): bool
    {
        $bookmarks = $this->getAll();

        foreach ($bookmarks as $bookmark) {
            if ((int) $bookmark->faqid === $faqId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Saves a given Faq to the bookmark collection of the current user.
     *
     * @param int $faqId ID of the Faq
     */
    public function add(int $faqId): bool
    {
        $query = sprintf(
            "INSERT INTO %sfaqbookmarks(userid, faqid) VALUES (%d, %d)",
            Database::getTablePrefix(),
            $this->currentUser->getUserId(),
            $faqId
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Gets all bookmarks from the current user.
     */
    public function getAll(): array
    {
        $query = sprintf(
            'SELECT faqid FROM %sfaqbookmarks WHERE userid = %d',
            Database::getTablePrefix(),
            $this->currentUser->getUserId()
        );
        $result = $this->configuration->getDb()->query($query);
        return $this->configuration->getDb()->fetchAll($result);
    }

    /**
     * Removes a bookmark from the current user.
     *
     * @param int $faqId ID of the Faq
     */
    public function remove(int $faqId): bool
    {
        $query = sprintf(
            'DELETE FROM %sfaqbookmarks WHERE userid = %d AND faqid = %d',
            Database::getTablePrefix(),
            $this->currentUser->getUserId(),
            $faqId
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Removes all bookmarks from the current user.
     *
     */
    public function removeAll(): bool
    {
        $query = sprintf(
            'DELETE FROM %sfaqbookmarks WHERE userid = %d',
            Database::getTablePrefix(),
            $this->currentUser->getUserId()
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function getBookmarkList(): array
    {
        $bookmarks = $this->getAll();
        $faq = new Faq($this->configuration);
        $category = new Category($this->configuration);
        $list = [];

        foreach ($bookmarks as $bookmark) {
            $faq->getFaq((int) $bookmark->faqid);
            $faqData = $faq->faqRecord;

            $url = sprintf(
                '%sindex.php?action=faq&id=%d&cat=%d&artlang=%s',
                $this->configuration->getDefaultUrl(),
                $faqData['id'],
                $category->getCategoryIdFromFaq($faqData['id']),
                $faqData['lang']
            );

            $link = new Link($url, $this->configuration);
            $link->text = Strings::htmlentities($faqData['title']);
            $link->itemTitle = $link->text;
            $link->tooltip = $link->text;

            $list[] = [
                'url' => $link->toString(),
                'title' => htmlspecialchars_decode((string) $faqData['title']),
                'id' => $faqData['id'],
                'answer' => $faqData['content']
            ];
        }

        return $list;
    }
}
