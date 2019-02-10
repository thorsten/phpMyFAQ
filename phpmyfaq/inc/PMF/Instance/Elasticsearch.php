<?php

use Elasticsearch\Client;

/**
 * The phpMyFAQ instances basic Elasticsearch class.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-25
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Instance_Elasticsearch.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-25
 */
class PMF_Instance_Elasticsearch
{
    /** @var PMF_Configuration */
    protected $config;

    /** @var Client */
    protected $client;

    /** @var array */
    protected $esConfig;

    /**
     * Elasticsearch mapping
     *
     * @var array
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
                    'type' => 'string',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => 'standard'
                ],
                'answer' => [
                    'type' => 'string',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => 'standard'
                ],
                'keywords' => [
                    'type' => 'string',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => 'standard'
                ],
                'categories' => [
                    'type' => 'string',
                    'analyzer' => 'autocomplete',
                    'search_analyzer' => 'standard'
                ]
            ]
        ]
    ];
    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
        $this->client = $config->getElasticsearch();
        $this->esConfig = $config->getElasticsearchConfig();
    }

    /**
     * Creates the Elasticsearch index.
     *
     * @return boolean
     */
    public function createIndex()
    {
        $this->client->indices()->create($this->getParams());
        return $this->putMapping();;
    }

    /**
     * Deletes the Elasticsearch index.
     *
     * @return array
     */
    public function dropIndex()
    {
        return $this->client->indices()->delete(['index' => $this->esConfig['index']]);
    }

    /**
     * Puts phpMyFAQ Elasticsearch mapping into index.
     *
     * @return boolean
     */
    public function putMapping()
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
     * @return array
     */
    public function getMapping()
    {
        return $this->client->indices()->getMapping();
    }

    /**
     * Indexing of a FAQ
     *
     * @param array $faq
     *
     * @return array
     */
    public function index(Array $faq)
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
     * @param array $faqs
     *
     * @return array
     */
    public function bulkIndex(Array $faqs)
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

        return $responses;
    }

    /**
     * Updates a FAQ document
     *
     * @param array $faq
     * @return array
     */
    public function update(Array $faq)
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
     * @param integer $solutionId
     * @return array
     */
    public function delete($solutionId)
    {
        $params = [
            'index' => $this->esConfig['index'],
            'type' => $this->esConfig['type'],
            'id' => $solutionId
        ];

        return $this->client->delete($params);
    }

    /**
     * Returns the basic phpMyFAQ index structure as raw array.
     *
     * @return array
     */
    private function getParams()
    {
        global $PMF_ELASTICSEARCH_STEMMING_LANGUAGE;

        return [
            'index' => $this->esConfig['index'],
            'body' => [
                'settings' => [
                    'number_of_shards' => PMF_ELASTICSEARCH_NUMBER_SHARDS,
                    'number_of_replicas' => PMF_ELASTICSEARCH_NUMBER_REPLICAS,
                    'analysis' => [
                        'filter' => [
                            'autocomplete_filter' => [
                                'type' =>'edge_ngram',
                                'min_gram' => 1,
                                'max_gram' => 20
                            ],
                            'pmf_language_stemmer'=>[
                                'type' => 'stemmer',
                                'name' => $PMF_ELASTICSEARCH_STEMMING_LANGUAGE[$this->config->getDefaultLanguage()]
                            ]
                        ],
                        'analyzer' => [
                            'autocomplete' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase',
                                    'autocomplete_filter',
                                    'pmf_language_stemmer'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
