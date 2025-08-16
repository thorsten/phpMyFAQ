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

namespace phpMyFAQ\Service\McpServer;

use Exception;
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
        private Faq $faq
    ) {
    }

    public function getName(): string
    {
        return 'faq_search';
    }

    public function call(ToolCall $input): ToolCallResult
    {
        $query = $input->arguments['query'] ?? '';
        $categoryId = $input->arguments['category_id'] ?? null;
        $limit = $input->arguments['limit'] ?? 10;
        $allLanguages = $input->arguments['all_languages'] ?? false;

        if (empty($query)) {
            return new ToolCallResult(
                'Error: Search query cannot be empty.',
                false
            );
        }

        try {
            // Set category filter if provided
            if ($categoryId !== null) {
                $this->search->setCategoryId((int) $categoryId);
            }

            // Perform the search
            $searchResults = $this->search->search($query, (bool) $allLanguages);

            // Limit results
            $searchResults = array_slice($searchResults, 0, (int) $limit);

            if (empty($searchResults)) {
                return new ToolCallResult(
                    'No FAQ entries found for the given query.',
                    true
                );
            }

            // Format the results
            $formattedResults = [];
            foreach ($searchResults as $result) {
                $faqId = $result->id;
                $faqLanguage = $result->lang;

                // Get the full FAQ content
                $faqData = $this->faq->getFaqResult($faqId, $faqLanguage, null, false);

                if ($faqData) {
                    $formattedResults[] = [
                        'id' => $faqId,
                        'language' => $faqLanguage,
                        'question' => $result->question ?? '',
                        'answer' => $result->answer ?? '',
                        'category_id' => $result->category_id ?? null,
                        'relevance_score' => $result->score ?? 0.0,
                        'url' => $this->buildFaqUrl($faqId, $faqLanguage),
                    ];
                }
            }

            $resultText = $this->formatResultsAsText($formattedResults);

            return new ToolCallResult($resultText, true);
        } catch (Exception $e) {
            return new ToolCallResult(
                'Error searching FAQ database: ' . $e->getMessage(),
                false
            );
        }
    }

    private function formatResultsAsText(array $results): string
    {
        if (empty($results)) {
            return 'No results found.';
        }

        $text = "Found " . count($results) . " relevant FAQ entries:\n\n";

        foreach ($results as $index => $result) {
            $text .= sprintf(
                "**FAQ #%d** (ID: %d, Language: %s)\n" .
                "**Question:** %s\n" .
                "**Answer:** %s\n" .
                "**URL:** %s\n" .
                "**Relevance Score:** %.2f\n\n",
                $index + 1,
                $result['id'],
                $result['language'],
                strip_tags($result['question']),
                strip_tags($result['answer']),
                $result['url'],
                $result['relevance_score']
            );
        }

        return $text;
    }

    private function buildFaqUrl(int $faqId, string $language): string
    {
        $baseUrl = $this->configuration->getDefaultUrl();
        return rtrim($baseUrl, '/') . '/index.php?action=faq&cat=0&id=' . $faqId . '&artlang=' . $language;
    }
}
