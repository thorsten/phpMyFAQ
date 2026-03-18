<?php

/**
 * phpMyFAQ MCP Server - FAQ Search Tool
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
 * @since     2026-03-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Service\McpServer;

use Exception;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Search;

readonly class FaqSearchTool implements McpToolExecutorInterface
{
    public function __construct(
        private Configuration $configuration,
        private Search $search,
        private Faq $faq,
    ) {
    }

    public function getDefinition(): McpToolDefinition
    {
        return new McpToolDefinition(
            name: 'faq_search',
            description: 'Search through the phpMyFAQ knowledge base to find relevant FAQ entries that can answer questions. '
            . 'This tool searches both questions and answers in the FAQ database to provide comprehensive results.',
            title: 'FAQ Search',
            inputSchema: [
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
            ],
            outputSchema: [
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
            ],
        );
    }

    public function execute(array $arguments): array
    {
        $query = $arguments['query'] ?? '';
        $categoryId = $arguments['category_id'] ?? null;
        $limit = $arguments['limit'] ?? 10;
        $allLanguages = $arguments['all_languages'] ?? false;

        if (trim((string) $query) === '') {
            return $this->createResult('Error: Search query cannot be empty.');
        }

        try {
            $this->faq->setUser(-1);
            $this->faq->setGroups([-1]);

            $category = new Category($this->configuration, [-1]);
            $category->setUser(-1);
            $this->search->setCategory($category);

            if ($categoryId !== null) {
                $this->search->setCategoryId((int) $categoryId);
            }

            $searchResults = $this->search->search($query, (bool) $allLanguages);

            if ($searchResults === []) {
                return $this->createResult($this->formatResultsAsJson([]));
            }

            $validResults = [];
            foreach ($searchResults as $searchResult) {
                $this->configuration->getLogger()->info(var_export($searchResult, return: true));

                $validResults[] = [
                    'id' => $searchResult->id,
                    'language' => $searchResult->lang,
                    'question' => $searchResult->question ?? '',
                    'answer' => $searchResult->answer ?? '',
                    'category_id' => $searchResult->category_id ?? null,
                    'relevance_score' => $searchResult->score ?? 0.0,
                    'url' => $this->buildFaqUrl($searchResult->id, $searchResult->lang),
                ];
            }

            $limitedResults = array_slice($validResults, 0, (int) $limit);

            if ($limitedResults === []) {
                return $this->createResult('No accessible FAQ entries found for the given query.');
            }

            return $this->createResult($this->formatResultsAsJson($limitedResults));
        } catch (Exception $exception) {
            return $this->createResult('Error searching FAQ database: ' . $exception->getMessage());
        }
    }

    public function getSearch(): Search
    {
        return $this->search;
    }

    public function getFaq(): Faq
    {
        return $this->faq;
    }

    /**
     * @param array<int, array<string, mixed>> $results
     */
    private function formatResultsAsJson(array $results): string
    {
        if ($results === []) {
            $jsonData = [
                'results' => [],
                'total_found' => 0,
            ];

            return json_encode($jsonData);
        }

        $jsonData = [
            'results' => $results,
            'total_found' => count($results),
        ];

        return json_encode($jsonData, JSON_PRETTY_PRINT);
    }

    private function buildFaqUrl(int $faqId, string $language): string
    {
        return $this->configuration->getDefaultUrl() . 'content/' . $faqId . '/' . $language;
    }

    /**
     * @return array{content: string, type: string, mimeType: string}
     */
    private function createResult(string $content): array
    {
        return [
            'content' => $content,
            'type' => 'text',
            'mimeType' => 'application/json',
        ];
    }
}
