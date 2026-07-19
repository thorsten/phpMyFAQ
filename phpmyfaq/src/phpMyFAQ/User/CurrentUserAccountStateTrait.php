<?php

/**
 * Trait for checking the current user's account state
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-24
 */

declare(strict_types=1);

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

trait CurrentUserAccountStateTrait
{
    /**
     * Returns the user ID of the composing user class.
     */
    abstract public function getUserId(): int;

    /**
     * Returns the configuration of the composing user class.
     */
    abstract protected function accountStateConfiguration(): Configuration;

    /**
     * Returns true if the user is a local user, otherwise false.
     */
    public function isLocalUser(): bool
    {
        $query = sprintf(
            "SELECT auth_source FROM %sfaquser WHERE auth_source = 'local' AND user_id = %d",
            Database::getTablePrefix(),
            $this->getUserId(),
        );

        $db = $this->accountStateConfiguration()->getDb();
        $result = $db->query($query);

        return (bool) $db->fetchRow($result);
    }

    public function isBlocked(): bool
    {
        $query = sprintf(
            'SELECT account_status FROM %sfaquser WHERE user_id = %d',
            Database::getTablePrefix(),
            $this->getUserId(),
        );

        $db = $this->accountStateConfiguration()->getDb();
        $result = $db->query($query);
        $row = $db->fetchArray($result);

        return ($row['account_status'] ?? null) === 'blocked';
    }
}
