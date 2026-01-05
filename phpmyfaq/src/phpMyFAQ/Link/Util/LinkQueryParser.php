<?php

/**
 * Helper functions for query and fragment parsing from link URLs.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Link\Util;

/**
 * Hilfsfunktionen für Query- und Fragment-Parsing aus Link-URLs.
 */
final class LinkQueryParser
{
    /**
     * @return array<string,string>
     */
    public static function parse(string $url): array
    {
        $parameters = [];
        if ($url === '' || $url === '0') {
            return $parameters;
        }

        $parsed = parse_url($url);
        if (isset($parsed['query'])) {
            $rawQuery = str_replace(['&amp;', '#38;', 'amp;'], '&', $parsed['query']);
            $tmp = [];
            parse_str($rawQuery, $tmp);
            foreach ($tmp as $k => $v) {
                if (!is_scalar($v)) {
                    continue;
                }

                $parameters[(string) $k] = $v;
            }
        }

        if (isset($parsed['fragment'])) {
            $fragment = $parsed['fragment'];
            $parameters['#'] = $fragment; // historisch
            $parameters['fragment'] = $fragment;
        }

        return $parameters;
    }
}
