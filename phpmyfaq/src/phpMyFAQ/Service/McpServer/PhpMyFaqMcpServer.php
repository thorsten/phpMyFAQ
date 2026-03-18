<?php

/**
 * phpMyFAQ MCP Server
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
 * @since     2025-08-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Service\McpServer;

use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Search;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PhpMyFaqMcpServer
 *
 * Main MCP server class for phpMyFAQ that sets up and runs the Model Context Protocol server
 * with FAQ search capabilities. This allows LLM models to query the phpMyFAQ knowledge base
 * through the MCP protocol.
 */
class PhpMyFaqMcpServer implements McpServerRuntimeInterface
{
    private McpServerRuntimeInterface $runtime;

    private const string MCP_SERVER_NAME = 'phpMyFAQ MCP Server';

    private const string MCP_SERVER_VERSION = '0.1.0-dev';

    public function __construct(
        private readonly Configuration $configuration,
        Language $language,
        private readonly Search $search,
        private readonly Faq $faq,
        ?McpServerRuntimeInterface $runtime = null,
    ) {
        $detectionEnabled = (bool) $this->configuration->get(item: 'main.languageDetection');
        $configLang = (string) $this->configuration->get(item: 'main.language');
        if ($detectionEnabled) {
            $language->setLanguageWithDetection($configLang);
            $this->configuration->setLanguage($language);
            $this->initializeServer($runtime);
            return;
        }

        $language->setLanguageFromConfiguration($configLang);
        $this->configuration->setLanguage($language);

        $this->initializeServer($runtime);
    }

    private function initializeServer(?McpServerRuntimeInterface $runtime = null): void
    {
        $this->runtime = $runtime ?? new McpSdkRuntime(
            $this->configuration,
            new FaqSearchTool($this->configuration, $this->search, $this->faq),
            $this->createServerInfo(),
        );
    }

    /**
     * Run the MCP server with console transport
     */
    public function runConsole(InputInterface $input, OutputInterface $output): void
    {
        $this->runtime->runConsole($input, $output);
    }

    /**
     * Get server information for debugging
     */
    public function getServerInfo(): array
    {
        return $this->runtime->getServerInfo();
    }

    /**
     * @return array<string, mixed>
     */
    private function createServerInfo(): array
    {
        return [
            'name' => self::MCP_SERVER_NAME,
            'version' => self::MCP_SERVER_VERSION,
            'description' => 'Model Context Protocol server for phpMyFAQ installations',
            'capabilities' => [
                'tools' => true,
            ],
            'tools' => [
                [
                    'name' => 'faq_search',
                    'description' => 'Search through phpMyFAQ installations',
                ],
            ],
        ];
    }
}
