<?php

/**
 * News repository interface
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
 * @since     2025-11-09
 */

declare(strict_types=1);

namespace phpMyFAQ\News;

use phpMyFAQ\Entity\NewsMessage;
use stdClass;

/**
 * Contract for News repositories.
 */
interface NewsRepositoryInterface
{
    /**
     * @return iterable<stdClass>
     */
    public function getLatest(string $language, bool $active = true, ?int $limit = null): iterable;

    /**
     * @return iterable<stdClass>
     */
    public function getHeaders(string $language): iterable;

    public function getById(int $newsId, string $language): ?stdClass;

    public function insert(NewsMessage $newsMessage): bool;

    public function update(NewsMessage $newsMessage): bool;

    public function delete(int $newsId, string $language): bool;

    public function activate(int $newsId, bool $status): bool;
}
