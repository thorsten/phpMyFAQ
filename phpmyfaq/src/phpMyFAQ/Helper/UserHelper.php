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
 * @copyright  2021-2025 phpMyFAQ Team
 * @license    https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link       https://www.phpmyfaq.de
 * @since      2021-03-14
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Strings;
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
    public function __construct(private User $user)
    {
    }

    /**
     * Get all users in <option> tags.
     *
     * @param int  $id Selected user ID
     * @param bool $allowBlockedUsers Allow blocked users as well, e.g., in admin
     */
    public function getAllUserOptions(int $id = 1, bool $allowBlockedUsers = false): string
    {
        $options = '';
        $user = clone $this->user;
        $allUsers = $user->getAllUsers(true, $allowBlockedUsers);

        foreach ($allUsers as $allUser) {
            if (-1 !== $allUser) {
                $user->getUserById($allUser);
                $options .= sprintf(
                    '<option value="%d" %s>%s (%s)</option>',
                    $allUser,
                    (($allUser === $id) ? 'selected' : ''),
                    Strings::htmlentities($user->getUserData('display_name')),
                    $user->getLogin()
                );
            }
        }

        return $options;
    }
}
