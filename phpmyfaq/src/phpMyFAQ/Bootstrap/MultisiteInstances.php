<?php

/**
 * Tells whether an installation serves multisite instances.
 *
 * phpMyFAQ selects the instance of a request through PHP constants, and a constant keeps its first
 * value for the lifetime of the process. A long-running worker would therefore answer every host
 * with the database and attachments of the instance that received its first request, which is why
 * worker mode is not supported with multisite.
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
 * @since     2026-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Bootstrap;

final readonly class MultisiteInstances
{
    public function __construct(
        private string $multisiteDirectory,
    ) {
    }

    /**
     * An instance is a directory next to multisite.php that holds the configuration of one host.
     */
    public function exist(): bool
    {
        if (!is_file($this->multisiteDirectory . '/multisite.php')) {
            return false;
        }

        return $this->configDirectories() !== [];
    }

    /**
     * The config directory of every instance, whether or not the instance is installed yet.
     *
     * @return list<string>
     */
    public function configDirectories(): array
    {
        if (!is_dir($this->multisiteDirectory)) {
            return [];
        }

        $entries = scandir($this->multisiteDirectory);
        if ($entries === false) {
            return [];
        }

        $configDirectories = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $configDirectory = $this->multisiteDirectory . '/' . $entry;
            if (is_dir($configDirectory)) {
                $configDirectories[] = $configDirectory;
            }
        }

        return $configDirectories;
    }
}
