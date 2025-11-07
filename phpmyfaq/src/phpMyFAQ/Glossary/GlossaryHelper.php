<?php

/**
 * The glossary helper class.
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
 * @since     2025-11-05
 */

declare(strict_types=1);

namespace phpMyFAQ\Glossary;

final class GlossaryHelper
{
    /**
     * Extract prefix, item, postfix from regex matches produced by pattern: /(^|\W)(item)(\W|$)/
     * keeping backward-compatibility with older, more verbose patterns.
     *
     * @param array<int, string> $matches
     * @return array{0:string,1:string,2:string} [prefix, item, postfix]
     */
    public function extractMatchParts(array $matches): array
    {
        $prefix = '';
        $item = '';
        $postfix = '';
        $count = count($matches);

        if ($count > 9) {
            $prefix = $matches[9];
            $item = $matches[10];
        }

        if ($item === '' && $count > 7) {
            $item = $matches[7];
            $postfix = $matches[8];
        }

        if ($item === '' && $count > 4) {
            $prefix = $matches[4];
            $item = $matches[5];
            $postfix = $matches[6];
        }

        if ($item === '' && $count >= 3) {
            $prefix = $matches[1] ?? '';
            $item = $matches[2] ?? '';
            $postfix = $matches[3] ?? '';
        }

        return [$prefix, $item, $postfix];
    }

    public function formatTooltip(string $definition, string $item, string $prefix = '', string $postfix = ''): string
    {
        $fmt = '%s<abbr data-bs-toggle="tooltip" data-bs-placement="bottom" title="%s" class="initialism">%s</abbr>%s';
        return sprintf($fmt, $prefix, $definition, $item, $postfix);
    }
}
