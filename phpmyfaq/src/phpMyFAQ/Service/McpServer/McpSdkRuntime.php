<?php

/**
 * phpMyFAQ MCP SDK Runtime
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
 * @since     2026-03-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Service\McpServer;

use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;
use phpMyFAQ\Configuration;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class McpSdkRuntime implements McpServerRuntimeInterface
{
    /**
     * @param array<string, mixed> $serverInfo
     */
    public function __construct(
        private Configuration $configuration,
        private FaqSearchTool $faqSearchTool,
        private array $serverInfo,
    ) {
    }

    public function runConsole(InputInterface $input, OutputInterface $output): void
    {
        $this->buildServer()->run(new StdioTransport());
    }

    public function getServerInfo(): array
    {
        return $this->serverInfo;
    }

    /**
     * Adapter method for mcp/sdk manual tool registration.
     *
     * Returns the decoded JSON payload for successful searches and a string for
     * user-facing errors, matching the intent of the current Symfony MCP runtime.
     *
     * @return array<string, mixed>|string
     */
    public function faqSearch(
        string $query,
        ?int $category_id = null,
        int $limit = 10,
        bool $all_languages = false,
    ): array|string {
        $result = $this->faqSearchTool->execute([
            'query' => $query,
            'category_id' => $category_id,
            'limit' => $limit,
            'all_languages' => $all_languages,
        ]);

        $decoded = json_decode($result['content'], associative: true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $result['content'];
    }

    private function buildServer(): Server
    {
        if (!class_exists(Server::class) || !class_exists(StdioTransport::class)) {
            throw new RuntimeException(
                'The mcp/sdk package is not installed or does not expose the expected server classes.',
            );
        }

        $definition = $this->faqSearchTool->getDefinition();

        return Server::builder()
            ->setServerInfo(
                (string) $this->serverInfo['name'],
                (string) $this->serverInfo['version'],
                (string) ($this->serverInfo['description'] ?? null),
            )
            ->addTool(
                $this->faqSearch(...),
                $definition->name,
                null,
                $definition->description,
                null,
                $definition->inputSchema,
                null,
                null,
                $definition->outputSchema,
            )
            ->build();
    }
}
