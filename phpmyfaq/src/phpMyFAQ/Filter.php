<?php

/**
 * ext/filter wrapper class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-01-28
 */

namespace phpMyFAQ;

/**
 * Class Filter
 *
 * @package phpMyFAQ
 */
class Filter
{
    /**
     * Static wrapper method for filter_input().
     *
     * @param int $type Filter type
     * @param string $variableName Variable name
     * @param int $filter Filter
     * @param mixed $default Default value
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
     * @param int $type Filter type
     * @param array $definition Definition
     *
     * @return mixed
     */
    public static function filterInputArray($type, array $definition)
    {
        return filter_input_array($type, $definition);
    }

    /**
     * Static wrapper method for filter_var().
     *
     * @param mixed $variable Variable
     * @param int $filter Filter
     * @param mixed $default Default value
     *
     * @return mixed
     */
    public static function filterVar($variable, $filter, $default = null)
    {
        $return = filter_var($variable, $filter, $default);

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

    /**
     * Removes a lot of HTML attributes.
     *
     * @param $html
     *
     * @return string
     */
    public static function removeAttributes($html = ''): string
    {
        $keep = [
            'href',
            'src',
            'title',
            'alt',
            'class',
            'style',
            'id',
            'name',
            'size',
            'dir',
            'rel',
            'rev',
            'target',
            'width',
            'height',
            'controls'
        ];

        // remove broken stuff
        $html = str_replace('&#13;', '', $html);

        preg_match_all('/[a-z]+=".+"/iU', $html, $attributes);

        foreach ($attributes[0] as $attribute) {
            $attributeName = stristr($attribute, '=', true);
            if (!in_array($attributeName, $keep)) {
                $html = str_replace(' ' . $attribute, '', $html);
            }
        }

        return $html;
    }
}
