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

use phpMyFAQ\Configuration;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class McpSdkRuntime implements McpServerRuntimeInterface
{
    /**
     * @param array<string, mixed> $serverInfo
     */
    public function __construct(
        private readonly Configuration $configuration,
        private readonly FaqSearchTool $faqSearchTool,
        private readonly array $serverInfo,
    ) {
    }

    public function runConsole(InputInterface $input, OutputInterface $output): void
    {
        $this->buildServer()->run(new \Mcp\Server\Transport\StdioTransport());
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

        $decoded = json_decode($result['content'], true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $result['content'];
    }

    private function buildServer(): \Mcp\Server
    {
        if (!class_exists(\Mcp\Server::class) || !class_exists(\Mcp\Server\Transport\StdioTransport::class)) {
            throw new RuntimeException(
                'The mcp/sdk package is not installed or does not expose the expected server classes.',
            );
        }

        $definition = $this->faqSearchTool->getDefinition();

        return \Mcp\Server::builder()
            ->setServerInfo(
                (string) $this->serverInfo['name'],
                (string) $this->serverInfo['version'],
                (string) ($this->serverInfo['description'] ?? null),
            )
            ->addTool(
                fn(
                    string $query,
                    ?int $category_id = null,
                    int $limit = 10,
                    bool $all_languages = false,
                ): array|string => $this->faqSearch($query, $category_id, $limit, $all_languages),
                $definition->name,
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
