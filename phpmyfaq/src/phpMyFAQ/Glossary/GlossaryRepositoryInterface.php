<?php

/**
 * The glossary repository interface.
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

interface GlossaryRepositoryInterface
{
    /**
     * @return array<int, array{id:int, language:string, item:string, definition:string}>
     */
    public function fetchAll(string $language): array;

    /**
     * @return array{id:int, language:string, item:string, definition:string}|array{}
     */
    public function fetch(int $id, string $language): array;

    public function create(string $language, string $item, string $definition): bool;

    public function update(int $id, string $language, string $item, string $definition): bool;

    public function delete(int $id, string $language): bool;
}
