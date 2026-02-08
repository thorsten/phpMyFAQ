<?php

/**
 * Search engine client creation and health-check logic for phpMyFAQ bootstrap
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Bootstrap;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use OpenSearch\SymfonyClientFactory;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class SearchClientFactory
{
    /**
     * Polls the search engine health endpoint until it responds with a 2xx–4xx status.
     */
    public static function waitForHealthy(
        string $baseUri,
        int $timeoutSeconds = 15,
        ?HttpClientInterface $httpClient = null,
    ): void {
        try {
            $http = $httpClient ?? HttpClient::create(['verify_peer' => false]);
            $deadline = time() + $timeoutSeconds;
            do {
                try {
                    $res = $http->request('GET', rtrim($baseUri, characters: '/') . '/_cluster/health');
                    $code = $res->getStatusCode();
                    if ($code >= 200 && $code < 500) {
                        break;
                    }
                } catch (Throwable) {
                    // ignore and retry
                }
                usleep(500_000);
            } while (time() < $deadline);
        } catch (Throwable) {
            // do not block bootstrap if a health check fails
        }
    }

    /**
     * Configures the Elasticsearch client and attaches it to the Configuration.
     */
    public static function configureElasticsearch(
        Configuration $faqConfig,
        string $configDir,
        ?HttpClientInterface $httpClient = null,
    ): void {
        require $configDir . '/constants_elasticsearch.php';
        $esConfig = new ElasticsearchConfiguration($configDir . '/elasticsearch.php');

        $esBaseUri = $_ENV['ELASTICSEARCH_BASE_URI'] ?? $esConfig->getHosts()[0];

        self::waitForHealthy($esBaseUri, (int) ($_ENV['SEARCH_WAIT_TIMEOUT'] ?? 15), $httpClient);

        try {
            $esClient = ClientBuilder::create()->setHosts([$esBaseUri])->build();
            $faqConfig->setElasticsearch($esClient);
            $faqConfig->setElasticsearchConfig($esConfig);
        } catch (AuthenticationException $e) {
            // @handle AuthenticationException
        }
    }

    /**
     * Configures the OpenSearch client and attaches it to the Configuration.
     */
    public static function configureOpenSearch(
        Configuration $faqConfig,
        string $configDir,
        ?HttpClientInterface $httpClient = null,
    ): void {
        require $configDir . '/constants_opensearch.php';
        $openSearchConfig = new OpenSearchConfiguration($configDir . '/opensearch.php');

        $baseUri = $_ENV['OPENSEARCH_BASE_URI'] ?? $openSearchConfig->getHosts()[0];

        self::waitForHealthy($baseUri, (int) ($_ENV['SEARCH_WAIT_TIMEOUT'] ?? 15), $httpClient);

        $client = new SymfonyClientFactory()->create([
            'base_uri' => $baseUri,
            'verify_peer' => false,
        ]);
        $faqConfig->setOpenSearch($client);
        $faqConfig->setOpenSearchConfig($openSearchConfig);
    }
}
