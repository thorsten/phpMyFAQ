<?php

/**
 * phpMyFAQ MCP Server - FAQ Search Tool Metadata
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

use Symfony\AI\McpSdk\Capability\Tool\MetadataInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolAnnotationsInterface;

/**
 * Class FaqSearchToolMetadata
 *
 * Defines the metadata for the FAQ search tool in the MCP server.
 * This tool allows LLMs to search through phpMyFAQ's knowledge base
 * to find relevant FAQ entries based on user questions.
 */
class FaqSearchToolMetadata implements MetadataInterface
{
    public function getName(): string
    {
        return 'faq_search';
    }

    public function getDescription(): ?string
    {
        return 'Search through the phpMyFAQ knowledge base to find relevant FAQ entries that can answer questions. ' .
               'This tool searches both questions and answers in the FAQ database to provide comprehensive results.';
    }

    public function getTitle(): ?string
    {
        return 'FAQ Search';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The search query or question to find relevant FAQ entries for',
                ],
                'category_id' => [
                    'type' => 'integer',
                    'description' => 'Optional category ID to limit search to a specific FAQ category',
                    'minimum' => 1,
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results to return (default: 10)',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 50,
                ],
                'all_languages' => [
                    'type' => 'boolean',
                    'description' => 'Whether to search in all languages or just the current language (default: false)',
                    'default' => false,
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function getOutputSchema(): ?array
    {
        return [
            'type' => 'object',
            'properties' => [
                'results' => [
                    'type' => 'array',
                    'description' => 'Array of FAQ search results',
                ],
                'total_found' => [
                    'type' => 'integer',
                    'description' => 'Total number of FAQ entries found',
                ],
            ],
        ];
    }

    public function getAnnotations(): ?ToolAnnotationsInterface
    {
        return null;
    }
}
