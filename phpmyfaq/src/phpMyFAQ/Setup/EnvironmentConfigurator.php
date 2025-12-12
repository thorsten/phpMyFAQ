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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use SplFileObject;
use Tivie\HtaccessParser\Exception\SyntaxException;
use Tivie\HtaccessParser\Parser;

use const Tivie\HtaccessParser\Token\TOKEN_DIRECTIVE;

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
        return parse_url($this->configuration->getDefaultUrl(), PHP_URL_PATH);
    }

    /**
     * @throws Exception
     */
    public function getRewriteBase(): string
    {
        $file = new SplFileObject($this->htaccessPath);
        $parser = new Parser();
        try {
            $htaccess = $parser->parse($file);
        } catch (SyntaxException $e) {
            throw new Exception('Syntax error in .htaccess file: ' . $e->getMessage());
        } catch (\Tivie\HtaccessParser\Exception\Exception $e) {
            throw new Exception('Error parsing .htaccess file: ' . $e->getMessage());
        }

        $rewriteBase = $htaccess->search('RewriteBase', TOKEN_DIRECTIVE);

        return $rewriteBase->getArguments()[0];
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
     * @throws Exception If the .htaccess file does not exist or contains syntax errors during parsing.
     */
    public function adjustRewriteBaseHtaccess(): bool
    {
        if (!file_exists($this->htaccessPath)) {
            throw new Exception(sprintf('The %s/.htaccess file does not exist!', $this->getServerPath()));
        }

        $file = new SplFileObject($this->htaccessPath);
        $parser = new Parser();
        try {
            $htaccess = $parser->parse($file);
        } catch (SyntaxException $e) {
            throw new Exception('Syntax error in .htaccess file: ' . $e->getMessage());
        } catch (\Tivie\HtaccessParser\Exception\Exception $e) {
            throw new Exception('Error parsing .htaccess file: ' . $e->getMessage());
        }

        // Adjust RewriteBase
        $rewriteBase = $htaccess->search('RewriteBase', TOKEN_DIRECTIVE);
        if ($rewriteBase) {
            $rewriteBase->removeArgument($this->getRewriteBase());
            $rewriteBase->setArguments((array) $this->getServerPath());
        }

        // Adjust ErrorDocument 404
        $errorDocument404 = $htaccess->search('ErrorDocument', TOKEN_DIRECTIVE);
        if ($errorDocument404) {
            // Get current arguments and clear them
            $currentArgs = $errorDocument404->getArguments();
            foreach ($currentArgs as $arg) {
                $errorDocument404->removeArgument($arg);
            }
            // Set new arguments: error code and path
            $new404Path = rtrim($this->getServerPath(), '/') . '/index.php?action=404';
            $errorDocument404->setArguments(['404', $new404Path]);
        }

        $output = (string) $htaccess;
        return (bool) file_put_contents($this->htaccessPath, $output);
    }
}
