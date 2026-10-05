<?php

/**
 * Typed wrappers around json_decode().
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
 * @since     2026-10-05
 */

declare(strict_types=1);

namespace phpMyFAQ\Core;

use JsonException;
use stdClass;

/**
 * Decodes JSON documents into a known shape instead of `mixed`.
 *
 * Every method returns null when the document does not have the requested
 * shape. Malformed JSON is reported the way json_decode() reports it: it
 * yields null, unless JSON_THROW_ON_ERROR is passed in $flags.
 */
final class Json
{
    /**
     * Decodes a JSON object or array into an associative array.
     *
     * @return array<array-key, mixed>|null
     * @throws JsonException
     */
    public static function decodeAssoc(string $json, int $flags = 0, int $depth = 512): ?array
    {
        /* @mago-expect analysis:mixed-assignment - json_decode() is mixed by nature; validated to array below */
        $decoded = json_decode($json, associative: true, depth: $depth, flags: $flags);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Decodes a JSON object into a stdClass.
     *
     * @throws JsonException
     */
    public static function decodeObject(string $json, int $flags = 0, int $depth = 512): ?stdClass
    {
        /* @mago-expect analysis:mixed-assignment - json_decode() is mixed by nature; validated to stdClass below */
        $decoded = json_decode($json, associative: false, depth: $depth, flags: $flags);

        return $decoded instanceof stdClass ? $decoded : null;
    }

    /**
     * Decodes a JSON array whose elements keep their object form (stdClass for nested objects).
     *
     * @return array<array-key, mixed>|null
     * @throws JsonException
     */
    public static function decodeList(string $json, int $flags = 0, int $depth = 512): ?array
    {
        /* @mago-expect analysis:mixed-assignment - json_decode() is mixed by nature; validated to array below */
        $decoded = json_decode($json, associative: false, depth: $depth, flags: $flags);

        return is_array($decoded) ? $decoded : null;
    }
}
