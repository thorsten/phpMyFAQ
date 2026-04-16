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
use Tivie\HtaccessParser\Exception\SyntaxException;
use Tivie\HtaccessParser\HtaccessContainer;
use Tivie\HtaccessParser\Parser;
use Tivie\HtaccessParser\Token\Block;
use Tivie\HtaccessParser\Token\Directive;

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
        $path = parse_url($this->configuration->getDefaultUrl(), PHP_URL_PATH);

        return $path === null || $path === false || $path === '' ? '/' : $path;
    }

    /**
     * @throws Exception
     */
    public function getRewriteBase(): string
    {
        $htaccess = $this->parseHtaccess();

        $rewriteBase = $htaccess->search('RewriteBase', TOKEN_DIRECTIVE);

        return $rewriteBase->getArguments()[0];
    }

    /**
     * Adjusts the RewriteBase and ErrorDocument 404 in the .htaccess file for the user's environment.
     *
     * This method ensures that URL routing works correctly and 404 errors are properly handled.
     *
     * - RewriteBase is set to the application's installation path (e.g., /faq/)
     * - ErrorDocument 404 is configured to route errors to the application's error handler (e.g., /404.html)
     *
     * @return bool Returns true if the .htaccess file was successfully modified, false otherwise.
     * @throws Exception If the .htaccess file does not exist or contains syntax errors during parsing.
     */
    public function adjustRewriteBaseHtaccess(): bool
    {
        if (!file_exists($this->htaccessPath)) {
            throw new Exception(sprintf('The %s/.htaccess file does not exist!', $this->getServerPath()));
        }

        $htaccess = $this->parseHtaccess();

        // Adjust RewriteBase
        $rewriteBase = $htaccess->search('RewriteBase', TOKEN_DIRECTIVE);
        if ($rewriteBase) {
            $currentArgs = $rewriteBase->getArguments();
            foreach ($currentArgs as $arg) {
                $rewriteBase->removeArgument($arg);
            }
            $rewriteBase->setArguments([$this->getServerPath()]);
        }

        // Adjust ErrorDocument 404 (filter by error code; user .htaccess may contain
        // additional ErrorDocument directives for other codes such as 403 or 500)
        $errorDocument404 = $this->findErrorDocument($htaccess, '404');
        if ($errorDocument404 instanceof Directive) {
            $currentArgs = $errorDocument404->getArguments();
            foreach ($currentArgs as $currentArg) {
                $errorDocument404->removeArgument($currentArg);
            }
            $new404Path = rtrim($this->getServerPath(), characters: '/') . '/index.php?action=404';
            $errorDocument404->setArguments(['404', $new404Path]);
        }

        $output = (string) $htaccess;
        return (bool) file_put_contents($this->htaccessPath, $output);
    }

    /**
     * Parses the .htaccess file using the Tivie parser.
     *
     * The content is normalized (line endings, trailing newline) and wrapped in an
     * in-memory SplFileObject whose valid() method is aligned with eof(). This avoids
     * a SplFileObject iterator/fgets desync that causes the parser to throw
     * "Cannot read from file" under PHP 8.6 when the built-in iterator state does
     * not match what getCurrentLine() (a thin fgets wrapper) expects.
     *
     * @throws Exception
     */
    private function parseHtaccess(): HtaccessContainer
    {
        if (!is_readable($this->htaccessPath)) {
            throw new Exception(sprintf('Cannot read .htaccess file at %s', $this->htaccessPath));
        }

        $content = file_get_contents($this->htaccessPath);
        if ($content === false) {
            throw new Exception(sprintf('Cannot read .htaccess file at %s', $this->htaccessPath));
        }

        $content = str_replace(search: ["\r\n", "\r"], replace: "\n", subject: $content);
        if ($content !== '' && !str_ends_with($content, needle: "\n")) {
            $content .= "\n";
        }

        $file = new InMemoryHtaccessFile();
        $file->fwrite($content);
        $file->rewind();

        $parser = new Parser();
        try {
            /** @var HtaccessContainer $htaccess */
            return $parser->parse($file);
        } catch (SyntaxException $e) {
            throw new Exception('Syntax error in .htaccess file: ' . $e->getMessage());
        } catch (\Tivie\HtaccessParser\Exception\Exception $e) {
            throw new Exception('Error parsing .htaccess file: ' . $e->getMessage());
        }
    }

    /**
     * Recursively searches the parsed .htaccess tree for an ErrorDocument directive
     * whose first argument matches the given HTTP error code.
     */
    private function findErrorDocument(iterable $container, string $errorCode): ?Directive
    {
        foreach ($container as $token) {
            if (
                $token instanceof Directive
                && $token->getName() === 'ErrorDocument'
                && ($token->getArguments()[0] ?? null) === $errorCode
            ) {
                return $token;
            }

            if ($token instanceof Block && $token->hasChildren()) {
                $found = $this->findErrorDocument($token, $errorCode);
                if ($found instanceof Directive) {
                    return $found;
                }
            }
        }

        return null;
    }
}
