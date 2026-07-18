<?php

/**
 * phpMyFAQ OpenSearch based search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-05-12
 */

declare(strict_types=1);

namespace phpMyFAQ\Search\Search;

use OpenSearch\Client;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use phpMyFAQ\Search\AbstractSearch;
use phpMyFAQ\Search\SearchInterface;
use stdClass;

/**
 * Class OpenSearch
 *
 * @package phpMyFAQ\Search
 */
class OpenSearch extends AbstractSearch implements SearchInterface
{
    private readonly Client $client;

    private readonly OpenSearchConfiguration $openSearchConfiguration;

    private string $language = '';

    /** @var int[] */
    private array $categoryIds = [];

    /**
     * Constructor.
     */
    public function __construct(
        Configuration $configuration,
        ?Client $client = null,
        ?OpenSearchConfiguration $openSearchConfiguration = null,
    ) {
        parent::__construct($configuration);

        $this->client = $client ?? $this->configuration->getOpenSearch();
        $this->openSearchConfiguration = $openSearchConfiguration ?? $this->configuration->getOpenSearchConfig();
    }

    /**
     * Executes the search request and maps the hits to result objects.
     *
     * @param array{index: string, size: int, body: array<string, mixed>} $searchParams
     * @return stdClass[]
     */
    private function mapHits(array $searchParams): array
    {
        $result = $this->client->search($searchParams);
        if (!is_array($result)) {
            return [];
        }

        $hits = $result['hits']['hits'] ?? null;
        if (!is_array($hits)) {
            return [];
        }

        $mapped = [];
        foreach ($hits as $hit) {
            if (!is_array($hit)) {
                continue;
            }

            $source = $hit['_source'] ?? null;
            if (!is_array($source)) {
                continue;
            }

            $resultSet = new stdClass();
            $resultSet->id = (int) ($source['id'] ?? 0);
            $resultSet->lang = (string) ($source['lang'] ?? '');
            $resultSet->question = (string) ($source['question'] ?? '');
            $resultSet->answer = (string) ($source['answer'] ?? '');
            $resultSet->keywords = (string) ($source['keywords'] ?? '');
            $resultSet->category_id = (int) ($source['category_id'] ?? 0);
            $resultSet->score = is_numeric($hit['_score'] ?? null) ? (float) $hit['_score'] : 0.0;

            // Add custom page specific fields if present
            if (($source['content_type'] ?? null) === 'page') {
                $resultSet->content_type = 'page';
                $resultSet->slug = (string) ($source['slug'] ?? '');
            }

            $mapped[] = $resultSet;
        }

        return $mapped;
    }

    /**
     * Prepares the search and executes it.
     *
     * @param string $searchTerm Search term
     * @return stdClass[]
     */
    public function search(string $searchTerm): array
    {
        $result = [];
        $this->resultSet = [];

        // Build search query that includes both FAQs and custom pages
        $searchParams = [
            'index' => $this->openSearchConfiguration->getIndex(),
            'size' => 100,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'multi_match' => [
                                'fields' => [
                                    'question',
                                    'answer',
                                    'keywords',
                                ],
                                'query' => $searchTerm,
                                'fuzziness' => 'AUTO',
                            ],
                        ],
                        'should' => [
                            // FAQs: must match category filter
                            [
                                'bool' => [
                                    'must' => [
                                        'term' => ['content_type' => 'faq'],
                                    ],
                                    'filter' => [
                                        'terms' => ['category_id' => $this->getCategoryIds()],
                                    ],
                                ],
                            ],
                            // Custom pages: no category filter
                            [
                                'term' => ['content_type' => 'page'],
                            ],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ],
            ],
        ];

        $this->resultSet = $this->mapHits($searchParams);

        return $this->resultSet;
    }

    /**
     * Returns the current category ID
     *
     * @return int[]
     */
    public function getCategoryIds(): array
    {
        return $this->categoryIds;
    }

    /**
     * Sets the current category ID
     *
     * @param int[] $categoryIds
     */
    public function setCategoryIds(array $categoryIds): void
    {
        $this->categoryIds = $categoryIds;
    }

    /**
     * Prepares the auto complete search and executes it.
     *
     * @param string $searchTerm Search term for autocompletion
     *
     * @return stdClass[]
     */
    public function autoComplete(string $searchTerm): array
    {
        $this->resultSet = [];

        $searchParams = [
            'index' => $this->openSearchConfiguration->getIndex(),
            'size' => 100,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'multi_match' => [
                                'fields' => [
                                    'question',
                                    'answer',
                                    'keywords',
                                ],
                                'query' => $searchTerm,
                                'fuzziness' => 'AUTO',
                            ],
                        ],
                        'filter' => [
                            'term' => [
                                'lang' => $this->getLanguage(),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->resultSet = $this->mapHits($searchParams);

        return $this->resultSet;
    }

    /**
     * Returns the current language, empty string if all languages
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Sets the current language
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }
}
