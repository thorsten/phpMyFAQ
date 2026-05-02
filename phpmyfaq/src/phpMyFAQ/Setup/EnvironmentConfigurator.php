<?php

/**
 * The environment configurator is responsible for adjusting the .htaccess file to the user's environment.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;

readonly class EnvironmentConfigurator
{
    private string $htaccessPath;

    public function __construct(
        private Configuration $configuration,
    ) {
        $this->htaccessPath = $this->configuration->getRootPath() . '/.htaccess';
    }

    public function getHtaccessPath(): string
    {
        return $this->htaccessPath;
    }

    public function getServerPath(): string
    {
        $path = parse_url($this->configuration->getDefaultUrl(), PHP_URL_PATH);

        return $path === null || $path === false || $path === '' ? '/' : $path;
    }

    /**
     * @throws Exception
     */
    public function getRewriteBase(): string
    {
        $content = $this->readHtaccess();

        if (preg_match('/^\s*RewriteBase\s+(\S+)/mi', $content, $matches) !== 1) {
            throw new Exception('RewriteBase directive not found in .htaccess file');
        }

        return $matches[1];
    }

    /**
     * Adjusts the RewriteBase and ErrorDocument 404 in the .htaccess file for the user's environment.
     *
     * This method ensures that URL routing works correctly and 404 errors are properly handled.
     *
     * - RewriteBase is set to the application's installation path (e.g., /faq/)
     * - ErrorDocument 404 is configured to route errors to the application's error handler (e.g., /faq/index.php?action=404)
     *
     * @return bool Returns true if the .htaccess file was successfully modified, false otherwise.
     * @throws Exception If the .htaccess file does not exist or cannot be read.
     */
    public function adjustRewriteBaseHtaccess(): bool
    {
        if (!file_exists($this->htaccessPath)) {
            throw new Exception(sprintf('The %s/.htaccess file does not exist!', $this->getServerPath()));
        }

        $content = $this->readHtaccess();

        $serverPath = $this->getServerPath();
        $new404Path = rtrim($serverPath, characters: '/') . '/index.php?action=404';

        $updated = preg_replace('/^(\s*RewriteBase\s+)\S+/mi', '${1}' . $serverPath, $content);
        if ($updated === null) {
            throw new Exception('Failed to update RewriteBase directive');
        }

        $updated = preg_replace('/^(\s*ErrorDocument\s+404\s+)\S+/mi', '${1}' . $new404Path, $updated);
        if ($updated === null) {
            throw new Exception('Failed to update ErrorDocument 404 directive');
        }

        return (bool) file_put_contents($this->htaccessPath, $updated);
    }

    /**
     * @throws Exception
     */
    private function readHtaccess(): string
    {
        $content = @file_get_contents($this->htaccessPath);
        if ($content === false) {
            throw new Exception(sprintf('Cannot read .htaccess file: %s', $this->htaccessPath));
        }

        return $content;
    }
}
