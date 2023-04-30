<?php

/**
 * phpMyFAQ Elasticsearch based search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-25
 */

namespace phpMyFAQ\Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use stdClass;

/**
 * Class Elasticsearch
 *
 * @package phpMyFAQ\Search
 */
class Elasticsearch extends AbstractSearch implements SearchInterface
{
    private readonly Client $client;

    private readonly ElasticsearchConfiguration $esConfig;

    private string $language = '';

    /**@var int[] */
    private array $categoryIds = [];

    /**
     * Constructor.
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);

        $this->client = $this->config->getElasticsearch();
        $this->esConfig = $this->config->getElasticsearchConfig();
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
        $searchParams = [
            'index' => $this->esConfig->getIndex(),
            'size' => 100,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'multi_match' => [
                                'fields' => [
                                    'question',
                                    'answer',
                                    'keywords'
                                ],
                                'query' => $searchTerm,
                                'fuzziness' => 'AUTO'
                            ]
                        ],
                        'filter' => [
                            'terms' => ['category_id' => $this->getCategoryIds()]
                        ],
                    ]
                ]
            ]
        ];

        try {
            $result = $this->client->search($searchParams)->asArray();
        } catch (ClientResponseException | ServerResponseException) {
            $this->resultSet = [];
        }

        if ($result['hits']['total']['value'] > 0) {
            foreach ($result['hits']['hits'] as $hit) {
                $resultSet = new stdClass();
                $resultSet->id = $hit['_source']['id'];
                $resultSet->lang = $hit['_source']['lang'];
                $resultSet->question = $hit['_source']['question'];
                $resultSet->answer = $hit['_source']['answer'];
                $resultSet->keywords = $hit['_source']['keywords'];
                $resultSet->category_id = $hit['_source']['category_id'];
                $resultSet->score = $hit['_score'];

                $this->resultSet[] = $resultSet;
            }
        } else {
            $this->resultSet = [];
        }

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
            'index' => $this->esConfig->getIndex(),
            'size' => 100,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'multi_match' => [
                                'fields' => [
                                    'question',
                                    'answer',
                                    'keywords'
                                ],
                                'query' => $searchTerm,
                                'fuzziness' => 'AUTO'
                            ]
                        ],
                        'filter' => [
                            'term' => [
                                'lang' => $this->getLanguage()
                            ]
                        ]
                    ]
                ]
            ]
        ];

        try {
            $result = $this->client->search($searchParams)->asArray();
        } catch (ClientResponseException | ServerResponseException) {
            return [];
        }

        if (0 !== $result['hits']['total']['value'] || 0 !== $result['hits']['total']) {
            foreach ($result['hits']['hits'] as $hit) {
                $resultSet = new stdClass();
                $resultSet->id = $hit['_source']['id'];
                $resultSet->lang = $hit['_source']['lang'];
                $resultSet->question = $hit['_source']['question'];
                $resultSet->answer = $hit['_source']['answer'];
                $resultSet->keywords = $hit['_source']['keywords'];
                $resultSet->category_id = $hit['_source']['category_id'];
                $resultSet->score = $hit['_score'];

                $this->resultSet[] = $resultSet;
            }
        } else {
            $this->resultSet = [];
        }

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
