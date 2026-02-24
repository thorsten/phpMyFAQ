<?php

/**
 * Options for paginated API responses
 *
 *  This Source Code Form is subject to the terms of the Mozilla Public License,
 *  v. 2.0. If a copy of the MPL was not distributed with this file, You can
 *  obtain one at https://mozilla.org/MPL/2.0/.
 *
 *  @package   phpMyFAQ
 *  @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 *  @copyright 2026 phpMyFAQ Team
 *  @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *  @link      https://www.phpmyfaq.de
 *  @since     2026-02-24
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Api\Filtering\FilterRequest;
use phpMyFAQ\Api\Sorting\SortRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class PaginatedResponseOptions
{
    public function __construct(
        public ?SortRequest $sort = null,
        public ?FilterRequest $filters = null,
        public int $status = Response::HTTP_OK,
    ) {
    }
}
