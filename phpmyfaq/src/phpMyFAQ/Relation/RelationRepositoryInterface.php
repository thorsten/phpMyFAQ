<?php

/**
 * The relation repository interface.
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
 * @since     2025-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Relation;

interface RelationRepositoryInterface
{
    /**
     * Returns all relevant articles for a FAQ record with the same language.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllRelatedByQuestion(string $question, string $keywords, string $language): array;
}
