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
    public function saveFaqAsBookmarkById(int $faqId): bool
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
     * Renders the bookmark tree for the personal bookmark list.
     */
    public function renderBookmarkTree(): string
    {
        return sprintf(
            '<div class="list-group mb-4">%s</div>',
            $this->renderBookmarkList()
        );
    }

    /**
     * Builds the list of bookmarks for the bookmark tree.
     *
     * @todo move this method to a new helper class
     */
    public function renderBookmarkList(): string
    {
        $bookmarks = $this->getAll();
        $faq = new Faq($this->configuration);
        $category = new Category($this->configuration);
        $html = '';

        foreach ($bookmarks as $bookmark) {
            $faq->getRecord((int) $bookmark->faqid);
            $faqData = $faq->faqRecord;

            $url = sprintf(
                '%sindex.php?action=faq&amp;id=%d&cat=%d&artlang=%s',
                $this->configuration->getDefaultUrl(),
                $faqData['id'],
                $category->getCategoryIdFromFaq($faqData['id']),
                $faqData['lang']
            );

            $link = new Link($url, $this->configuration);
            $link->text = Strings::htmlentities($faqData['title']);
            $link->itemTitle = $link->text;
            $link->tooltip = $link->text;

            $html .= sprintf(
                '<a href="%s" class="list-group-item list-group-item-action" id="delete-bookmark-%d">' .
                '<div class="d-flex w-100 justify-content-between">' .
                '<h5 class="mb-1">%s</h5>' .
                '<i class="bi bi-trash-fill text-danger m-1 pmf-delete-bookmark" data-pmf-bookmark-id="%d"></i>' .
                '</div>' .
                '</a>',
                $link->toString(),
                $faqData['id'],
                htmlspecialchars_decode((string) $faqData['title']),
                $faqData['id'],
            );
        }

        return $html;
    }
}
