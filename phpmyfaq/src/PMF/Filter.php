<?php

/**
 * ext/filter wrapper class.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2009-01-28
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Filter.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2009-01-28
 */
class PMF_Filter
{
    /**
     * Static wrapper method for filter_input().
     *
     * @param int    $type         Filter type
     * @param string $variableName Variable name
     * @param int    $filter       Filter
     * @param mixed  $default      Default value
     *
     * @return mixed
     */
    public static function filterInput($type, $variableName, $filter, $default = null)
    {
        $return = filter_input($type, $variableName, $filter);

        return (is_null($return) || $return === false) ? $default : $return;
    }

    /**
     * Static wrapper method for filter_input_array.
     *
     * @param int   $type       Filter type
     * @param array $definition Definition
     *
     * @return mixed
     */
    public static function filterInputArray($type, Array $definition)
    {
        return filter_input_array($type, $definition);
    }

    /**
     * Static wrapper method for filter_var().
     *
     * @param mixed $variable Variable
     * @param int   $filter   Filter
     * @param mixed $default  Default value
     *
     * @return mixed
     */
    public static function filterVar($variable, $filter, $default = null)
    {
        $return = filter_var($variable, $filter);

        return ($return === false) ? $default : $return;
    }

    /**
     * Filters a query string.
     *
     * @return string
     */
    public static function getFilteredQueryString()
    {
        $urlData = [];
        $cleanUrlData = [];

        if (!isset($_SERVER['QUERY_STRING'])) {
            return '';
        }

        parse_str($_SERVER['QUERY_STRING'], $urlData);

        foreach ($urlData as $key => $urlPart) {
            $cleanUrlData[strip_tags($key)] = strip_tags($urlPart);
        }

        return http_build_query($cleanUrlData);
    }
}
