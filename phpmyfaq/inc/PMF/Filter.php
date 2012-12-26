<?php
/**
 * ext/filter wrapper class
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Filter
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-01-28
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Filter
 *
 * @category  phpMyFAQ
 * @package   PMF_Filter
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-01-28
 */
class PMF_Filter
{
    /**
     * Static wrapper method for filter_input()
     *
     * @param  integer $type          Filter type
     * @param  string  $variable_name Variable name
     * @param  integer $filter        Filter 
     * @param  mixed   $default       Default value
     *
     * @return mixed
     */
    public static function filterInput ($type, $variable_name, $filter, $default = null)
    {
        $return = filter_input($type, $variable_name, $filter);
        return (is_null($return) || $return === false) ? $default : $return;
    }
    
    /**
     * Static wrapper method for filter_input_array()
     *
     * @param  integer $type       Filter type
     * @param  array   $definition Definition
     * @return mixed
     */
    public static function filterInputArray ($type, Array $definition)
    {
        return filter_input_array($type, $definition);
    }
    
    /**
     * Static wrapper method for filter_var()
     *
     * @param  mixed   $variable Variable
     * @param  integer $filter   Filter
     * @param  mixed   $default       Default value
     * @return mixed
     */
    public static function filterVar ($variable, $filter, $default = null)
    {
        $return = filter_var($variable, $filter);
        return ($return === false) ? $default : $return;
    }
    
    /**
     * Filters a query string
     *
     * @return string
     */
    public static function getFilteredQueryString()
    {
        $urlData = $cleanUrlData = array();
        
        parse_str($_SERVER['QUERY_STRING'], $urlData);
        
        foreach ($urlData as $key => $urlPart) {
            $cleanUrlData[strip_tags($key)] = strip_tags($urlPart);
        }
        
        return http_build_query($cleanUrlData);
    }
}
