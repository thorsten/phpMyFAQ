<?php

/**
 * phpMyFAQ MCP Server - FAQ Search Tool Executor
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

use Exception;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Search;
use Symfony\AI\McpSdk\Capability\Tool\IdentifierInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\AI\McpSdk\Capability\Tool\ToolCallResult;
use Symfony\AI\McpSdk\Capability\Tool\ToolExecutorInterface;

/**
 * Class FaqSearchToolExecutor
 *
 * Executes the FAQ search functionality for the MCP server.
 * This class uses phpMyFAQ's Search and Faq classes to search
 * through the knowledge base and return relevant FAQ entries.
 */
readonly class FaqSearchToolExecutor implements ToolExecutorInterface, IdentifierInterface
{
    public function __construct(
        private Configuration $configuration,
        private Search $search,
        private Faq $faq,
    ) {
    }

    public function getName(): string
    {
        return 'faq_search';
    }

    /**
     * @throws Exception
     */
    public function call(ToolCall $toolCall): ToolCallResult
    {
        $query = $toolCall->arguments['query'] ?? '';
        $categoryId = $toolCall->arguments['category_id'] ?? null;
        $limit = $toolCall->arguments['limit'] ?? 10;
        $allLanguages = $toolCall->arguments['all_languages'] ?? false;

        if (empty($query)) {
            return new ToolCallResult('Error: Search query cannot be empty.', 'text', 'application/json');
        }

        try {
            $this->faq->setUser(-1);
            $this->faq->setGroups([-1]);

            // Set the category class
            $category = new Category($this->configuration, [-1]);
            $category->setUser(-1);
            $this->search->setCategory($category);

            // Set category filter if provided
            if ($categoryId !== null) {
                $this->search->setCategoryId((int) $categoryId);
            }

            // Perform the search
            $searchResults = $this->search->search($query, (bool) $allLanguages);

            if ($searchResults === []) {
                $emptyResult = $this->formatResultsAsJson([]);
                return new ToolCallResult($emptyResult, 'text', 'application/json');
            }

            // Format the results
            $validResults = [];
            foreach ($searchResults as $searchResult) {
                $this->configuration->getLogger()->info(var_export($searchResult, true));

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

            // Limit results
            $limitedResults = array_slice($validResults, 0, (int) $limit);

            if ($limitedResults === []) {
                return new ToolCallResult(
                    'No accessible FAQ entries found for the given query.',
                    'text',
                    'application/json',
                );
            }

            $resultJson = $this->formatResultsAsJson($limitedResults);
            return new ToolCallResult($resultJson, 'text', 'application/json');
        } catch (Exception $exception) {
            return new ToolCallResult(
                'Error searching FAQ database: ' . $exception->getMessage(),
                'text',
                'application/json',
            );
        }
    }

    private function formatResultsAsJson(array $results): string
    {
        if ($results === []) {
            return json_encode([
                'results' => [],
                'total_found' => 0,
            ]);
        }

        $jsonData = [
            'results' => $results,
            'total_found' => count($results),
        ];

        return json_encode($jsonData, JSON_PRETTY_PRINT);
    }

    private function buildFaqUrl(int $faqId, string $language): string
    {
        $baseUrl = $this->configuration->getDefaultUrl();
        return rtrim($baseUrl, '/') . '/index.php?action=faq&cat=0&id=' . $faqId . '&artlang=' . $language;
    }
}
