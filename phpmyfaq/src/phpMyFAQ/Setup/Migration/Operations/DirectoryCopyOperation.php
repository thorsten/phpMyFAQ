<?php

/**
 * Directory copy operation for migrations.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Filesystem\Filesystem;

readonly class DirectoryCopyOperation implements OperationInterface
{
    public function __construct(
        private Filesystem $filesystem,
        private string $source,
        private string $destination,
        private bool $onlyIfExists = true,
    ) {
    }

    public function getType(): string
    {
        return 'directory_copy';
    }

    public function getDescription(): string
    {
        $sourceShort = $this->shortenPath($this->source);
        $destShort = $this->shortenPath($this->destination);
        return sprintf('Copy directory: %s -> %s', $sourceShort, $destShort);
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function execute(): bool
    {
        if ($this->onlyIfExists && !is_dir($this->source)) {
            return true; // Skip if a source doesn't exist and onlyIfExists is true
        }

        try {
            return $this->filesystem->recursiveCopy($this->source, $this->destination);
        } catch (\Exception) {
            return false;
        }
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'source' => $this->source,
            'destination' => $this->destination,
            'onlyIfExists' => $this->onlyIfExists,
        ];
    }

    private function shortenPath(string $path): string
    {
        // Remove common prefixes to shorten the path for display
        if (defined('PMF_ROOT_DIR')) {
            $path = str_replace(PMF_ROOT_DIR, '', $path);
        }
        return $path;
    }
}
