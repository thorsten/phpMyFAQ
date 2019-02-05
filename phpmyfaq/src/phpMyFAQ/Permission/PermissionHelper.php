<?php

namespace phpMyFAQ\Permission;

/**
 * This class is a helper class for permission relevant methods.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-07-18
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Class PermissionHelper
 * @package phpMyFAQ\Permission
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-07-18
 */
class PermissionHelper
{
    /**
     * Renders a select box for permission types.
     *
     * @param string $current Selected option
     * @return string
     */
    public static function permOptions($current)
    {
        $options = ['basic', 'medium', 'large'];
        $output = '';

        foreach ($options as $value) {
            $output .= sprintf(
                '<option value="%s"%s>%s</option>',
                $value,
                ($value == $current) ? ' selected' : '',
                $value
            );
        }

        return $output;
    }
}
