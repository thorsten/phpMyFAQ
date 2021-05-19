<?php

/**
 * The phpMyFAQ instances basic Elasticsearch class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-25
 */

namespace phpMyFAQ\Instance;

use Elasticsearch\Client;
use phpMyFAQ\Configuration;

/**
 * Class Elasticsearch
 *
 * @package phpMyFAQ\Instance
 */
class Elasticsearch
{
    /** @var Configuration */
    protected $config;

    /** @var Client */
    protected $client;

    /** @var array<string, mixed> */
    protected $esConfig;

    /**
     * Elasticsearch mapping
     * @var array<string, mixed>
     */
    private $mappings = [
        'faqs' => [
            '_source' => [
                'enabled' => true
            ],
            'properties' => [
                'id' => [
                    'type' => 'integer'
                ],
                'question' => [
                    'type' => 'text',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => PMF_ELASTICSEARCH_TOKENIZER
                ],
                'answer' => [
                    'type' => 'text',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => PMF_ELASTICSEARCH_TOKENIZER
                ],
                'keywords' => [
                    'type' => 'text',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => PMF_ELASTICSEARCH_TOKENIZER
                ],
                'categories' => [
                    'type' => 'text',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => PMF_ELASTICSEARCH_TOKENIZER
                ]
            ]
        ]
    ];

    /**
     * Elasticsearch constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->client = $config->getElasticsearch();
        $this->esConfig = $config->getElasticsearchConfig();
    }

    /**
     * Creates the Elasticsearch index.
     *
     * @return bool
     */
    public function createIndex(): bool
    {
        $this->client->indices()->create($this->getParams());
        return $this->putMapping();
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
     *
     * @return bool
     */
    public function putMapping(): bool
    {
        $response = $this->getMapping();

        if (0 === count($response[$this->esConfig['index']]['mappings'])) {
            $params = [
                'index' => $this->esConfig['index'],
                'type' => $this->esConfig['type'],
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
     * @return array<string, mixed>
     */
    public function getMapping(): array
    {
        return $this->client->indices()->getMapping();
    }

    /**
     * Deletes the Elasticsearch index.
     *
     * @return string[]
     */
    public function dropIndex(): array
    {
        return $this->client->indices()->delete(['index' => $this->esConfig['index']]);
    }

    /**
     * Indexing of a FAQ
     *
     * @param string[] $faq
     * @return string[]
     */
    public function index(array $faq): array
    {
        $params = [
            'index' => $this->esConfig['index'],
            'type' => $this->esConfig['type'],
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
        $responses = [];
        $i = 1;

        foreach ($faqs as $faq) {
            if ('no' === $faq['active']) {
                continue;
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $this->esConfig['index'],
                    '_type' => $this->esConfig['type'],
                    '_id' => $faq['solution_id'],
                ]
            ];

            $params['body'][] = [
                'id' => $faq['id'],
                'lang' => $faq['lang'],
                'question' => $faq['title'],
                'answer' => strip_tags($faq['content']),
                'keywords' => $faq['keywords'],
                'category_id' => $faq['category_id']
            ];

            if ($i % 1000 == 0) {
                $responses = $this->client->bulk($params);
                $params = ['body' => []];
                unset($responses);
            }

            $i++;
        }

        // Send the last batch if it exists
        if (!empty($params['body'])) {
            $responses = $this->client->bulk($params);
        }

        if (isset($responses) && count($responses)) {
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
            'type' => $this->esConfig['type'],
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

        return $this->client->update($params);
    }

    /**
     * Deletes a FAQ document
     *
     * @param int $solutionId
     * @return string[]
     */
    public function delete(int $solutionId): array
    {
        $params = [
            'index' => $this->esConfig['index'],
            'type' => $this->esConfig['type'],
            'id' => $solutionId
        ];

        return $this->client->delete($params);
    }
}
