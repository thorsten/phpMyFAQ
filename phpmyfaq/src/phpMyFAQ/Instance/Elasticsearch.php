<?php

/**
 * The phpMyFAQ instances basic Elasticsearch class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-25
 */

namespace phpMyFAQ\Instance;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Http\Promise\Promise;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;

/**
 * Class Elasticsearch
 *
 * @package phpMyFAQ\Instance
 */
class Elasticsearch
{
    /** @var Client */
    protected Client $client;

    /** @var array<string, mixed> */
    protected array $esConfig;

    /**
     * Elasticsearch mapping
     * @var array<string, mixed>
     */
    private array $mappings = [
        '_source' => [
            'enabled' => true
        ],
        'properties' => [
            'question' => [
                'analyzer' => 'autocomplete',
                'search_analyzer' => PMF_ELASTICSEARCH_TOKENIZER
            ],
            'answer' => [
                'analyzer' => 'autocomplete',
                'search_analyzer' => PMF_ELASTICSEARCH_TOKENIZER
            ],
            'keywords' => [
                'analyzer' => 'autocomplete',
                'search_analyzer' => PMF_ELASTICSEARCH_TOKENIZER
            ],
            'categories' => [
                'analyzer' => 'autocomplete',
                'search_analyzer' => PMF_ELASTICSEARCH_TOKENIZER
            ]
        ]
    ];

    /**
     * Elasticsearch constructor.
     */
    public function __construct(protected Configuration $config)
    {
        $this->client = $config->getElasticsearch();
        $this->esConfig = $config->getElasticsearchConfig();
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
        } catch (ClientResponseException | MissingParameterException | ServerResponseException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Returns the basic phpMyFAQ index structure as raw array.
     *
     * @return array<string, mixed>
     */
    private function getParams(): array
    {
        return [
            'index' => $this->esConfig['index'],
            'body' => [
                'settings' => [
                    'number_of_shards' => PMF_ELASTICSEARCH_NUMBER_SHARDS,
                    'number_of_replicas' => PMF_ELASTICSEARCH_NUMBER_REPLICAS,
                    'analysis' => [
                        'filter' => [
                            'autocomplete_filter' => [
                                'type' => 'edge_ngram',
                                'min_gram' => 1,
                                'max_gram' => 20
                            ],
                            'Language_stemmer' => [
                                'type' => 'stemmer',
                                'name' => PMF_ELASTICSEARCH_STEMMING_LANGUAGE[$this->config->getDefaultLanguage()]
                            ]
                        ],
                        'analyzer' => [
                            'autocomplete' => [
                                'type' => 'custom',
                                'tokenizer' => PMF_ELASTICSEARCH_TOKENIZER,
                                'filter' => [
                                    'lowercase',
                                    'autocomplete_filter',
                                    'Language_stemmer'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Puts phpMyFAQ Elasticsearch mapping into index.
     */
    public function putMapping(): bool
    {
        $response = $this->getMapping();

        if (
            0 === (
            is_countable($response[$this->esConfig['index']]['mappings'])
                ?
                count($response[$this->esConfig['index']]['mappings'])
                :
                0
            )
        ) {
            $params = [
                'index' => $this->esConfig['index'],
                'body' => $this->mappings
            ];

            $response = $this->client->indices()->putMapping($params);

            if (isset($response['acknowledged']) && true === $response['acknowledged']) {
                return true;
            }
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
        } catch (ClientResponseException | ServerResponseException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Deletes the Elasticsearch index.
     *
     * @throws Exception
     */
    public function dropIndex(): object
    {
        try {
            return $this->client->indices()->delete(['index' => $this->esConfig['index']])->asObject();
        } catch (ClientResponseException | MissingParameterException | ServerResponseException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Indexing of a FAQ
     *
     * @param string[] $faq
     */
    public function index(array $faq): object
    {
        $params = [
            'index' => $this->esConfig['index'],
            'id' => $faq['solution_id'],
            'body' => [
                'id' => $faq['id'],
                'lang' => $faq['lang'],
                'question' => $faq['question'],
                'answer' => strip_tags($faq['answer']),
                'keywords' => $faq['keywords'],
                'category_id' => $faq['category_id']
            ]
        ];

        try {
            return $this->client->index($params)->asObject();
        } catch (ClientResponseException | MissingParameterException | ServerResponseException) {
            //return ['error' => $e->getMessage()];
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
            if ('no' === $faq['active']) {
                continue;
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $this->esConfig['index'],
                    '_id' => $faq['solution_id'],
                ]
            ];

            $params['body'][] = [
                'id' => $faq['id'],
                'lang' => $faq['lang'],
                'question' => $faq['title'],
                'answer' => strip_tags((string) $faq['content']),
                'keywords' => $faq['keywords'],
                'category_id' => $faq['category_id']
            ];

            if ($i % 1000 == 0) {
                try {
                    $responses = $this->client->bulk($params);
                } catch (ClientResponseException | ServerResponseException $e) {
                    return ['error' => $e->getMessage()];
                }
                $params = ['body' => []];
                unset($responses);
            }

            $i++;
        }

        // Send the last batch if it exists
        if (!empty($params['body'])) {
            try {
                $responses = $this->client->bulk($params);
            } catch (ClientResponseException | ServerResponseException $e) {
                return ['error' => $e->getMessage()];
            }
        }

        if (isset($responses) && (is_countable($responses) ? count($responses) : 0)) {
            return ['success' => $responses];
        }

        return ['error' => ''];
    }

    /**
     * Updates a FAQ document
     *
     * @param string[] $faq
     * @return string[]
     */
    public function update(array $faq): array
    {
        $params = [
            'index' => $this->esConfig['index'],
            'id' => $faq['solution_id'],
            'body' => [
                'doc' => [
                    'id' => $faq['id'],
                    'lang' => $faq['lang'],
                    'question' => $faq['question'],
                    'answer' => strip_tags($faq['answer']),
                    'keywords' => $faq['keywords'],
                    'category_id' => $faq['category_id']
                ]
            ]
        ];

        try {
            return $this->client->update($params)->asArray();
        } catch (ClientResponseException | MissingParameterException | ServerResponseException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Deletes a FAQ document
     *
     * @return string[]
     */
    public function delete(int $solutionId): array
    {
        $params = [
            'index' => $this->esConfig['index'],
            'id' => $solutionId
        ];

        try {
            return $this->client->delete($params)->asArray();
        } catch (ClientResponseException | MissingParameterException | ServerResponseException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
