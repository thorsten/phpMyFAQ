<?php

/**
 * The phpMyFAQ\Database\SqlServerUnicodeLiterals class prefixes non-ASCII string literals
 * in a SQL query with the T-SQL national character marker (N'...') so SQL Server evaluates
 * them as NVARCHAR instead of lossy VARCHAR.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-08-17
 */

declare(strict_types=1);

namespace phpMyFAQ\Database;

/**
 * Class SqlServerUnicodeLiterals
 *
 * @package phpMyFAQ\Database
 */
final class SqlServerUnicodeLiterals
{
    public static function apply(string $query): string
    {
        if (!self::containsNonAscii($query)) {
            return $query;
        }

        $length = strlen($query);
        $result = '';
        $position = 0;

        while ($position < $length) {
            $char = $query[$position];

            if ($char === '-' && ($position + 1) < $length && $query[$position + 1] === '-') {
                $position = self::copyUntil($query, $result, $position, "\n");
                continue;
            }

            if ($char === '/' && ($position + 1) < $length && $query[$position + 1] === '*') {
                $position = self::copyUntil($query, $result, $position, '*/');
                continue;
            }

            if ($char === '[') {
                $position = self::copyDelimited($query, $result, $position, ']');
                continue;
            }

            if ($char === '"') {
                $position = self::copyDelimited($query, $result, $position, '"');
                continue;
            }

            if ($char === "'") {
                $position = self::copyStringLiteral($query, $result, $position);
                continue;
            }

            $result .= $char;
            ++$position;
        }

        return $result;
    }

    private static function containsNonAscii(string $string): bool
    {
        return preg_match('/[\x80-\xFF]/', $string) === 1;
    }

    /**
     * Copies everything from $position up to and including $terminator (or to the end of the
     * query when the terminator never appears) and returns the position after the copied chunk.
     */
    private static function copyUntil(string $query, string &$result, int $position, string $terminator): int
    {
        $end = strpos($query, $terminator, $position);
        $end = $end === false ? strlen($query) : $end + strlen($terminator);

        $result .= substr($query, $position, $end - $position);

        return $end;
    }

    /**
     * Copies a delimited identifier ([name] or "name"), honouring the doubled-delimiter
     * escape, and returns the position after the closing delimiter.
     */
    private static function copyDelimited(string $query, string &$result, int $position, string $delimiter): int
    {
        $length = strlen($query);
        $end = $position + 1;

        while ($end < $length) {
            if ($query[$end] !== $delimiter) {
                ++$end;
                continue;
            }

            if (($end + 1) < $length && $query[$end + 1] === $delimiter) {
                $end += 2;
                continue;
            }

            ++$end;
            break;
        }

        $result .= substr($query, $position, $end - $position);

        return $end;
    }

    /**
     * Copies a single-quoted string literal, honouring the doubled-quote escape, and prefixes
     * it with the national marker when its content contains non-ASCII bytes and the literal
     * is not already marked.
     */
    private static function copyStringLiteral(string $query, string &$result, int $position): int
    {
        $length = strlen($query);
        $end = $position + 1;

        while ($end < $length) {
            if ($query[$end] !== "'") {
                ++$end;
                continue;
            }

            if (($end + 1) < $length && $query[$end + 1] === "'") {
                $end += 2;
                continue;
            }

            ++$end;
            break;
        }

        $literal = substr($query, $position, $end - $position);

        if (self::containsNonAscii($literal) && !self::endsWithNationalMarker($result)) {
            $result .= self::endsWithWordCharacter($result) ? ' N' : 'N';
        }

        $result .= $literal;

        return $end;
    }

    /**
     * True when the output already ends with a standalone N marker, i.e. an N that is not the
     * last letter of a preceding keyword or identifier such as THEN or COLUMN.
     */
    private static function endsWithNationalMarker(string $result): bool
    {
        $length = strlen($result);
        if ($length === 0 || $result[$length - 1] !== 'N' && $result[$length - 1] !== 'n') {
            return false;
        }

        return $length === 1 || !self::isWordCharacter($result[$length - 2]);
    }

    private static function endsWithWordCharacter(string $result): bool
    {
        return $result !== '' && self::isWordCharacter($result[strlen($result) - 1]);
    }

    private static function isWordCharacter(string $char): bool
    {
        return preg_match('/[\w\x80-\xFF]/', $char) === 1;
    }
}
