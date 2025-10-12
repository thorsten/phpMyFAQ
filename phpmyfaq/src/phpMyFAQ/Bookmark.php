<?php

declare(strict_types=1);

/**
 * Bookmark handling.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-19
 */

namespace phpMyFAQ;

use phpMyFAQ\User\CurrentUser;

/**
 * Class Bookmark
 */
class Bookmark
{
    /**
     * @var array<int, object>|null
     */
    private ?array $bookmarkCache;

    /**
     * Constructor.
     *
     * @param Configuration $configuration Configuration object
     * @param CurrentUser $currentUser CurrentUser object
     */
    public function __construct(
        private readonly Configuration $configuration,
        private readonly CurrentUser $currentUser,
    ) {
        $this->bookmarkCache = null;
    }

    /**
     * Returns true if a given FAQ ID is a bookmark of the current User.
     * Returns false if not.
     *
     * @param int $faqId ID of the Faq
     */
    public function isFaqBookmark(int $faqId): bool
    {
        if ($faqId <= 0) {
            return false;
        }

        $bookmarks = $this->getAll();
        if ($bookmarks === []) {
            return false;
        }

        foreach ($bookmarks as $bookmark) {
            if (isset($bookmark->faqid) && (int) $bookmark->faqid === $faqId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Saves a given FAQ to the bookmark collection of the current user.
     *
     * @param int $faqId ID of the Faq
     */
    public function add(int $faqId): bool
    {
        if ($faqId <= 0) {
            return false;
        }

        $query = sprintf(
            'INSERT INTO %sfaqbookmarks(userid, faqid) VALUES (%d, %d)',
            Database::getTablePrefix(),
            $this->currentUser->getUserId(),
            $faqId,
        );

        $result = (bool) $this->configuration->getDb()->query($query);

        if ($result) {
            $this->bookmarkCache = null;
        }

        return $result;
    }

    /**
     * Gets all bookmarks from the current user.
     *
     * @return array<int, object> List of DB result objects each containing ->faqid
     */
    public function getAll(): array
    {
        if ($this->bookmarkCache !== null) {
            return $this->bookmarkCache;
        }

        $query = sprintf(
            'SELECT faqid FROM %sfaqbookmarks WHERE userid = %d',
            Database::getTablePrefix(),
            $this->currentUser->getUserId(),
        );
        $result = $this->configuration->getDb()->query($query);
        $data = $this->configuration->getDb()->fetchAll($result);

        $this->bookmarkCache = is_array($data) ? $data : [];

        return $this->bookmarkCache;
    }

    /**
     * Removes a bookmark from the current user.
     *
     * @param int $faqId ID of the Faq
     */
    public function remove(int $faqId): bool
    {
        if ($faqId <= 0) {
            return false;
        }

        $query = sprintf(
            'DELETE FROM %sfaqbookmarks WHERE userid = %d AND faqid = %d',
            Database::getTablePrefix(),
            $this->currentUser->getUserId(),
            $faqId,
        );

        $result = (bool) $this->configuration->getDb()->query($query);

        if ($result) {
            $this->bookmarkCache = null;
        }

        return $result;
    }

    /**
     * Removes all bookmarks from the current user.
     */
    public function removeAll(): bool
    {
        $query = sprintf(
            'DELETE FROM %sfaqbookmarks WHERE userid = %d',
            Database::getTablePrefix(),
            $this->currentUser->getUserId(),
        );

        $result = (bool) $this->configuration->getDb()->query($query);

        if ($result) {
            $this->bookmarkCache = null;
        }

        return $result;
    }

    /**
     * @return array<int, array{url:string,title:string,id:int,answer:string}>
     */
    public function getBookmarkList(): array
    {
        $bookmarks = $this->getAll();
        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);
        $faq = new Faq($this->configuration);
        $faq->setUser($currentUser)->setGroups($currentGroups);

        $category = new Category($this->configuration);
        $list = [];

        foreach ($bookmarks as $bookmark) {
            if (!isset($bookmark->faqid)) {
                continue;
            }
            $faqId = (int) $bookmark->faqid;
            if ($faqId <= 0) {
                continue;
            }

            $faq->getFaq($faqId);
            $faqData = $faq->faqRecord;

            if (empty($faqData['id'])) {
                continue;
            }

            $categoryId = $category->getCategoryIdFromFaq($faqData['id']);
            $url = sprintf(
                '%sindex.php?action=faq&id=%d&cat=%d&artlang=%s',
                $this->configuration->getDefaultUrl(),
                (int) $faqData['id'],
                $categoryId,
                $faqData['lang'] ?? '',
            );

            $link = new Link($url, $this->configuration);
            $title = (string) ($faqData['title'] ?? '');
            $link->text = Strings::htmlentities($title);
            $link->itemTitle = $link->text;
            $link->tooltip = $link->text;

            $list[] = [
                'url' => $link->toString(),
                'title' => htmlspecialchars_decode($title),
                'id' => (int) $faqData['id'],
                'answer' => (string) ($faqData['content'] ?? ''),
            ];
        }

        return $list;
    }
}
