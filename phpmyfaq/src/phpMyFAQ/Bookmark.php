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
    public function __construct(private Configuration $config, private CurrentUser $user)
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

    /**
     * Renders the bookmark tree for the personally bookmark list.
     *
     * @return string
     */
    public function renderBookmarkTree(): string
    {
        return sprintf(
            '<ul class="list-group list-group-flush mb-4">%s</ul>',
            $this->buildBookmarkList()
        );
    }

    /**
     * Builds the list of bookmarks for the bookmark tree.
     *
     * @return string
     */
    public function buildBookmarkList(): string
    {
        $bookmarks = $this->getAllBookmarks();
        $html = '';
        foreach ($bookmarks as $object => $key) {
            $faq = new Faq($this->config);
            $faq->getRecord((int) $key->faqid);
            $faqData = $faq->faqRecord;

            $url = sprintf(
                '%sindex.php?action=faq&amp;id=%d',
                $this->config->getDefaultUrl(),
                $key->faqid
            );
            $link = new Link($url, $this->config);
            $link->text = $faqData['title'];
            $link->itemTitle = $faqData['title'];
            $link->tooltip = $faqData['title'];

            $plurals = new Plurals();
            $visits = new Visits($this->config);
            foreach ($visits->getAllData() as $item) {
                if ((string) $key->faqid === $item['id']) {
                    $visitsFaq = $item['visits'];
                }
            }
            $html .= sprintf(
                '<li class="list-group-item d-flex justify-content-between align-items-start">'
                . '<div class="ms-2 me-auto">'
                . '<div class="fw-bold">%s</div>'
                . '<div class="small">%s</div>'
                . '</div>'
                . '<span id="viewsPerRecord" class="badge bg-primary rounded-pill">%s</span>'
                . '</li>',
                $link->toHtmlAnchor(),
                strip_tags($faqData['content']),
                $plurals->getMsg('plmsgViews', $visitsFaq)
            );
        }
        return $html;
    }
}
