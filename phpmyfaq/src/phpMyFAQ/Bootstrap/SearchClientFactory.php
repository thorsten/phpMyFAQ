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

use Elastic\Elasticsearch\Client;
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
     *
     * @param array<string, bool|string> $tlsOptions Symfony HttpClient TLS options
     *                                               (verify_peer, verify_host, cafile, capath)
     */
    public static function waitForHealthy(
        string $baseUri,
        int $timeoutSeconds = 15,
        ?HttpClientInterface $httpClient = null,
        ?callable $httpClientFactory = null,
        array $tlsOptions = [],
    ): void {
        try {
            $http = $httpClient;
            if ($http === null && $httpClientFactory !== null) {
                $createdHttpClient = $httpClientFactory();
                if ($createdHttpClient instanceof HttpClientInterface) {
                    $http = $createdHttpClient;
                }
            }

            // Peer verification stays on unless the search configuration file disables it
            $http ??= HttpClient::create($tlsOptions + ['verify_peer' => true, 'verify_host' => true]);
            $deadline = time() + $timeoutSeconds;
            do {
                try {
                    $res = $http->request('GET', rtrim($baseUri, characters: '/') . '/_cluster/health');
                    $code = $res->getStatusCode();
                    if ($code >= 200 && $code < 500) {
                        break;
                    }
                } catch (Throwable $exception) {
                    unset($exception);
                }
                usleep(500_000);
            } while (time() < $deadline);
        } catch (Throwable $exception) {
            unset($exception);
        }
    }

    /**
     * Configures the Elasticsearch client and attaches it to the Configuration.
     */
    public static function configureElasticsearch(
        Configuration $faqConfig,
        string $configDir,
        ?HttpClientInterface $httpClient = null,
        ?callable $clientFactory = null,
    ): void {
        require $configDir . '/constants_elasticsearch.php';
        $esConfig = new ElasticsearchConfiguration($configDir . '/elasticsearch.php');

        $esBaseUri = $_ENV['ELASTICSEARCH_BASE_URI'] ?? $esConfig->getHosts()[0];

        self::waitForHealthy(
            $esBaseUri,
            (int) ($_ENV['SEARCH_WAIT_TIMEOUT'] ?? 15),
            $httpClient,
            tlsOptions: $esConfig->getTlsClientOptions(),
        );

        try {
            $esClient = null;
            if ($clientFactory !== null) {
                $createdClient = $clientFactory($esBaseUri);
                if ($createdClient instanceof Client) {
                    $esClient = $createdClient;
                }
            }

            $esClient ??= self::buildElasticsearchClient($esBaseUri, $esConfig);
            $faqConfig->setElasticsearch($esClient);
            $faqConfig->setElasticsearchConfig($esConfig);
        } catch (AuthenticationException $exception) {
            unset($exception);
        }
    }

    public static function buildElasticsearchClient(string $baseUri, ElasticsearchConfiguration $esConfig): Client
    {
        $builder = ClientBuilder::create()
            ->setHosts([$baseUri])
            ->setSSLVerification($esConfig->isPeerVerificationEnabled());

        $caFile = $esConfig->getCaFile();
        if ($caFile !== null) {
            $builder->setCABundle($caFile);
        }

        return $builder->build();
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

        self::waitForHealthy(
            $baseUri,
            (int) ($_ENV['SEARCH_WAIT_TIMEOUT'] ?? 15),
            $httpClient,
            tlsOptions: $openSearchConfig->getTlsClientOptions(),
        );

        $client = new SymfonyClientFactory()->create(['base_uri' => $baseUri]
        + $openSearchConfig->getTlsClientOptions());
        $faqConfig->setOpenSearch($client);
        $faqConfig->setOpenSearchConfig($openSearchConfig);
    }
}
