<?php

/**
 * Category breadcrumb HTML renderer class
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
 * @since     2026-10-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Category\Navigation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;

/**
 * Renders breadcrumb segments as HTML using existing Link + Strings utilities.
 */
final class BreadcrumbsHtmlRenderer
{
    /**
     * @param array<int, array{id:int, name:string, description:string}> $segments
     */
    public function render(Configuration $configuration, array $segments, string $useCssClass = 'breadcrumb'): string
    {
        $items = [];
        foreach ($segments as $index => $segment) {
            $url = strtr(
                string: '{base}index.php?action=show&cat={id}',
                replace_pairs: [
                    '{base}' => $configuration->getDefaultUrl(),
                    '{id}' => (string) $segment['id'],
                ],
            );
            $oLink = new Link($url, $configuration);
            $oLink->text = Strings::htmlentities($segment['name']);
            $oLink->itemTitle = Strings::htmlentities($segment['name']);
            $oLink->tooltip = Strings::htmlentities($segment['description'] ?? '');
            if (0 === $index) {
                $oLink->setRelation(rel: 'index');
            }
            $items[] = sprintf(
                format: '<li class="breadcrumb-item">%s</li>',
                values: $oLink->toHtmlAnchor(),
            );
        }

        return strtr(
            string: '<ul class="{class}">{items}</ul>',
            replace_pairs: [
                '{class}' => $useCssClass,
                '{items}' => implode(
                    separator: '',
                    array: $items,
                ),
            ],
        );
    }
}
