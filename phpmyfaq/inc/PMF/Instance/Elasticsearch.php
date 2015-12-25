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
     * Constructor.
     *
     * @param PMF_Configuration $config
     */
    public function __construct(PMF_Configuration $config, Client $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Creates the Elasticsearch index.
     *
     */
    public function createIndex()
    {
        $response = $this->client->indices()->create($this->getParams());


    }

    /**
     * Deletes the Elasticsearch index.
     *
     */
    public function dropIndex()
    {
        $config = $this->config->getElasticsearchConfig();

        $response = $this->client->indices()->delete(['index' => $config['index']]);
    }

    /**
     * Returns the basic phpMyFAQ index structure as raw array.
     *
     * @return array
     */
    private function getParams()
    {
        $config = $this->config->getElasticsearchConfig();

        return [
            'index' => $config['index'],
            'body' => [
                'settings' => [
                    'number_of_shards' => 2,
                    'number_of_replicas' => 1
                ]
            ]
        ];
    }
}
