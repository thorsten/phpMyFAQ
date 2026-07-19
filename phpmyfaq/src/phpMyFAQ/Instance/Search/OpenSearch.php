<?php

/**
 * phpMyFAQ OpenSearch instance class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-11-20
 */

declare(strict_types=1);

namespace phpMyFAQ\Instance\Search;

use Exception;
use OpenSearch\Client;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\OpenSearchConfiguration;

/**
 * Class OpenSearch
 *
 * @package phpMyFAQ\Instance
 */
readonly class OpenSearch
{
    /**
     * @var array<string, mixed>
     */
    private array $mappings;

    private Client $client;

    private OpenSearchConfiguration $openSearchConfiguration;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct(
        private Configuration $configuration,
    ) {
        $this->client = $this->configuration->getOpenSearch();
        $this->openSearchConfiguration = $this->configuration->getOpenSearchConfig();

        $this->mappings = [
            'properties' => [
                'id' => ['type' => 'integer'],
                'lang' => ['type' => 'keyword'],
                'solution_id' => ['type' => 'integer'],
                'question' => [
                    'type' => 'text',
                    'analyzer' => 'autocomplete',
                ],
                'answer' => [
                    'type' => 'text',
                    'analyzer' => 'autocomplete',
                ],
                'keywords' => [
                    'type' => 'text',
                    'analyzer' => 'autocomplete',
                ],
                'category_id' => ['type' => 'integer'],
                'content_type' => ['type' => 'keyword'],
                'slug' => ['type' => 'keyword'],
            ],
        ];
    }

    /**
     * Creates the OpenSearch index.
     *
     * @throws Exception
     */
    public function createIndex(): bool
    {
        $result = $this->client->indices()->exists(['index' => $this->openSearchConfiguration->getIndex()]);

        if (!$result) {
            $this->client->indices()->create($this->getParams());
        }

        return $this->putMapping();
    }

    /**
     * Returns the basic phpMyFAQ index structure as a raw array.
     *
     * @return array{index: string, body: array<string, mixed>}
     */
    private function getParams(): array
    {
        return [
            'index' => $this->openSearchConfiguration->getIndex(),
            'body' => [
                'settings' => [
                    'number_of_shards' => PMF_OPENSEARCH_NUMBER_SHARDS,
                    'number_of_replicas' => PMF_OPENSEARCH_NUMBER_REPLICAS,
                    'analysis' => [
                        'filter' => [
                            'autocomplete_filter' => [
                                'type' => 'edge_ngram',
                                'min_gram' => 1,
                                'max_gram' => 20,
                            ],
                            'Language_stemmer' => [
                                'type' => 'stemmer',
                                'name' => PMF_OPENSEARCH_STEMMING_LANGUAGE[$this->configuration->getDefaultLanguage()],
                            ],
                        ],
                        'analyzer' => [
                            'autocomplete' => [
                                'type' => 'custom',
                                'tokenizer' => PMF_OPENSEARCH_TOKENIZER,
                                'filter' => [
                                    'lowercase',
                                    'autocomplete_filter',
                                    'Language_stemmer',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Puts phpMyFAQ OpenSearch mapping into index.
     *
     * @throws Exception
     */
    public function putMapping(): bool
    {
        $response = $this->getMapping();
        $indexMapping = $response[$this->openSearchConfiguration->getIndex()] ?? null;
        $currentMappings = is_array($indexMapping) ? $indexMapping['mappings'] ?? null : null;

        if (!is_array($currentMappings) || $currentMappings === []) {
            $params = [
                'index' => $this->openSearchConfiguration->getIndex(),
                'body' => $this->mappings,
            ];

            $this->client->indices()->putMapping($params);
        }

        return true;
    }

    /**
     * Returns the current mapping.
     *
     * @throws Exception
     */
    public function getMapping(): array
    {
        return $this->client->indices()->getMapping();
    }

    /**
     * Deletes the OpenSearch index.
     *
     * @throws Exception
     */
    public function dropIndex(): array
    {
        return $this->client->indices()->delete(['index' => $this->openSearchConfiguration->getIndex()]);
    }

    /**
     * Indexing of a FAQ
     *
     * @param array<string, int|string|null> $faq
     */
    public function index(array $faq): array
    {
        $params = [
            'index' => $this->openSearchConfiguration->getIndex(),
            'id' => (string) ($faq['solution_id'] ?? ''),
            'body' => [
                'id' => (int) ($faq['id'] ?? 0),
                'lang' => (string) ($faq['lang'] ?? ''),
                'question' => (string) ($faq['question'] ?? ''),
                'answer' => strip_tags((string) ($faq['answer'] ?? '')),
                'keywords' => (string) ($faq['keywords'] ?? ''),
                'category_id' => (int) ($faq['category_id'] ?? 0),
                'content_type' => 'faq',
            ],
        ];

        return $this->client->index($params);
    }

    /**
     * Bulk indexing of all FAQs
     *
     * @param array<string, mixed> $faqs
     * @return array<string, mixed>
     */
    public function bulkIndex(array $faqs): array
    {
        $params = ['body' => []];
        $i = 1;

        foreach ($faqs as $faq) {
            if ('no' === ($faq['active'] ?? 'no')) {
                continue;
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $this->openSearchConfiguration->getIndex(),
                    '_id' => (string) ($faq['solution_id'] ?? ''),
                ],
            ];

            $params['body'][] = [
                'id' => (int) ($faq['id'] ?? 0),
                'lang' => (string) ($faq['lang'] ?? ''),
                'question' => (string) ($faq['title'] ?? ''),
                'answer' => strip_tags((string) ($faq['content'] ?? '')),
                'keywords' => (string) ($faq['keywords'] ?? ''),
                'category_id' => (int) ($faq['category_id'] ?? 0),
                'content_type' => 'faq',
            ];

            if (($i % 1000) === 0) {
                $responses = $this->client->bulk($params);

                $params = ['body' => []];
                unset($responses);
            }

            ++$i;
        }

        // Send the last batch if it exists
        $responses = $this->client->bulk($params);

        if ($responses !== null) {
            return ['success' => $responses];
        }

        return ['error' => 'Unknown error.'];
    }

    /**
     * Updates a FAQ document
     *
     * @param array<string, int|string|null> $faq
     * @return array<array-key, mixed>
     */
    public function update(array $faq): array
    {
        $params = [
            'index' => $this->openSearchConfiguration->getIndex(),
            'id' => (string) ($faq['solution_id'] ?? ''),
            'body' => [
                'doc' => [
                    'id' => (int) ($faq['id'] ?? 0),
                    'lang' => (string) ($faq['lang'] ?? ''),
                    'question' => (string) ($faq['question'] ?? ''),
                    'answer' => strip_tags((string) ($faq['answer'] ?? '')),
                    'keywords' => (string) ($faq['keywords'] ?? ''),
                    'category_id' => (int) ($faq['category_id'] ?? 0),
                    'content_type' => 'faq',
                ],
            ],
        ];

        return $this->client->update($params);
    }

    /**
     * Deletes a FAQ document
     *
     * @return array<array-key, mixed>
     */
    public function delete(int $solutionId): array
    {
        $params = [
            'index' => $this->openSearchConfiguration->getIndex(),
            'id' => (string) $solutionId,
        ];

        return $this->client->delete($params);
    }

    /**
     * Checks if OpenSearch is available
     */
    public function isAvailable(): bool
    {
        try {
            return $this->client->ping();
        } catch (Exception $exception) {
            $this->configuration->getLogger()->error('OpenSearch ping failed.', [$exception->getMessage()]);
            return false;
        }
    }

    /**
     * Indexing of a custom page
     *
     * @param array<string, mixed> $page
     */
    public function indexCustomPage(array $page): array
    {
        // Only index active pages
        if (($page['active'] ?? null) === 'n') {
            // Delete from index if it exists (in case it was previously active)
            return $this->deleteCustomPage((int) ($page['id'] ?? 0), (string) ($page['lang'] ?? ''));
        }

        $params = [
            'index' => $this->openSearchConfiguration->getIndex(),
            'id' => 'page_' . (string) ($page['id'] ?? '') . '_' . (string) ($page['lang'] ?? ''),
            'body' => [
                'id' => (int) ($page['id'] ?? 0),
                'lang' => (string) ($page['lang'] ?? ''),
                'question' => (string) ($page['page_title'] ?? ''),
                'answer' => strip_tags((string) ($page['content'] ?? '')),
                'keywords' => '',
                'category_id' => 0,
                'content_type' => 'page',
                'slug' => (string) ($page['slug'] ?? ''),
            ],
        ];

        try {
            return $this->client->index($params);
        } catch (Exception $e) {
            $this->configuration->getLogger()->error('Index custom page error.', [$e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Updates a custom page document
     *
     * @param array<string, mixed> $page
     * @return array<array-key, mixed>
     */
    public function updateCustomPage(array $page): array
    {
        // Only index active pages - delete from index if inactive
        if (($page['active'] ?? null) === 'n') {
            return $this->deleteCustomPage((int) ($page['id'] ?? 0), (string) ($page['lang'] ?? ''));
        }

        $params = [
            'index' => $this->openSearchConfiguration->getIndex(),
            'id' => 'page_' . (string) ($page['id'] ?? '') . '_' . (string) ($page['lang'] ?? ''),
            'body' => [
                'doc' => [
                    'id' => (int) ($page['id'] ?? 0),
                    'lang' => (string) ($page['lang'] ?? ''),
                    'question' => (string) ($page['page_title'] ?? ''),
                    'answer' => strip_tags((string) ($page['content'] ?? '')),
                    'keywords' => '',
                    'category_id' => 0,
                    'content_type' => 'page',
                    'slug' => (string) ($page['slug'] ?? ''),
                ],
            ],
        ];

        try {
            return $this->client->update($params);
        } catch (Exception $e) {
            // If document doesn't exist, try to create it
            if (str_contains($e->getMessage(), 'document_missing_exception')) {
                return $this->indexCustomPage($page);
            }
            $this->configuration->getLogger()->error('Update custom page error.', [$e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Deletes a custom page document
     *
     * @return array<array-key, mixed>
     */
    public function deleteCustomPage(int $pageId, string $lang): array
    {
        $params = [
            'index' => $this->openSearchConfiguration->getIndex(),
            'id' => 'page_' . $pageId . '_' . $lang,
        ];

        try {
            return $this->client->delete($params);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Bulk indexing of custom pages
     *
     * @param array<int, array<string, mixed>> $pages
     * @return array<string, mixed>
     */
    public function bulkIndexCustomPages(array $pages): array
    {
        $params = ['body' => []];
        $i = 1;

        foreach ($pages as $page) {
            if ('n' === ($page['active'] ?? 'n')) {
                continue;
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $this->openSearchConfiguration->getIndex(),
                    '_id' => 'page_' . (string) ($page['id'] ?? '') . '_' . (string) ($page['lang'] ?? ''),
                ],
            ];

            $params['body'][] = [
                'id' => (int) ($page['id'] ?? 0),
                'lang' => (string) ($page['lang'] ?? ''),
                'question' => (string) ($page['page_title'] ?? ''),
                'answer' => strip_tags((string) ($page['content'] ?? '')),
                'keywords' => '',
                'category_id' => 0,
                'content_type' => 'page',
                'slug' => (string) ($page['slug'] ?? ''),
            ];

            if (($i % 1000) === 0) {
                $responses = $this->client->bulk($params);
                $params = ['body' => []];
                unset($responses);
            }

            ++$i;
        }

        // Send the last batch if it exists
        $responses = null;
        if (($params['body'] ?? []) !== []) {
            $responses = $this->client->bulk($params);
        }

        if ($responses !== null) {
            return ['success' => $responses];
        }

        return ['success' => true];
    }
}
