<?php

/**
 * This class manages user permissions and group memberships.
 * There are currently two possible extensions of this class: basic and medium by the
 * classes BasicPermission and MediumPermission.
 * The permission type can be selected by calling the static method $perm = Permission::selectPerm(perm_type)
 * where perm_type is 'medium'.
 * Both ways, a BasicPermission or MediumPermission is returned.
 * Perhaps the most important method is $perm->hasPermission(right_name).
 * This checks whether the user has the user_id set with $perm->setPerm()
 * The permission object is added to a user using the user's addPerm() method.
 * A single permission-object is allowed for each user.
 * The permission-object is in the user's $perm variable.
 * Permission methods are performed using the variable (e.g., $user->perm->method()).
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-17
 */

namespace phpMyFAQ;

use InvalidArgumentException;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Permission\MediumPermission;

/**
 * Class Permission
 *
 * @package phpMyFAQ
 */
class Permission
{
    /**
     * Permission::selectPerm() returns an instance of an implementation of the Permission interface.
     *
     * @param  string        $permLevel
     * @param  Configuration $configuration
     * @return BasicPermission|MediumPermission
     */
    public static function createPermission(
        string $permLevel,
        Configuration $configuration
    ): BasicPermission|MediumPermission {
        $permClass = sprintf('\phpMyFAQ\Permission\%sPermission', ucfirst(strtolower($permLevel)));

        if (!class_exists($permClass)) {
            throw new InvalidArgumentException(sprintf('Invalid permission level: %s', $permLevel));
        }

        return new $permClass($configuration);
    }
}
