<?php

/**
 * Storage-backed theme upload and activation manager.
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
 * @since     2026-02-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Template;

use phpMyFAQ\Configuration;
use phpMyFAQ\Storage\StorageInterface;
use RuntimeException;
use ZipArchive;

readonly class ThemeManager
{
    public function __construct(
        private Configuration $configuration,
        private StorageInterface $storage,
        private string $themeRootPath = 'themes',
    ) {
    }

    /**
     * Validates and uploads a ZIP-based theme into the configured storage.
     *
     * @throws RuntimeException
     */
    public function uploadTheme(string $themeName, string $archivePath): int
    {
        $this->assertValidThemeName($themeName);
        $this->assertArchiveIsReadable($archivePath);

        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Theme upload requires the PHP zip extension (ZipArchive).');
        }

        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Failed to open theme archive.');
        }

        $containsIndexTemplate = false;

        /** @var array<string, string> */
        $validatedEntries = [];

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entryName = $zip->getNameIndex($index);
                if ($entryName === false || $entryName === '' || str_ends_with($entryName, '/')) {
                    continue;
                }

                $normalizedEntryPath = $this->normalizeArchivePath($entryName, $themeName);
                if ($normalizedEntryPath === '') {
                    continue;
                }

                $this->assertAllowedFileExtension($normalizedEntryPath);

                $contents = $zip->getFromIndex($index);
                if (!is_string($contents)) {
                    throw new RuntimeException(sprintf('Failed to read archive entry "%s".', $entryName));
                }

                if ($normalizedEntryPath === 'index.twig') {
                    $containsIndexTemplate = true;
                }

                $storagePath = $this->themeStoragePath($themeName, $normalizedEntryPath);
                $validatedEntries[$storagePath] = $contents;
            }
        } finally {
            $zip->close();
        }

        if (!$containsIndexTemplate) {
            throw new RuntimeException('Invalid theme archive: missing required "index.twig".');
        }

        if ($validatedEntries === []) {
            throw new RuntimeException('Theme archive does not contain uploadable files.');
        }

        foreach ($validatedEntries as $storagePath => $contents) {
            $this->storage->put($storagePath, $contents);
        }

        return count($validatedEntries);
    }

    public function activateTheme(string $themeName): bool
    {
        $this->assertValidThemeName($themeName);
        return $this->configuration->set('layout.templateSet', $themeName);
    }

    public function activateDefaultTheme(): bool
    {
        return $this->configuration->set('layout.templateSet', 'default');
    }

    public function getThemeRootPath(): string
    {
        return trim($this->themeRootPath, '/');
    }

    private function themeStoragePath(string $themeName, string $relativePath = ''): string
    {
        $basePath = trim($this->themeRootPath, '/') . '/' . $themeName;
        $relativePath = ltrim($relativePath, '/');

        if ($relativePath === '') {
            return $basePath;
        }

        return $basePath . '/' . $relativePath;
    }

    private function assertValidThemeName(string $themeName): void
    {
        if (!preg_match('/^[A-Za-z0-9_-]{2,64}$/', $themeName)) {
            throw new RuntimeException('Invalid theme name. Allowed: letters, numbers, "_" and "-".');
        }
    }

    private function assertArchiveIsReadable(string $archivePath): void
    {
        if (!is_file($archivePath) || !is_readable($archivePath)) {
            throw new RuntimeException('Theme archive file is missing or not readable.');
        }
    }

    private function normalizeArchivePath(string $entryName, string $themeName): string
    {
        $normalizedPath = str_replace('\\', '/', trim($entryName));
        $normalizedPath = ltrim($normalizedPath, '/');

        // Flatten root folder archives: "mytheme/index.twig" -> "index.twig".
        $pathParts = array_values(array_filter(
            explode('/', $normalizedPath),
            static fn(string $part): bool => $part !== '',
        ));
        if (count($pathParts) > 1 && $pathParts[0] === $themeName) {
            $pathParts = array_slice($pathParts, 1);
        }

        $rebuiltPath = implode('/', $pathParts);
        if ($rebuiltPath === '') {
            return '';
        }

        if (str_contains($rebuiltPath, '..')) {
            throw new RuntimeException('Theme archive contains invalid relative paths.');
        }

        return $rebuiltPath;
    }

    private function assertAllowedFileExtension(string $path): void
    {
        if (!$this->isAllowedFileExtension($path)) {
            throw new RuntimeException(sprintf('Theme file type is not allowed: %s', $path));
        }
    }

    private function isAllowedFileExtension(string $path): bool
    {
        return (bool) preg_match('/\.(twig|css|js|json|png|jpg|jpeg|svg|webp|gif|woff2?|ttf|otf)$/i', $path);
    }
}
