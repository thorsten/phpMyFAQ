<?php

/**
 * Discovers plugin classes, optionally backed by a manifest cache.
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
 * @since     2026-07-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Plugin;

/**
 * Class PluginDiscovery
 *
 * Maps plugin files to their fully qualified class names. Without a cache
 * file every call scans the plugin directory and parses each plugin file for
 * its namespace. With a cache file, the resulting class map is stored as a
 * PHP manifest and reused as long as the directory signature (plugin root and
 * per-plugin directory mtimes) is unchanged — so installing or removing a
 * plugin invalidates the manifest automatically, without a manual cache
 * clear. Editing the namespace inside an existing plugin file does not bump
 * a directory mtime; that is a development workflow, where callers should
 * pass no cache file.
 */
final class PluginDiscovery
{
    public function __construct(
        private readonly string $pluginDir,
        private readonly ?string $cacheFile = null,
    ) {
    }

    /**
     * @return array<string, string> plugin file path => fully qualified class name
     */
    public function getClassMap(): array
    {
        if ($this->cacheFile === null) {
            return $this->scan();
        }

        $signature = $this->computeSignature();
        $manifest = $this->readManifest();
        if ($manifest !== null && $manifest['signature'] === $signature) {
            return $manifest['classMap'];
        }

        $classMap = $this->scan();
        $this->writeManifest($signature, $classMap);

        return $classMap;
    }

    /**
     * @return array<string, string>
     */
    private function scan(): array
    {
        $classMap = [];
        $pluginFiles = glob($this->normalizedPluginDir() . '/*/*Plugin.php');

        foreach ($pluginFiles === false ? [] : $pluginFiles as $pluginFile) {
            $className = basename(path: $pluginFile, suffix: '.php');
            $namespace = $this->getNamespaceFromFile($pluginFile) ?? '';
            $classMap[$pluginFile] = $namespace . '\\' . $className;
        }

        return $classMap;
    }

    /**
     * Signature over the plugin root and each plugin directory mtime.
     */
    private function computeSignature(): string
    {
        $pluginDir = $this->normalizedPluginDir();
        $parts = [$pluginDir => $this->modificationTime($pluginDir)];

        $subDirectories = glob($pluginDir . '/*', GLOB_ONLYDIR);
        foreach ($subDirectories === false ? [] : $subDirectories as $subDirectory) {
            $parts[$subDirectory] = $this->modificationTime($subDirectory);
        }

        return md5(serialize($parts));
    }

    /**
     * @return array{signature: string, classMap: array<string, string>}|null
     */
    private function readManifest(): ?array
    {
        if ($this->cacheFile === null || !is_file($this->cacheFile)) {
            return null;
        }

        /** @var mixed $manifest */
        $manifest = include $this->cacheFile;
        if (
            !is_array($manifest)
            || !is_string($manifest['signature'] ?? null)
            || !is_array($manifest['classMap'] ?? null)
        ) {
            return null;
        }

        $classMap = [];
        /** @var mixed $className */
        foreach ($manifest['classMap'] as $pluginFile => $className) {
            if (!is_string($pluginFile) || !is_string($className)) {
                return null;
            }

            $classMap[$pluginFile] = $className;
        }

        return ['signature' => $manifest['signature'], 'classMap' => $classMap];
    }

    /**
     * @param array<string, string> $classMap
     */
    private function writeManifest(string $signature, array $classMap): void
    {
        if ($this->cacheFile === null) {
            return;
        }

        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir(directory: $cacheDir, permissions: 0o755, recursive: true);
        }

        $manifest = ['signature' => $signature, 'classMap' => $classMap];
        $payload = "<?php\n\nreturn " . var_export($manifest, return: true) . ";\n";
        file_put_contents($this->cacheFile, $payload, LOCK_EX);
    }

    private function getNamespaceFromFile(string $file): ?string
    {
        $src = file_get_contents($file);
        $matches = [];
        if ($src !== false && preg_match('/^namespace\s+(.+?);/m', $src, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function normalizedPluginDir(): string
    {
        return rtrim(string: $this->pluginDir, characters: '/');
    }

    private function modificationTime(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $modificationTime = filemtime($directory);

        return $modificationTime === false ? 0 : $modificationTime;
    }
}
