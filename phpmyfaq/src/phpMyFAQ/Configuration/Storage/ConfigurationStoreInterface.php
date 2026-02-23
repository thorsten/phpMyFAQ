<?php

/**
 * Configuration storage interface
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
 * @since     2026-02-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration\Storage;

interface ConfigurationStoreInterface
{
    public function updateConfigValue(string $key, string $value): bool;

    /**
     * @return array<int, object>
     */
    public function fetchAll(): array;

    public function insert(string $name, string $value): bool;

    public function delete(string $name): bool;

    public function renameKey(string $currentKey, string $newKey): bool;
}
