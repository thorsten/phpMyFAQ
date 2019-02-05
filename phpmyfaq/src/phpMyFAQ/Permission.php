<?php

namespace phpMyFAQ;

/**
 * This class manages user permissions and group memberships.
 *
 * There are three possible extensions of this class: basic, medium and large
 * by the classes BasicPermission, MediumPermission and LargePermission. The
 * classes to allow for scalability.
 *
 * The permission type can be selected by calling $perm = Permission(perm_type) or
 * static method $perm = Permission::selectPerm(perm_type) where perm_type is
 * 'medium' or 'large'. Both ways, a BasicPermission, MediumPermission or
 * is returned.
 *
 * Before calling any method, the object $perm needs to be initialised calling
 * user_id, context, context_id). The parameters context and context_id are
 * accepted, but do only matter in LargePermission. In other words, if you have a
 * or MediumPermission, it does not matter if you pass context and context_id or
 * But in LargePermission, they do make a significant difference if passed, thus
 * for up- and downwards-compatibility.
 *
 * Perhaps the most important method is $perm->checkRight(right_name). This
 * checks whether the user having the user_id set with $perm->setPerm()
 *
 * The permission object is added to a user using the user's addPerm() method.
 * a single permission-object is allowed for each user. The permission-object is
 * in the user's $perm variable. Permission methods are performed using the
 * variable (e.g. $user->perm->method() ).
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-17
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Class Permission
 *
 * @package phpMyFAQ
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-17
 */
class Permission
{
    /**
     * Configuration object.
     *
     * @var Configuration
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Permission::selectPerm() returns an instance of a subclass of
     * Permission. $permLevel which subclass is returned.
     *
     * @param $permLevel
     * @param Configuration $config
     * @return Permission
     */
    public static function selectPerm($permLevel, Configuration $config)
    {
        if (isset($permLevel)) {
            $permClass = '\phpMyFAQ\Permission\\' . ucfirst(strtolower($permLevel)) . 'Permission';
            if (class_exists($permClass)) {
                return new $permClass($config);
            }
        }

        return new self($config);
    }
}
