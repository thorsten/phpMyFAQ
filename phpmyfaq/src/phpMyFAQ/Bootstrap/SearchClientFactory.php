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
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class SearchClientFactory
{
    /**
     * Seconds a web request waits for the connection to the search engine.
     */
    public const int WEB_CONNECT_TIMEOUT = 2;

    /**
     * Seconds a web request waits for an answer of the search engine, unless SEARCH_REQUEST_TIMEOUT is set.
     */
    public const int WEB_REQUEST_TIMEOUT = 5;

    /**
     * Symfony HttpClient options that keep an unreachable search engine from blocking a web request:
     * the search then fails after a few seconds and the caller falls back to the database.
     *
     * @return array{max_connect_duration: int, timeout: int}
     */
    public static function webHttpClientOptions(): array
    {
        $requestTimeout = (int) ($_ENV['SEARCH_REQUEST_TIMEOUT'] ?? self::WEB_REQUEST_TIMEOUT);

        return [
            'max_connect_duration' => self::WEB_CONNECT_TIMEOUT,
            'timeout' => $requestTimeout > 0 ? $requestTimeout : self::WEB_REQUEST_TIMEOUT,
        ];
    }

    /**
     * Symfony HttpClient options for the index operations of the admin panel (create, import, drop):
     * connecting stays limited, but a large bulk import may take longer to answer than a search.
     *
     * @return array{max_connect_duration: int}
     */
    public static function longRunningHttpClientOptions(): array
    {
        return ['max_connect_duration' => self::WEB_CONNECT_TIMEOUT];
    }

    /**
     * Polls the search engine health endpoint until it responds with a 2xx–4xx status.
     *
     * @param (callable(): HttpClientInterface)|null $httpClientFactory
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
                $http = $httpClientFactory();
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
     * Configures the Elasticsearch client and attaches it to the Configuration. Building the
     * client does not contact the cluster.
     *
     * @param (callable(string): Client)|null $clientFactory
     * @param bool $waitForCluster True where waiting for the cluster is the point (command line):
     *                             polls the cluster health for up to SEARCH_WAIT_TIMEOUT seconds
     *                             and builds a client without timeouts. False for web requests:
     *                             no round-trip, and a client with short timeouts.
     */
    public static function configureElasticsearch(
        Configuration $faqConfig,
        string $configDir,
        ?HttpClientInterface $httpClient = null,
        ?callable $clientFactory = null,
        bool $waitForCluster = false,
    ): void {
        require $configDir . '/constants_elasticsearch.php';
        $esConfig = new ElasticsearchConfiguration($configDir . '/elasticsearch.php');

        $esBaseUri = $_ENV['ELASTICSEARCH_BASE_URI'] ?? $esConfig->getHosts()[0];

        if ($waitForCluster) {
            self::waitForHealthy(
                $esBaseUri,
                (int) ($_ENV['SEARCH_WAIT_TIMEOUT'] ?? 15),
                $httpClient,
                tlsOptions: $esConfig->getTlsClientOptions(),
            );
        }

        try {
            $esClient = $clientFactory !== null
                ? $clientFactory($esBaseUri)
                : self::buildElasticsearchClient(
                    $esBaseUri,
                    $esConfig,
                    $waitForCluster ? [] : self::webHttpClientOptions(),
                );
            $faqConfig->setElasticsearch($esClient);
            $faqConfig->setElasticsearchConfig($esConfig);
        } catch (AuthenticationException $exception) {
            unset($exception);
        }
    }

    /**
     * @param array<string, int> $httpClientOptions Symfony HttpClient options, e.g. the timeouts of a web request
     */
    public static function buildElasticsearchClient(
        string $baseUri,
        ElasticsearchConfiguration $esConfig,
        array $httpClientOptions = [],
    ): Client {
        $builder = ClientBuilder::create()
            ->setHosts([$baseUri])
            ->setSSLVerification($esConfig->isPeerVerificationEnabled());

        if ($httpClientOptions !== []) {
            // The options are Symfony HttpClient options, so the client must not be left to discovery
            $builder->setHttpClient(new Psr18Client())->setHttpClientOptions($httpClientOptions);
        }

        $caFile = $esConfig->getCaFile();
        if ($caFile !== null) {
            $builder->setCABundle($caFile);
        }

        return $builder->build();
    }

    /**
     * Builds an Elasticsearch client for the index operations of the admin panel.
     * See longRunningHttpClientOptions().
     *
     * @throws AuthenticationException
     */
    public static function buildLongRunningElasticsearchClient(ElasticsearchConfiguration $esConfig): Client
    {
        return self::buildElasticsearchClient(
            $_ENV['ELASTICSEARCH_BASE_URI'] ?? $esConfig->getHosts()[0],
            $esConfig,
            self::longRunningHttpClientOptions(),
        );
    }

    /**
     * Configures the OpenSearch client and attaches it to the Configuration. Building the
     * client does not contact the cluster.
     *
     * @param bool $waitForCluster See configureElasticsearch()
     */
    public static function configureOpenSearch(
        Configuration $faqConfig,
        string $configDir,
        ?HttpClientInterface $httpClient = null,
        bool $waitForCluster = false,
    ): void {
        require $configDir . '/constants_opensearch.php';
        $openSearchConfig = new OpenSearchConfiguration($configDir . '/opensearch.php');

        $baseUri = $_ENV['OPENSEARCH_BASE_URI'] ?? $openSearchConfig->getHosts()[0];

        if ($waitForCluster) {
            self::waitForHealthy(
                $baseUri,
                (int) ($_ENV['SEARCH_WAIT_TIMEOUT'] ?? 15),
                $httpClient,
                tlsOptions: $openSearchConfig->getTlsClientOptions(),
            );
        }

        $client = new SymfonyClientFactory()->create(['base_uri' => $baseUri]
        + $openSearchConfig->getTlsClientOptions()
        + ($waitForCluster ? [] : self::webHttpClientOptions()));
        $faqConfig->setOpenSearch($client);
        $faqConfig->setOpenSearchConfig($openSearchConfig);
    }

    /**
     * Builds an OpenSearch client for the index operations of the admin panel.
     * See longRunningHttpClientOptions().
     */
    public static function buildLongRunningOpenSearchClient(
        OpenSearchConfiguration $openSearchConfig,
    ): \OpenSearch\Client {
        return new SymfonyClientFactory()->create([
            'base_uri' => $_ENV['OPENSEARCH_BASE_URI'] ?? $openSearchConfig->getHosts()[0],
        ]
        + $openSearchConfig->getTlsClientOptions()
        + self::longRunningHttpClientOptions());
    }
}
