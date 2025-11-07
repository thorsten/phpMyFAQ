<?php

declare(strict_types=1);

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

namespace phpMyFAQ\Service\McpServer;

use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Search;
use Symfony\AI\McpSdk\Capability\ToolChain;
use Symfony\AI\McpSdk\Message\Factory;
use Symfony\AI\McpSdk\Server;
use Symfony\AI\McpSdk\Server\JsonRpcHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\InitializeHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\ToolCallHandler;
use Symfony\AI\McpSdk\Server\RequestHandler\ToolListHandler;
use Symfony\AI\McpSdk\Server\Transport\Stdio\SymfonyConsoleTransport;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PhpMyFaqMcpServer
 *
 * Main MCP server class for phpMyFAQ that sets up and runs the Model Context Protocol server
 * with FAQ search capabilities. This allows LLM models to query the phpMyFAQ knowledge base
 * through the MCP protocol.
 */
class PhpMyFaqMcpServer
{
    private JsonRpcHandler $jsonRpcHandler;

    private const string MCP_SERVER_NAME = 'phpMyFAQ MCP Server';

    private const string MCP_SERVER_VERSION = '0.1.0-dev';

    public function __construct(
        private readonly Configuration $configuration,
        Language $language,
        private readonly Search $search,
        private readonly Faq $faq,
    ) {
        $detectionEnabled = (bool) $this->configuration->get(item: 'main.languageDetection');
        $configLang = (string) $this->configuration->get(item: 'main.language');
        if ($detectionEnabled) {
            $language->setLanguageWithDetection($configLang);
            $this->configuration->setLanguage($language);
            $this->initializeServer();
            return;
        }
        $language->setLanguageFromConfiguration($configLang);
        $this->configuration->setLanguage($language);

        $this->initializeServer();
    }

    private function initializeServer(): void
    {
        $toolChain = new ToolChain([
            new FaqSearchToolMetadata(),
            new FaqSearchToolExecutor($this->configuration, $this->search, $this->faq),
        ]);

        $messageFactory = new Factory();

        $requestHandlers = [
            new InitializeHandler(self::MCP_SERVER_NAME, self::MCP_SERVER_VERSION),
            new ToolListHandler($toolChain),
            new ToolCallHandler($toolChain),
        ];

        $notificationHandlers = [];

        $this->jsonRpcHandler = new JsonRpcHandler(
            $messageFactory,
            $requestHandlers,
            $notificationHandlers,
            $this->configuration->getLogger(),
        );
    }

    /**
     * Run the MCP server with console transport
     */
    public function runConsole(InputInterface $input, OutputInterface $output): void
    {
        $symfonyConsoleTransport = new SymfonyConsoleTransport($input, $output);
        $server = new Server($this->jsonRpcHandler, $this->configuration->getLogger());
        $server->connect($symfonyConsoleTransport);
    }

    /**
     * Get the configured JSON-RPC handler
     */
    public function getJsonRpcHandler(): JsonRpcHandler
    {
        return $this->jsonRpcHandler;
    }

    /**
     * Get server information for debugging
     */
    public function getServerInfo(): array
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
