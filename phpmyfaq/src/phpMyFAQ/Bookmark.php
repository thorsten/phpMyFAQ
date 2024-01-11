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

use phpMyFAQ\Language\Plurals;
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
    public function __construct(private readonly Configuration $config, private readonly CurrentUser $user)
    {
    }

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
     * @param int $faqId ID of the Faq
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
            'SELECT faqid FROM %sfaqbookmarks WHERE userid = %d',
            Database::getTablePrefix(),
            $this->user->getUserId()
        );
        $result = $this->config->getDb()->query($query);
        return $this->config->getDb()->fetchAll($result);
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
            $this->user->getUserId(),
            $faqId
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Renders the bookmark tree for the personal bookmark list.
     *
     * @return string
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
     * @return string
     */
    public function renderBookmarkList(): string
    {
        $bookmarks = $this->getAll();
        $faq = new Faq($this->config);
        $category = new Category($this->config);
        $html = '';

        foreach ($bookmarks as $object => $key) {
            $faq->getRecord((int) $key->faqid);
            $faqData = $faq->faqRecord;

            $url = sprintf(
                '%sindex.php?action=faq&amp;id=%d&cat=%d&artlang=%s',
                $this->config->getDefaultUrl(),
                $faqData['id'],
                $category->getCategoryIdFromFaq($faqData['id']),
                $faqData['lang']
            );

            $link = new Link($url, $this->config);
            $link->text = $link->itemTitle = $link->tooltip = Strings::htmlentities($faqData['title']);

            $html .= sprintf(
                '<a href="%s" class="list-group-item list-group-item-action" id="delete-bookmark-%d">' .
                '<div class="d-flex w-100 justify-content-between">' .
                '<h5 class="mb-1">%s</h5>' .
                '<i class="bi bi-trash bi-2x m-1 pmf-delete-bookmark" data-pmf-bookmark-id="%d"></i>' .
                '</div>' .
                '</a>',
                $link->toString(),
                $faqData['id'],
                htmlspecialchars_decode($faqData['content']),
                $faqData['id'],
            );
        }
        return $html;
    }
}
