<?php

/**
 * Building Category overview URL segments for phpMyFAQ Link rewriting.
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

namespace phpMyFAQ\Link\Strategy;

use phpMyFAQ\Link;

final class ShowStrategy implements StrategyInterface
{
    /**
     * @param array<string,string> $params
     */
    public function build(array $params, Link $link): string
    {
        $cat = $params[Link::LINK_GET_CATEGORY] ?? 0;
        if ($cat === 0 || $cat === '0') {
            return Link::LINK_HTML_SHOW_CATEGORIES;
        }
        $url = Link::LINK_CATEGORY . $cat;
        if (isset($params[Link::LINK_GET_PAGE])) {
            $url .= Link::LINK_HTML_SLASH . $params[Link::LINK_GET_PAGE];
        }
        return $url . Link::LINK_HTML_SLASH . $link->getSEOTitle() . Link::LINK_HTML_EXTENSION;
    }
}
