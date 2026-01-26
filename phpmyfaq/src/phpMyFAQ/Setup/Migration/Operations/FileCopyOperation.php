<?php

/**
 * File copy operation for migrations.
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

readonly class FileCopyOperation implements OperationInterface
{
    /**
     * Create a file copy operation with a source, destination, and optional existence check.
     *
     * @param string $source Path of the source file to copy.
     * @param string $destination Path where the file should be copied to.
     * @param bool $onlyIfExists If true, skip the copy when the source does not exist (default true).
     */
    public function __construct(
        private Filesystem $filesystem,
        private string $source,
        private string $destination,
        private bool $onlyIfExists = true,
    ) {
    }

    /**
     * Get the operation type identifier.
     *
     * @return string The operation type identifier 'file_copy'.
     */
    public function getType(): string
    {
        return 'file_copy';
    }

    /**
     * Provide a human-readable description of the file copy operation.
     *
     * @return string A description in the form "Copy file: <source> -> <destination>" where each path is shortened relative to PMF_ROOT_DIR when applicable.
     */
    public function getDescription(): string
    {
        $sourceShort = $this->shortenPath($this->source);
        $destShort = $this->shortenPath($this->destination);
        return sprintf('Copy file: %s -> %s', $sourceShort, $destShort);
    }

    /**
     * Get the source file path for the copy operation.
     *
     * @return string The source file path.
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get the destination path for the file copy operation.
     *
     * @return string The destination path where the file should be copied.
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * Executes the file copy operation from the configured source to destination.
     *
     * If `onlyIfExists` is true and the source file does not exist, the operation is skipped.
     *
     * @return bool `true` if the file was copied or the operation was skipped due to a missing source, `false` if the copy failed.
     */
    public function execute(): bool
    {
        if ($this->onlyIfExists && !file_exists($this->source)) {
            return true; // Skip if a source doesn't exist and onlyIfExists is true
        }

        try {
            $this->filesystem->copy($this->source, $this->destination);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Convert the operation into an associative array of its public properties and metadata.
     *
     * @return array{type:string,description:string,source:string,destination:string,onlyIfExists:bool} Associative array containing:
     *     - type: operation type identifier
     *     - description: human-readable description
     *     - source: source file path
     *     - destination: destination file path
     *     - onlyIfExists: whether the operation should be skipped when the source is missing
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
     * Shortens a filesystem path for display by removing the PMF_ROOT_DIR prefix when defined.
     *
     * @param string $path The original filesystem path.
     * @return string The path with PMF_ROOT_DIR removed if it was defined and present, otherwise the original path.
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