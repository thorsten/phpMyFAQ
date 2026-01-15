<?php

/**
 * Bookmark Repository.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-10-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Bookmark;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\User\CurrentUser;

readonly class BookmarkRepository implements BookmarkRepositoryInterface
{
    public function __construct(
        private Configuration $configuration,
        private CurrentUser $currentUser,
    ) {
    }

    public function add(int $faqId): bool
    {
        if ($faqId <= 0) {
            return false;
        }

        $table = Database::getTablePrefix() . 'faqbookmarks';
        $userId = $this->currentUser->getUserId();

        $query = <<<SQL
                INSERT INTO {$table} (userid, faqid) VALUES ({$userId},{$faqId})
            SQL;

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * @return array<int, object>
     */
    public function getAll(): array
    {
        $table = Database::getTablePrefix() . 'faqbookmarks';
        $userId = $this->currentUser->getUserId();

        $query = <<<SQL
                SELECT faqid FROM {$table} WHERE userid = {$userId}
            SQL;

        $result = $this->configuration->getDb()->query($query);
        $data = $this->configuration->getDb()->fetchAll($result);

        return is_array($data) ? $data : [];
    }

    public function remove(int $faqId): bool
    {
        if ($faqId <= 0) {
            return false;
        }

        $table = Database::getTablePrefix() . 'faqbookmarks';
        $userId = $this->currentUser->getUserId();

        $query = <<<SQL
                DELETE FROM {$table} WHERE userid = {$userId} AND faqid = {$faqId}
            SQL;

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function removeAll(): bool
    {
        $table = Database::getTablePrefix() . 'faqbookmarks';
        $userId = $this->currentUser->getUserId();

        $query = <<<SQL
                DELETE FROM {$table} WHERE userid = {$userId}
            SQL;

        return (bool) $this->configuration->getDb()->query($query);
    }
}
