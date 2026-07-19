<?php

/**
 * The phpMyFAQ instances a basic Elasticsearch class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Instance\Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Http\Promise\Promise;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Core\Exception;

/**
 * Class Elasticsearch
 *
 * @package phpMyFAQ\Instance
 */
class Elasticsearch
{
    protected Client $client;

    protected ElasticsearchConfiguration $elasticsearchConfiguration;

    /**
     * Elasticsearch mapping
     * @var array<string, mixed>
     */
    private array $mappings = [];

    /**
     * Elasticsearch constructor.
     */
    public function __construct(
        protected Configuration $configuration,
    ) {
        $this->client = $configuration->getElasticsearch();
        $this->elasticsearchConfiguration = $configuration->getElasticsearchConfig();
        $this->mappings = $this->buildMappings();
    }

    /**
     * Creates the Elasticsearch index.
     *
     * @throws Exception
     */
    public function createIndex(): bool
    {
        try {
            $this->client->indices()->create($this->getParams());
            return $this->putMapping();
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Returns the basic phpMyFAQ index structure as raw array.
     *
     * @return array{index: string, body: array<string, mixed>}
     */
    private function getParams(): array
    {
        $tokenizer = $this->getTokenizer();

        return [
            'index' => $this->elasticsearchConfiguration->getIndex(),
            'body' => [
                'settings' => [
                    'number_of_shards' => $this->getNumberOfShards(),
                    'number_of_replicas' => $this->getNumberOfReplicas(),
                    'analysis' => [
                        'filter' => [
                            'autocomplete_filter' => [
                                'type' => 'edge_ngram',
                                'min_gram' => 1,
                                'max_gram' => 20,
                            ],
                            'Language_stemmer' => [
                                'type' => 'stemmer',
                                'name' => $this->getStemmingLanguage(),
                            ],
                        ],
                        'analyzer' => [
                            'autocomplete' => [
                                'type' => 'custom',
                                'tokenizer' => $tokenizer,
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
     * @return array<string, mixed>
     */
    private function buildMappings(): array
    {
        $searchAnalyzer = $this->getSearchAnalyzer();

        return [
            '_source' => [
                'enabled' => true,
            ],
            'properties' => [
                'question' => [
                    'type' => 'search_as_you_type',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => $searchAnalyzer,
                ],
                'answer' => [
                    'type' => 'search_as_you_type',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => $searchAnalyzer,
                ],
                'keywords' => [
                    'type' => 'search_as_you_type',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => $searchAnalyzer,
                ],
                'categories' => [
                    'type' => 'search_as_you_type',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => $searchAnalyzer,
                ],
                'content_type' => [
                    'type' => 'keyword',
                ],
                'slug' => [
                    'type' => 'keyword',
                ],
            ],
        ];
    }

    private function getSearchAnalyzer(): string
    {
        if (defined('PMF_ELASTICSEARCH_SEARCH_ANALYZER')) {
            $searchAnalyzer = constant('PMF_ELASTICSEARCH_SEARCH_ANALYZER');
            if (is_string($searchAnalyzer) && $searchAnalyzer !== '') {
                return $searchAnalyzer;
            }
        }

        return 'standard';
    }

    private function getTokenizer(): string
    {
        if (defined('PMF_ELASTICSEARCH_TOKENIZER')) {
            return (string) constant('PMF_ELASTICSEARCH_TOKENIZER');
        }

        return 'standard';
    }

    private function getNumberOfShards(): int
    {
        if (defined('PMF_ELASTICSEARCH_NUMBER_SHARDS')) {
            return (int) constant('PMF_ELASTICSEARCH_NUMBER_SHARDS');
        }

        return 2;
    }

    private function getNumberOfReplicas(): int
    {
        if (defined('PMF_ELASTICSEARCH_NUMBER_REPLICAS')) {
            return (int) constant('PMF_ELASTICSEARCH_NUMBER_REPLICAS');
        }

        return 0;
    }

    private function getStemmingLanguage(): string
    {
        if (!defined('PMF_ELASTICSEARCH_STEMMING_LANGUAGE')) {
            return 'english';
        }

        $defaultLanguage = $this->configuration->getDefaultLanguage();
        $stemmingLanguages = constant('PMF_ELASTICSEARCH_STEMMING_LANGUAGE');

        if (!is_array($stemmingLanguages)) {
            return 'english';
        }

        $stemmer = $stemmingLanguages[$defaultLanguage] ?? 'english';

        return is_string($stemmer) ? $stemmer : 'english';
    }

    /**
     * Puts phpMyFAQ Elasticsearch mapping into index.
     */
    public function putMapping(): bool
    {
        $response = $this->getMapping();
        $indexMapping = $response instanceof ElasticsearchResponse
            ? $response[$this->elasticsearchConfiguration->getIndex()]
            : null;
        $currentMappings = is_array($indexMapping) ? $indexMapping['mappings'] ?? null : null;

        if (!is_array($currentMappings) || $currentMappings === []) {
            $params = [
                'index' => $this->elasticsearchConfiguration->getIndex(),
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
    public function getMapping(): \Elastic\Elasticsearch\Response\Elasticsearch|Promise
    {
        try {
            return $this->client->indices()->getMapping();
        } catch (ClientResponseException|ServerResponseException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Unwraps a synchronous Elasticsearch response. The client is configured
     * for synchronous requests, so receiving a Promise is a programming error.
     */
    private function unwrapResponse(ElasticsearchResponse|Promise $response): ElasticsearchResponse
    {
        if (!$response instanceof ElasticsearchResponse) {
            throw new \RuntimeException('Unexpected asynchronous Elasticsearch response.');
        }

        return $response;
    }

    /**
     * Deletes the Elasticsearch index.
     *
     * @throws Exception
     */
    public function dropIndex(): object
    {
        try {
            return $this->unwrapResponse($this->client
                ->indices()
                ->delete([
                    'index' => $this->elasticsearchConfiguration->getIndex(),
                ]))->asObject();
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * Indexing of a FAQ
     *
     * @param array<string, int|string|null> $faq
     */
    public function index(array $faq): ?object
    {
        $params = [
            'index' => $this->elasticsearchConfiguration->getIndex(),
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

        try {
            return $this->unwrapResponse($this->client->index($params))->asObject();
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
            $this->configuration->getLogger()->error('Index error.', [$e->getMessage()]);
            return null;
        }
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
        $responses = [];
        $i = 1;

        foreach ($faqs as $faq) {
            if ('no' === ($faq['active'] ?? 'no')) {
                continue;
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $this->elasticsearchConfiguration->getIndex(),
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
                try {
                    $responses = $this->client->bulk($params);
                } catch (ClientResponseException|ServerResponseException $e) {
                    return ['error' => $e->getMessage()];
                }

                $params = ['body' => []];
                unset($responses);
            }

            ++$i;
        }

        // Send the last batch if it exists
        try {
            $responses = $this->unwrapResponse($this->client->bulk($params));
        } catch (ClientResponseException|ServerResponseException $e) {
            return ['error' => $e->getMessage()];
        }

        if ($responses->getStatusCode() === 200) {
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
            'index' => $this->elasticsearchConfiguration->getIndex(),
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

        try {
            return $this->unwrapResponse($this->client->update($params))->asArray();
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Deletes a FAQ document
     *
     * @return array<array-key, mixed>
     */
    public function delete(int $solutionId): array
    {
        $params = [
            'index' => $this->elasticsearchConfiguration->getIndex(),
            'id' => (string) $solutionId,
        ];

        try {
            return $this->unwrapResponse($this->client->delete($params))->asArray();
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Checks if Elasticsearch is available
     */
    public function isAvailable(): bool
    {
        try {
            return $this->unwrapResponse($this->client->ping())->asBool();
        } catch (ClientResponseException|ServerResponseException $e) {
            $this->configuration->getLogger()->error('Elasticsearch ping failed.', [$e->getMessage()]);
            return false;
        }
    }

    /**
     * Indexing of a custom page
     *
     * @param array<string, mixed> $page
     */
    public function indexCustomPage(array $page): ?object
    {
        // Only index active pages
        if (($page['active'] ?? null) === 'n') {
            // Delete from index if it exists (in case it was previously active)
            $this->deleteCustomPage((int) ($page['id'] ?? 0), (string) ($page['lang'] ?? ''));
            return null;
        }

        $params = [
            'index' => $this->elasticsearchConfiguration->getIndex(),
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
            return $this->unwrapResponse($this->client->index($params))->asObject();
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
            $this->configuration->getLogger()->error('Index custom page error.', [$e->getMessage()]);
            return null;
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
            'index' => $this->elasticsearchConfiguration->getIndex(),
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
            return $this->unwrapResponse($this->client->update($params))->asArray();
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
            // If document doesn't exist, try to create it
            if (str_contains($e->getMessage(), 'document_missing_exception')) {
                $result = $this->indexCustomPage($page);
                return $result ? ['success' => true] : ['error' => 'Failed to create document'];
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
            'index' => $this->elasticsearchConfiguration->getIndex(),
            'id' => 'page_' . $pageId . '_' . $lang,
        ];

        try {
            return $this->unwrapResponse($this->client->delete($params))->asArray();
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
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
        $responses = [];
        $i = 1;

        foreach ($pages as $page) {
            if ('n' === ($page['active'] ?? 'n')) {
                continue;
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $this->elasticsearchConfiguration->getIndex(),
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
                try {
                    $responses = $this->client->bulk($params);
                } catch (ClientResponseException|ServerResponseException $e) {
                    return ['error' => $e->getMessage()];
                }

                $params = ['body' => []];
                unset($responses);
            }

            ++$i;
        }

        // Send the last batch if it exists
        $responses = null;
        if (($params['body'] ?? []) !== []) {
            try {
                $responses = $this->unwrapResponse($this->client->bulk($params));
            } catch (ClientResponseException|ServerResponseException $e) {
                return ['error' => $e->getMessage()];
            }
        }

        if ($responses instanceof ElasticsearchResponse && $responses->getStatusCode() === 200) {
            return ['success' => $responses];
        }

        return ['success' => true];
    }
}
