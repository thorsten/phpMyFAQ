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
    /**
     * Create a DirectoryCopyOperation configured to copy a directory from the given source to the given destination.
     *
     * @param Filesystem $filesystem The filesystem used to perform the recursive copy.
     * @param string $source The source directory path to copy.
     * @param string $destination The destination directory path.
     * @param bool $onlyIfExists Whether to skip execution when the source directory does not exist (default true).
     */
    public function __construct(
        private Filesystem $filesystem,
        private string $source,
        private string $destination,
        private bool $onlyIfExists = true,
    ) {
    }

    /**
     * Return the operation's type identifier.
     *
     * @return string The operation type identifier 'directory_copy'.
     */
    public function getType(): string
    {
        return 'directory_copy';
    }

    /**
     * Build a human-friendly description of this directory copy operation.
     *
     * @return string A description in the form "Copy directory: {source} -> {destination}" where source and destination paths are shortened for display.
     */
    public function getDescription(): string
    {
        $sourceShort = $this->shortenPath($this->source);
        $destShort = $this->shortenPath($this->destination);
        return sprintf('Copy directory: %s -> %s', $sourceShort, $destShort);
    }

    /**
     * Get the configured source directory path for the operation.
     *
     * @return string The source directory path.
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get the configured destination path for the directory copy.
     *
     * @return string The destination filesystem path.
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * Executes the directory copy operation.
     *
     * If configured to run only when the source exists, the method returns `true` without performing any action when the source is not a directory. Otherwise it attempts a recursive copy from source to destination.
     *
     * @return bool `true` on success or when skipped due to a missing source while onlyIfExists is true, `false` on failure.
     */
    public function execute(): bool
    {
        if ($this->onlyIfExists && !is_dir($this->source)) {
            return true; // Skip if a source doesn't exist and onlyIfExists is true
        }

        try {
            $this->filesystem->recursiveCopy($this->source, $this->destination);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Serialize the operation into an associative array representation.
     *
     * The array contains keys describing the operation and its configuration:
     * - `type`: operation type identifier
     * - `description`: human-friendly description
     * - `source`: source directory path
     * - `destination`: destination directory path
     * - `onlyIfExists`: whether the operation should be skipped when the source does not exist
     *
     * @return array{type:string,description:string,source:string,destination:string,onlyIfExists:bool}
     */
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

    /**
     * Shortens a filesystem path for display by removing the PMF root directory prefix if defined.
     *
     * @param string $path The original filesystem path.
     * @return string The possibly shortened path with the PMF root prefix removed when applicable.
     */
    private function shortenPath(string $path): string
    {
        // Remove common prefixes to shorten the path for display
        if (defined('PMF_ROOT_DIR')) {
            $path = str_replace(PMF_ROOT_DIR, '', $path);
        }
        return $path;
    }
}