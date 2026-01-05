<?php

/**
 * Building search URL segments for phpMyFAQ Link rewriting.
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

final class SearchStrategy implements StrategyInterface
{
    /**
     * @param array<string,string> $params
     */
    public function build(array $params, Link $link): string
    {
        $hasSearch = isset($params[Link::LINK_GET_ACTION_SEARCH]);
        $isTag = !$hasSearch && isset($params[Link::LINK_GET_TAGGING_ID]);

        if ($isTag) {
            $url = Link::LINK_TAGS . $params[Link::LINK_GET_TAGGING_ID];
            if (isset($params[Link::LINK_GET_PAGE])) {
                $url .= Link::LINK_HTML_SLASH . $params[Link::LINK_GET_PAGE];
            }

            $url .= Link::LINK_SLASH . $link->getSEOTitle() . Link::LINK_HTML_EXTENSION;
        } else {
            $url = Link::LINK_HTML_SEARCH;
            if ($hasSearch) {
                $url .=
                    Link::LINK_SEARCHPART_SEPARATOR
                    . Link::LINK_GET_ACTION_SEARCH
                    . '='
                    . $params[Link::LINK_GET_ACTION_SEARCH];
                if (isset($params[Link::LINK_GET_PAGE])) {
                    $url .= Link::LINK_AMPERSAND . Link::LINK_GET_PAGE . '=' . $params[Link::LINK_GET_PAGE];
                }
            }
        }

        if (isset($params[Link::LINK_GET_LANGS])) {
            $url .= Link::LINK_AMPERSAND . Link::LINK_GET_LANGS . '=' . $params[Link::LINK_GET_LANGS];
        }

        return $url;
    }
}
