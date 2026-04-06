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
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-01-28
 */

declare(strict_types=1);

namespace phpMyFAQ;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HttpFoundation\Request;

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
     * @param int        $type Filter type
     * @param string     $variableName Variable name
     * @param int        $filter Filter
     * @param mixed|null $default Default value
     */
    public static function filterInput(int $type, string $variableName, int $filter, mixed $default = null): mixed
    {
        $return = filter_input($type, $variableName, $filter);

        if ($filter === FILTER_SANITIZE_SPECIAL_CHARS) {
            $return = filter_input($type, $variableName, FILTER_CALLBACK, ['options' =>
                (new Filter())->filterSanitizeString(...)]);
        }

        return is_null($return) || $return === false ? $default : $return;
    }

    /**
     * Static wrapper method for filter_input_array.
     *
     * @param int   $type Filter type
     * @param array $definition Definition
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
     */
    public static function filterVar(mixed $variable, int $filter, mixed $default = null): mixed
    {
        $return = filter_var($variable, $filter);

        if ($filter === FILTER_SANITIZE_SPECIAL_CHARS) {
            $return = filter_var($variable, FILTER_CALLBACK, ['options' => (new Filter())->filterSanitizeString(...)]);
        }

        return $return === false ? $default : $return;
    }

    /**
     * Static wrapper method for filter_var_array().
     */
    public static function filterArray(array $array, array|int $options = FILTER_UNSAFE_RAW): bool|array|null
    {
        return filter_var_array($array, $options);
    }

    /**
     * Filters a query string.
     */
    public static function getFilteredQueryString(): string
    {
        $urlData = [];
        $cleanUrlData = [];

        $request = Request::createFromGlobals();
        $queryString = $request->getQueryString();

        if ($queryString === null) {
            return '';
        }

        parse_str($queryString, $urlData);

        foreach ($urlData as $key => $urlPart) {
            $cleanKey = strip_tags((string) $key);
            if (is_array($urlPart)) {
                // sanitize one level deep; http_build_query will handle arrays
                $cleanUrlData[$cleanKey] = array_map(static fn($v) => strip_tags((string) $v), $urlPart);
                continue;
            }

            $cleanUrlData[$cleanKey] = strip_tags((string) $urlPart);
        }

        return http_build_query($cleanUrlData, arg_separator: '&', encoding_type: PHP_QUERY_RFC3986);
    }

    /**
     * This method is a polyfill for FILTER_SANITIZE_STRING, deprecated since PHP 8.1.
     */
    public function filterSanitizeString(string $string): string
    {
        $string = htmlspecialchars(
            string: $string,
            flags: ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
            encoding: 'UTF-8',
            double_encode: true,
        );
        $string = preg_replace(pattern: '/\x00|<[^>]*>?/', replacement: '', subject: $string);
        return str_replace(["'", '"'], ['&#39;', '&#34;'], (string) $string);
    }

    /**
     * Sanitizes HTML by allowing safe elements and attributes via Symfony's HtmlSanitizer.
     */
    public static function removeAttributes(string $html = ''): string
    {
        // remove broken stuff
        $html = str_replace(search: '&#13;', replace: '', subject: $html);

        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowAttribute('class', allowedElements: '*')
            ->allowAttribute('style', allowedElements: '*')
            ->allowAttribute('id', allowedElements: '*')
            ->allowAttribute('dir', allowedElements: '*')
            ->allowAttribute('name', allowedElements: '*')
            ->allowAttribute('target', allowedElements: 'a')
            ->allowAttribute('controls', allowedElements: ['audio', 'video'])
            ->blockElement('form')
            ->blockElement('input')
            ->blockElement('textarea')
            ->blockElement('select')
            ->blockElement('button');

        $sanitizer = new HtmlSanitizer($config);

        return $sanitizer->sanitize($html);
    }
}
