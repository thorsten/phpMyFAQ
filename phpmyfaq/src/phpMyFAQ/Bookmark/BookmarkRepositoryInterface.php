<?php

/**
 * Bookmark Repository Interface.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 */

declare(strict_types=1);

namespace phpMyFAQ\Bookmark;

interface BookmarkRepositoryInterface
{
    /**
     * Add a bookmark for the given FAQ id.
     */
    public function add(int $faqId): bool;

    /**
     * Return all bookmark rows for the current user.
     *
     * @return array<int, object>
     */
    public function getAll(): array;

    /**
     * Remove the bookmark for the given FAQ id.
     */
    public function remove(int $faqId): bool;

    /**
     * Remove all bookmarks for the current user.
     */
    public function removeAll(): bool;
}
