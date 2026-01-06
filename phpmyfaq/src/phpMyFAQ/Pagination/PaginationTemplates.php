<?php

/**
 * Immutable template configuration for pagination class.
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
 * @since     2026-01-06
 */

declare(strict_types=1);

namespace phpMyFAQ\Pagination;

readonly class PaginationTemplates
{
    public function __construct(
        public string $link = '<li class="page-item"><a class="page-link" href="{LINK_URL}">{LINK_TEXT}</a></li>',
        public string $currentPage = '<li class="page-item active"><a class="page-link" href="{LINK_URL}">{LINK_TEXT}</a></li>',
        public string $nextPage = '<li class="page-item"><a class="page-link" href="{LINK_URL}">&rarr;</a></li>',
        public string $prevPage = '<li class="page-item"><a class="page-link" href="{LINK_URL}">&larr;</a></li>',
        public string $firstPage = '<li class="page-item"><a class="page-link" href="{LINK_URL}">&#8676;</a></li>',
        public string $lastPage = '<li class="page-item"><a class="page-link" href="{LINK_URL}">&#8677;</a></li>',
        public string $layout = '<ul class="pagination justify-content-center">{LAYOUT_CONTENT}</ul>',
    ) {
    }

    public static function default(): self
    {
        return new self();
    }
}
