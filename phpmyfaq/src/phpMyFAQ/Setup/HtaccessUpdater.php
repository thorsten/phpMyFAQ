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
     * all other user-generated content
     *
     * @throws Exception
     */
    public function updateRewriteBase(string $htaccessPath, string $newBasePath): bool
    {
        if (!file_exists($htaccessPath)) {
            throw new Exception('The .htaccess file does not exist at: ' . $htaccessPath);
        }

        // Create backup before modification
        $this->createBackup($htaccessPath);

        // Read the file content
        $content = file_get_contents($htaccessPath);
        if ($content === false) {
            throw new Exception('Failed to read .htaccess file');
        }

        // Ensure the base path has proper format
        $newBasePath = rtrim($newBasePath, '/') . '/';
        if ($newBasePath === '/') {
            $newBasePath = '/';
        }

        // Use regex to find and replace the RewriteBase directive
        // This pattern matches the RewriteBase line while preserving comments and formatting
        $pattern = '/^(\s*RewriteBase\s+)(.*)$/m';
        $replacement = '${1}' . $newBasePath;

        $updatedContent = preg_replace($pattern, $replacement, $content);

        if ($updatedContent === null) {
            throw new Exception('Failed to update RewriteBase directive');
        }

        // If no RewriteBase was found, add it after the RewriteEngine directive
        if ($updatedContent === $content) {
            $updatedContent = $this->addRewriteBase($content, $newBasePath);
        }

        // Write the updated content back to the file
        if (file_put_contents($htaccessPath, $updatedContent) === false) {
            throw new Exception('Failed to write updated .htaccess file');
        }

        return true;
    }

    /**
     * Adds a RewriteBase directive after the RewriteEngine directive if it doesn't exist
     */
    private function addRewriteBase(string $content, string $basePath): string
    {
        // Look for RewriteEngine directive and add RewriteBase after it
        $pattern = '/^(\s*RewriteEngine\s+On\s*)$/mi';
        $replacement = '$1' . "\n    # the path to your phpMyFAQ installation\n    RewriteBase " . $basePath;

        $updatedContent = preg_replace($pattern, $replacement, $content);

        // If RewriteEngine On was not found, add both directives at the beginning of mod_rewrite section
        if ($updatedContent === $content) {
            $pattern = '/^(\s*<IfModule\s+mod_rewrite\.c>\s*)$/mi';
            $replacement = '$1' . "\n    # This has to be 'On'\n    RewriteEngine On\n    " .
                "# the path to your phpMyFAQ installation\n    RewriteBase " . $basePath;
            $updatedContent = preg_replace($pattern, $replacement, $content);
        }

        return $updatedContent;
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
        $hasModRewrite = strpos($content, '<IfModule mod_rewrite.c>') !== false;
        $hasRewriteEngine = strpos($content, 'RewriteEngine') !== false;
        $hasRewriteBase = strpos($content, 'RewriteBase') !== false;

        return $hasModRewrite && $hasRewriteEngine && $hasRewriteBase;
    }
}
