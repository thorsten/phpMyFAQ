<?php

/**
 * ext/filter wrapper class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-01-28
 */

namespace phpMyFAQ;

/**
 * Class Filter
 *
 * @testdox Filter should
 * @package phpMyFAQ
 */
class Filter
{
    /**
     * Static wrapper method for filter_input().
     *
     * @param int        $type Filter type
     * @param string     $variableName Variable name
     * @param int        $filter Filter
     * @param mixed|null $default Default value
     * @return mixed
     */
    public static function filterInput(int $type, string $variableName, int $filter, mixed $default = null): mixed
    {
        $return = filter_input($type, $variableName, $filter);

        if ($filter === FILTER_UNSAFE_RAW) {
            $return = filter_input(
                $type,
                $variableName,
                FILTER_CALLBACK,
                ['options' => [new Filter(), 'filterSanitizeString']]
            );
        }

        return (is_null($return) || $return === false) ? $default : $return;
    }

    /**
     * Static wrapper method for filter_input_array.
     *
     * @param int   $type Filter type
     * @param array $definition Definition
     * @return array|bool|null
     */
    public static function filterInputArray(int $type, array $definition): array|bool|null
    {
        return filter_input_array($type, $definition);
    }

    /**
     * Static wrapper method for filter_var().
     *
     * @param mixed      $variable Variable
     * @param int        $filter Filter
     * @param mixed|null $default Default value
     * @return mixed
     */
    public static function filterVar(mixed $variable, int $filter, mixed $default = null): mixed
    {
        $return = filter_var($variable, $filter);

        if ($filter === FILTER_UNSAFE_RAW) {
            $return = filter_var(
                $variable,
                FILTER_CALLBACK,
                ['options' => [new Filter(), 'filterSanitizeString']]
            );
        }

        return ($return === false) ? $default : $return;
    }

    /**
     * Filters a query string.
     *
     * @return string
     */
    public static function getFilteredQueryString(): string
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
     * This method is a polyfill for FILTER_SANITIZE_STRING, deprecated since PHP 8.1.
     * @param string $string
     * @return string
     */
    public function filterSanitizeString(string $string): string
    {
        $string = htmlspecialchars($string);
        $string = preg_replace('/\x00|<[^>]*>?/', '', $string);
        return str_replace(["'", '"'], ['&#39;', '&#34;'], $string);
    }

    /**
     * Removes a lot of HTML attributes.
     *
     * @param string $html
     * @return string
     */
    public static function removeAttributes(string $html = ''): string
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
