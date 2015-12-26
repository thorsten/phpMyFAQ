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
 * @copyright 2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
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
 * @copyright 2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2015-12-25
 */
class PMF_Instance_Elasticsearch
{
    /** @var PMF_Configuration */
    protected $config;

    /** @var Client */
    protected $client;

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
                    'analyzer' => 'standard'
                ],
                'answer' => [
                    'type' => 'string',
                    'analyzer' => 'standard'
                ],
                'keywords' => [
                    'type' => 'string',
                    'analyzer' => 'standard'
                ],
                'categories' => [
                    'type' => 'string',
                    'analyzer' => 'standard'
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
        $this->client = $config->getElasticsearch();
        $this->config = $config->getElasticsearchConfig();
    }

    /**
     * Creates the Elasticsearch index.
     *
     * @return array
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
        return $this->client->indices()->delete(['index' => $this->config['index']]);
    }

    /**
     * Puts phpMyFAQ Elasticsearch mapping into index.
     *
     * @return bool
     */
    public function putMapping()
    {
        $response = $this->getMapping();

        if (0 === count($response[$this->config['index']]['mappings'])) {

            $params = [
                'index' => $this->config['index'],
                'type' => $this->config['type'],
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

    public function index(Array $faq)
    {

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
            $params['body'][] = [
                'index' => [
                    '_index' => $this->config['index'],
                    '_type' => $this->config['type'],
                    '_id' => $i
                ]
            ];

            $params['body'][] = [
                'id' => $faq['id'],
                'question' => $faq['title'],
                'answer' => $faq['content'],
                'keywords' => $faq['keywords'],
                'categories' => $faq['category_id']
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

    public function update(Array $faq)
    {

    }

    public function delete($faqId)
    {

    }

    /**
     * Returns the basic phpMyFAQ index structure as raw array.
     *
     * @return array
     */
    private function getParams()
    {
        return [
            'index' => $this->config['index'],
            'body' => [
                'settings' => [
                    'number_of_shards' => 2,
                    'number_of_replicas' => 0
                ]
            ]
        ];
    }
}
