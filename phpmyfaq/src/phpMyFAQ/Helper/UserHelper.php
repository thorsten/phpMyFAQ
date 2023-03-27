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
 * @copyright  2021-2023 phpMyFAQ Team
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
class UserHelper
{
    /**
     * UserHelper constructor.
     */
    public function __construct(private readonly User $user)
    {
    }

    /**
     * Get all users in <option> tags.
     *
     * @param int  $id Selected user ID
     * @param bool $allowBlockedUsers Allow blocked users as well, e.g. in admin
     */
    public function getAllUserOptions(int $id = 1, bool $allowBlockedUsers = false): string
    {
        $options = '';
        $allUsers = $this->user->getAllUsers(true, $allowBlockedUsers);

        foreach ($allUsers as $userId) {
            if (-1 !== $userId) {
                $this->user->getUserById($userId);
                $options .= sprintf(
                    '<option value="%d" %s>%s (%s)</option>',
                    $userId,
                    (($userId === $id) ? 'selected' : ''),
                    Strings::htmlentities($this->user->getUserData('display_name')),
                    $this->user->getLogin()
                );
            }
        }

        return $options;
    }
}
