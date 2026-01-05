<?php

/**
 * phpMyFAQ MCP Server Console Command
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

namespace phpMyFAQ\Command;

use Exception;
use phpMyFAQ\Service\McpServer\PhpMyFaqMcpServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class McpServerCommand
 *
 * Console command to run the phpMyFAQ MCP (Model Context Protocol) server.
 * This command starts the MCP server that allows LLM models to query
 * the phpMyFAQ knowledge base through the MCP protocol.
 */
#[AsCommand(name: 'phpmyfaq:mcp:server', description: 'Run the phpMyFAQ MCP server for LLM integration')]
class McpServerCommand extends Command
{
    public function __construct(
        private readonly PhpMyFaqMcpServer $phpMyFaqMcpServer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(description: 'Run the phpMyFAQ MCP server for LLM integration')
            ->setHelp(
                help: 'This command starts the MCP server that allows LLM models to search and query phpMyFAQ installations.',
            )
            ->addOption(
                name: 'info',
                shortcut: 'i',
                mode: InputOption::VALUE_NONE,
                description: 'Show server information instead of running the server',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        if ($input->getOption(name: 'info')) {
            $this->showServerInfo($symfonyStyle);
            return Command::SUCCESS;
        }

        $symfonyStyle->title(message: 'phpMyFAQ MCP Server');
        $symfonyStyle->info(message: 'Starting MCP server for phpMyFAQ knowledge base...');
        $symfonyStyle->info(message: 'The server will handle MCP protocol requests from LLM clients.');
        $symfonyStyle->warning(message: 'Press Ctrl+C to stop the server.');

        try {
            $this->phpMyFaqMcpServer->runConsole($input, $output);
            return Command::SUCCESS;
        } catch (Exception $exception) {
            $symfonyStyle->error('Failed to start MCP server: ' . $exception->getMessage());
            return Command::FAILURE;
        }
    }

    private function showServerInfo(SymfonyStyle $symfonyStyle): void
    {
        $serverInfo = $this->phpMyFaqMcpServer->getServerInfo();

        $symfonyStyle->title($serverInfo['name']);
        $symfonyStyle->definitionList(
            ['Version' => $serverInfo['version']],
            ['Description' => $serverInfo['description']],
            ['Capabilities' => implode(separator: ', ', array: array_keys(array_filter($serverInfo['capabilities'])))],
        );

        $symfonyStyle->section(message: 'Available Tools');

        $toolsTable = [];
        foreach ($serverInfo['tools'] as $tool) {
            $toolsTable[] = [$tool['name'], $tool['description']];
        }

        $symfonyStyle->table(['Name', 'Description'], $toolsTable);

        $symfonyStyle->section(message: 'Usage Examples');
        $symfonyStyle->text([
            'Start the server:',
            '  php bin/console phpmyfaq:mcp:server',
            '',
            'The server will accept MCP protocol requests and provide access to:',
            '  • FAQ search functionality',
            '  • Knowledge base querying',
            '  • Contextual information for LLM models',
        ]);
    }
}
