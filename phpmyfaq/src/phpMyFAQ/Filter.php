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
        if ($filter === FILTER_SANITIZE_SPECIAL_CHARS) {
            $return = filter_input($type, $variableName, FILTER_CALLBACK, [
                'options' => new Filter()->filterSanitizeString(...),
            ]);
        } else {
            $return = filter_input($type, $variableName, $filter);
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
        if ($filter === FILTER_SANITIZE_SPECIAL_CHARS) {
            $return = filter_var($variable, FILTER_CALLBACK, ['options' => new Filter()->filterSanitizeString(...)]);
        } else {
            $return = filter_var($variable, $filter);
        }

        return $return === false || $return === null ? $default : $return;
    }

    /**
     * Validates an email address and sanitizes it for safe output.
     */
    public static function filterEmail(mixed $variable, mixed $default = null): mixed
    {
        $validated = self::filterVar($variable, FILTER_VALIDATE_EMAIL, $default);
        if ($validated !== null && $validated !== false && $validated !== $default) {
            return self::filterVar($validated, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $validated;
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
            $cleanKey = strip_tags($key);
            $cleanUrlData[$cleanKey] = self::sanitizeQueryValue($urlPart);
        }

        return http_build_query($cleanUrlData, arg_separator: '&', encoding_type: PHP_QUERY_RFC3986);
    }

    /**
     * Recursively sanitizes query string values by stripping tags.
     */
    private static function sanitizeQueryValue(mixed $value): string|array
    {
        if (is_array($value)) {
            return array_map(static fn($v): string|array => self::sanitizeQueryValue($v), $value);
        }

        return strip_tags((string) $value);
    }

    /**
     * This method is a polyfill for FILTER_SANITIZE_STRING, deprecated since PHP 8.1.
     */
    public function filterSanitizeString(string $string): string
    {
        $string = str_replace("\x00", '', $string);
        $string = strip_tags($string);
        return str_replace(["'", '"'], ['&apos;', '&quot;'], $string);
    }

    /**
     * Sanitizes HTML by allowing safe elements and attributes via Symfony's HtmlSanitizer.
     */
    public static function removeAttributes(string $html = ''): string
    {
        // remove broken stuff
        $html = str_replace(search: '&#13;', replace: '', subject: $html);

        $config = new HtmlSanitizerConfig()
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowAttribute('class', allowedElements: '*')
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
