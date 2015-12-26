<?php

use Elasticsearch\Client;

/**
 * phpMyFAQ Elasticsearch based search classes.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
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
 * PMF_Search_Elasticsearch.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2015-12-25
 */
class PMF_Search_Elasticsearch extends PMF_Search_Abstract implements PMF_Search_Interface
{
    /** @var Client */
    private $client = null;

    /** @var array */
    private $esConfig = [];

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
     * @param PMF_Configuration
     */
    public function __construct(PMF_Configuration $config)
    {
        parent::__construct($config);

        $this->client = $this->_config->getElasticsearch();
        $this->esConfig = $this->_config->getElasticsearchConfig();
    }

    /**
     * Prepares the search and executes it.
     *
     * @param string $searchTerm Search term
     *
     * @return resource
     *
     * @throws PMF_Search_Exception
     */
    public function search($searchTerm)
    {

    }

    public function index(Array $faq)
    {

    }

    public function bulkIndex(Array $faqs)
    {

    }

    public function update(Array $faq)
    {

    }

    public function delete($faqId)
    {

    }

    /**
     * Puts phpMyFAQ Elasticsearch mapping into index.
     *
     * @return bool
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
}
