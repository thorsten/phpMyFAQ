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

use phpMyFAQ\Database;

trait CurrentUserAccountStateTrait
{
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

        $result = $this->configuration->getDb()->query($query);

        return (bool) $this->configuration->getDb()->fetchRow($result);
    }

    public function isBlocked(): bool
    {
        $query = sprintf(
            'SELECT account_status FROM %sfaquser WHERE user_id = %d',
            Database::getTablePrefix(),
            $this->getUserId(),
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchRow($result);

        return $row === 'blocked';
    }
}
