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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-19
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Bookmark\BookmarkFormatter;
use phpMyFAQ\Bookmark\BookmarkRepository;
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

    private readonly BookmarkRepository $repository;

    private readonly BookmarkFormatter $formatter;

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
        $this->repository = new BookmarkRepository($this->configuration, $this->currentUser);
        $this->formatter = new BookmarkFormatter($this->configuration, $this->currentUser);
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
            if (!(isset($bookmark->faqid) && (int) $bookmark->faqid === $faqId)) {
                continue;
            }

            return true;
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
        $result = $this->repository->add($faqId);

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

        $this->bookmarkCache = $this->repository->getAll();

        return $this->bookmarkCache;
    }

    /**
     * Removes a bookmark from the current user.
     *
     * @param int $faqId ID of the Faq
     */
    public function remove(int $faqId): bool
    {
        $result = $this->repository->remove($faqId);

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
        $result = $this->repository->removeAll();

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
        $list = [];

        foreach ($bookmarks as $bookmark) {
            $item = $this->formatter->format($bookmark);
            if ($item !== null) {
                $list[] = $item;
            }
        }

        return $list;
    }
}
