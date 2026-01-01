<?php

/**
 * Helper class for phpMyFAQ user.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package    phpMyFAQ\Helper
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright  2021-2026 phpMyFAQ Team
 * @license    https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link       https://www.phpmyfaq.de
 * @since      2021-03-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use phpMyFAQ\User;

/**
 * Class UserHelper
 *
 * @package phpMyFAQ\Helper
 */
readonly class UserHelper
{
    /**
     * UserHelper constructor.
     */
    public function __construct(
        private User $user,
    ) {
    }

    /**
     * Get all users in <option> tags.
     *
     * @param int  $selectedId Selected user ID
     * @param bool $allowBlockedUsers Allow blocked users as well, e.g., in admin
     * @return array Array of users with 'id', 'selected', 'displayName', and 'login' keys
     */
    public function getAllUsersForTemplate(int $selectedId = 1, bool $allowBlockedUsers = false): array
    {
        $users = [];
        $user = clone $this->user;
        $allUserIds = $user->getAllUsers(true, $allowBlockedUsers);

        foreach ($allUserIds as $allUserId) {
            if ($allUserId === -1) {
                continue;
            }

            $user->getUserById($allUserId);
            $users[] = [
                'id' => $allUserId,
                'selected' => $allUserId === $selectedId,
                'displayName' => $user->getUserData('display_name'),
                'login' => $user->getLogin(),
            ];
        }

        return $users;
    }
}
