<?php

/**
 * The HtaccessUpdater class provides surgical updates to .htaccess files
 * while preserving user-generated content.
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
 * @since     2025-01-01
 */

namespace phpMyFAQ\Setup;

use phpMyFAQ\Core\Exception;

class HtaccessUpdater
{
    /**
     * Creates a backup of the .htaccess file before modification
     *
     * @throws Exception
     */
    public function createBackup(string $htaccessPath): string
    {
        if (!file_exists($htaccessPath)) {
            throw new Exception('The .htaccess file does not exist at: ' . $htaccessPath);
        }

        $backupPath = $htaccessPath . '.backup-' . date('Y-m-d-H-i-s');
        if (!copy($htaccessPath, $backupPath)) {
            throw new Exception('Failed to create backup of .htaccess file');
        }

        return $backupPath;
    }

    /**
     * Surgically updates the RewriteBase directive in .htaccess while preserving
     * all other user-generated content. Idempotent and backup only on change.
     *
     * @throws Exception
     */
    public function updateRewriteBase(string $htaccessPath, string $newBasePath): bool
    {
        if (!file_exists($htaccessPath)) {
            throw new Exception('The .htaccess file does not exist at: ' . $htaccessPath);
        }

        $content = file_get_contents($htaccessPath);
        if ($content === false) {
            throw new Exception('Failed to read .htaccess file');
        }

        // Normalize base path: ensure leading slash and a single trailing slash, '/' stays '/'
        $trimmed = trim($newBasePath);
        $trimmed = trim($trimmed, "/\t\n\r\0\x0B");
        $newBasePath = $trimmed === '' ? '/' : ('/' . trim($trimmed, '/'));
        if ($newBasePath !== '/') {
            $newBasePath .= '/';
        }

        // No-op if an equivalent RewriteBase already exists (with or without trailing slash, optional comment)
        $equivalentBase = rtrim($newBasePath, '/');
        $equivalentRegex = '/^\s*RewriteBase\s+' . preg_quote($equivalentBase, '/') . '\/?(?:\s*(?:#.*)?)?\s*$/mi';
        if (preg_match($equivalentRegex, $content) === 1) {
            return true; // unchanged, avoid backup
        }

        // Replace existing RewriteBase occurrences, if any
        $pattern = '/^(\s*RewriteBase\s+)([^\s#]+)(.*)$/mi';
        $replacement = '${1}' . $newBasePath . '${3}';
        $replaceCount = 0;
        $updatedContent = preg_replace($pattern, $replacement, $content, -1, $replaceCount);
        if ($updatedContent === null) {
            throw new Exception('Failed to update RewriteBase directive');
        }

        // If no RewriteBase existed, insert it once in a sensible location
        if ($replaceCount === 0) {
            $updatedContent = $this->addRewriteBase($content, $newBasePath);
        }

        // Still no change? Nothing to do.
        if ($updatedContent === $content) {
            return true;
        }

        // Create backup only when we actually change the file
        $this->createBackup($htaccessPath);

        if (file_put_contents($htaccessPath, $updatedContent) === false) {
            throw new Exception('Failed to write updated .htaccess file');
        }

        return true;
    }

    /**
     * Adds a RewriteBase directive after the first "RewriteEngine On" or inside the first mod_rewrite block.
     * Ensures we insert at most one occurrence.
     */
    private function addRewriteBase(string $content, string $basePath): string
    {
        // Insert once after the first RewriteEngine On
        $pattern = '/^(\s*RewriteEngine\s+On\s*)$/mi';
        $replacement = '$1' . "\n    # the path to your phpMyFAQ installation\n    RewriteBase " . $basePath;
        $count = 0;
        $updated = preg_replace($pattern, $replacement, $content, 1, $count);

        if ($count > 0) {
            return $updated;
        }

        // Otherwise, insert into the first <IfModule mod_rewrite.c> block
        $pattern = '/^(\s*<IfModule\s+mod_rewrite\.c>\s*)$/mi';
        $replacement = '$1' . "\n    # This has to be 'On'\n    RewriteEngine On\n    " .
            "# the path to your phpMyFAQ installation\n    RewriteBase " . $basePath;
        $updated = preg_replace($pattern, $replacement, $content, 1, $count);

        if ($count > 0) {
            return $updated;
        }

        // Last resort: append a minimal block
        return rtrim($content) . "\n\n<IfModule mod_rewrite.c>\n    # This has to be 'On'\n    RewriteEngine On\n    " .
            "# the path to your phpMyFAQ installation\n    RewriteBase " . $basePath . "\n</IfModule>\n";
    }

    /**
     * Validates that the .htaccess file has a proper structure after update
     */
    public function validateHtaccessStructure(string $htaccessPath): bool
    {
        if (!file_exists($htaccessPath)) {
            return false;
        }

        $content = file_get_contents($htaccessPath);
        if ($content === false) {
            return false;
        }

        // Check if it contains essential directives
        $hasModRewrite = str_contains($content, '<IfModule mod_rewrite.c>');
        $hasRewriteEngine = str_contains($content, 'RewriteEngine');
        $hasRewriteBase = str_contains($content, 'RewriteBase');

        return $hasModRewrite && $hasRewriteEngine && $hasRewriteBase;
    }
}
