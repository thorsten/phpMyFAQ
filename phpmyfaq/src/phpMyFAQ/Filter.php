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
        $return = $filter === FILTER_SANITIZE_SPECIAL_CHARS ? filter_input($type, $variableName, FILTER_CALLBACK, [
                'options' => new Filter()->filterSanitizeString(...),
            ]) : filter_input($type, $variableName, $filter);

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
     * The conditional return type narrows the result by filter so callers stop
     * receiving `mixed`. Literal filter values are used because mago currently
     * resolves named constants in conditional types as class names; the values
     * map to:
     *   515 = FILTER_SANITIZE_SPECIAL_CHARS, 257 = FILTER_VALIDATE_INT,
     *   258 = FILTER_VALIDATE_BOOLEAN.
     * On failure the method returns `$default`, so each branch is unioned with
     * the `TDefault` template (which resolves to `null` when no default is given,
     * or e.g. `string`/`array` when a typed default is passed).
     *
     * Interim workaround: mago already infers native `filter_var()` return types
     * from validation flags, but not through this wrapper (the `$filter` argument
     * is a runtime variable here). Migrate hot call sites to native `filter_var()`
     * once that inference is richer. See https://github.com/carthage-software/mago/issues/1117
     *
     * @template TDefault
     *
     * @param mixed    $variable Variable
     * @param int      $filter Filter
     * @param TDefault $default Default value
     *
     * @return ($filter is 515 ? string|TDefault : ($filter is 257 ? int|TDefault : ($filter is 258 ? bool|TDefault : mixed)))
     */
    public static function filterVar(mixed $variable, int $filter, mixed $default = null): mixed
    {
        $return = $filter === FILTER_SANITIZE_SPECIAL_CHARS
            ? filter_var($variable, FILTER_CALLBACK, ['options' => new Filter()->filterSanitizeString(...)])
            : filter_var($variable, $filter);

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
            return array_map(self::sanitizeQueryValue(...), $value);
        }

        return strip_tags((string) $value);
    }

    /**
     * This method is a polyfill for FILTER_SANITIZE_STRING, deprecated since PHP 8.1.
     */
    public function filterSanitizeString(string $string): string
    {
        $string = str_replace("\x00", replace: '', subject: $string);
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
