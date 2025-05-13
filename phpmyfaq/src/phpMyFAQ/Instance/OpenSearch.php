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
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-11-20
 */

namespace phpMyFAQ\Instance;

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

    private OpenSearchConfiguration $osConfig;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct(private Configuration $configuration)
    {
        $this->client = $this->configuration->getOpenSearch();
        $this->osConfig = $this->configuration->getOpenSearchConfig();

        $this->mappings = [
            'properties' => [
                'id' => ['type' => 'integer'],
                'lang' => ['type' => 'keyword'],
                'solution_id' => ['type' => 'integer'],
                'question' => [
                    'type' => 'text',
                    'analyzer' => 'autocomplete'
                ],
                'answer' => [
                    'type' => 'text',
                    'analyzer' => 'autocomplete'
                ],
                'keywords' => [
                    'type' => 'text',
                    'analyzer' => 'autocomplete'
                ],
                'category_id' => ['type' => 'integer']
            ]
        ];
    }

    /**
     * Creates the OpenSearch index.
     *
     * @throws Exception
     */
    public function createIndex(): bool
    {
        $result = $this->client->indices()->exists(['index' => $this->osConfig->getIndex()]);

        if (!$result) {
            $this->client->indices()->create($this->getParams());
        }

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
            'index' => $this->osConfig->getIndex(),
            'body' => [
                'settings' => [
                    'number_of_shards' => PMF_OPENSEARCH_NUMBER_SHARDS,
                    'number_of_replicas' => PMF_OPENSEARCH_NUMBER_REPLICAS,
                    'analysis' => [
                        'filter' => [
                            'autocomplete_filter' => [
                                'type' => 'edge_ngram',
                                'min_gram' => 1,
                                'max_gram' => 20
                            ],
                            'Language_stemmer' => [
                                'type' => 'stemmer',
                                'name' => PMF_OPENSEARCH_STEMMING_LANGUAGE[
                                $this->configuration->getDefaultLanguage()
                                ]
                            ]
                        ],
                        'analyzer' => [
                            'autocomplete' => [
                                'type' => 'custom',
                                'tokenizer' => PMF_OPENSEARCH_TOKENIZER,
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
     * Puts phpMyFAQ OpenSearch mapping into index.
     *
     * @throws Exception
     */
    public function putMapping(): bool
    {
        $response = $this->getMapping();

        if (
            0 === (
            is_countable($response[$this->osConfig->getIndex()]['mappings'])
                ?
                count($response[$this->osConfig->getIndex()]['mappings'])
                :
                0
            )
        ) {
            $params = [
                'index' => $this->osConfig->getIndex(),
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
        return $this->client->indices()->delete(['index' => $this->osConfig->getIndex()]);
    }

    /**
     * Indexing of a FAQ
     *
     * @param string[] $faq
     */
    public function index(array $faq): array
    {
        $params = [
            'index' => $this->osConfig->getIndex(),
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
        $i = 1;

        foreach ($faqs as $faq) {
            if ('no' === $faq['active']) {
                continue;
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $this->osConfig->getIndex(),
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
                $responses = $this->client->bulk($params);

                $params = ['body' => []];
                unset($responses);
            }

            ++$i;
        }

        // Send the last batch if it exists
        $responses = $this->client->bulk($params);

        if (isset($responses)) {
            return ['success' => $responses];
        }

        return ['error' => 'Unknown error.'];
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
            'index' => $this->osConfig->getIndex(),
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
     * @return string[]
     */
    public function delete(int $solutionId): array
    {
        $params = [
            'index' => $this->osConfig->getIndex(),
            'id' => $solutionId
        ];

        return $this->client->delete($params);
    }
}
