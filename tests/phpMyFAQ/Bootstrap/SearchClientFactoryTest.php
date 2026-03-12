<?php

namespace phpMyFAQ\Bootstrap;

use Elastic\Elasticsearch\Exception\AuthenticationException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\LayoutSettings;
use phpMyFAQ\Configuration\LdapSettings;
use phpMyFAQ\Configuration\MailSettings;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use phpMyFAQ\Configuration\SearchSettings;
use phpMyFAQ\Configuration\SecuritySettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Configuration\Storage\HybridConfigurationStore;
use phpMyFAQ\Configuration\UrlSettings;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Environment;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(SearchClientFactory::class)]
#[AllowMockObjectsWithoutExpectations]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationRepository::class)]
#[UsesClass(ElasticsearchConfiguration::class)]
#[UsesClass(LayoutSettings::class)]
#[UsesClass(LdapSettings::class)]
#[UsesClass(MailSettings::class)]
#[UsesClass(OpenSearchConfiguration::class)]
#[UsesClass(SearchSettings::class)]
#[UsesClass(SecuritySettings::class)]
#[UsesClass(Sqlite3::class)]
#[UsesClass(UrlSettings::class)]
#[UsesClass(Environment::class)]
#[UsesClass(System::class)]
#[UsesClass(Translation::class)]
#[UsesClass(PluginManager::class)]
#[UsesClass(ConfigurationStorageSettingsResolver::class)]
#[UsesClass(DatabaseConfigurationStore::class)]
#[UsesClass(HybridConfigurationStore::class)]
class SearchClientFactoryTest extends TestCase
{
    private array $envBackup = [];

    protected function setUp(): void
    {
        $this->envBackup = [
            'ELASTICSEARCH_BASE_URI' => $_ENV['ELASTICSEARCH_BASE_URI'] ?? null,
            'OPENSEARCH_BASE_URI' => $_ENV['OPENSEARCH_BASE_URI'] ?? null,
            'SEARCH_WAIT_TIMEOUT' => $_ENV['SEARCH_WAIT_TIMEOUT'] ?? null,
        ];
    }

    protected function tearDown(): void
    {
        foreach ($this->envBackup as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
                continue;
            }

            $_ENV[$key] = $value;
        }
    }

    public function testWaitForHealthyReturnsImmediatelyOnSuccess(): void
    {
        $response = new MockResponse('{"status":"green"}', ['http_code' => 200]);
        $httpClient = new MockHttpClient([$response]);

        // Should return without exception and within the timeout
        SearchClientFactory::waitForHealthy('http://localhost:9200', 2, $httpClient);

        $this->assertEquals(1, $httpClient->getRequestsCount());
    }

    public function testWaitForHealthyRetriesOnFailure(): void
    {
        $responses = [
            new MockResponse('', ['http_code' => 503]),
            new MockResponse('{"status":"yellow"}', ['http_code' => 200]),
        ];
        $httpClient = new MockHttpClient($responses);

        SearchClientFactory::waitForHealthy('http://localhost:9200', 5, $httpClient);

        $this->assertEquals(2, $httpClient->getRequestsCount());
    }

    public function testWaitForHealthyDoesNotThrowOnTimeout(): void
    {
        // All requests will fail, but should not throw
        $httpClient = new MockHttpClient(function () {
            throw new \RuntimeException('Connection refused');
        });

        SearchClientFactory::waitForHealthy('http://localhost:9200', 1, $httpClient);

        $this->assertTrue(true);
    }

    public function testWaitForHealthyTrimsTrailingSlash(): void
    {
        $response = new MockResponse('{"status":"green"}', ['http_code' => 200]);
        $httpClient = new MockHttpClient([$response]);

        SearchClientFactory::waitForHealthy('http://localhost:9200/', 2, $httpClient);

        $this->assertEquals(1, $httpClient->getRequestsCount());
    }

    public function testWaitForHealthySwallowsClientFactoryExceptions(): void
    {
        SearchClientFactory::waitForHealthy(
            'http://localhost:9200',
            1,
            null,
            static fn() => throw new \RuntimeException('factory failed'),
        );

        $this->assertTrue(true);
    }

    public function testConfigureElasticsearchAttachesClientAndConfigUsingEnvironmentBaseUri(): void
    {
        $configDir = $this->createSearchConfigDirectory('elasticsearch');
        $_ENV['ELASTICSEARCH_BASE_URI'] = 'http://env-elastic:9200';
        $_ENV['SEARCH_WAIT_TIMEOUT'] = '1';

        $response = new MockResponse('{"status":"green"}', ['http_code' => 200]);
        $httpClient = new MockHttpClient([$response]);
        $configuration = $this->createConfiguration();

        SearchClientFactory::configureElasticsearch($configuration, $configDir, $httpClient);

        $this->assertSame(['http://localhost:9200'], $configuration->getElasticsearchConfig()->getHosts());
        $this->assertSame('pmf', $configuration->getElasticsearchConfig()->getIndex());
        $this->assertInstanceOf(\Elastic\Elasticsearch\Client::class, $configuration->getElasticsearch());
        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testConfigureElasticsearchSwallowsAuthenticationExceptions(): void
    {
        $configDir = $this->createSearchConfigDirectory('elasticsearch');
        $_ENV['ELASTICSEARCH_BASE_URI'] = 'http://env-elastic:9200';
        $_ENV['SEARCH_WAIT_TIMEOUT'] = '1';

        $response = new MockResponse('{"status":"green"}', ['http_code' => 200]);
        $httpClient = new MockHttpClient([$response]);
        $configuration = $this->createConfiguration();

        SearchClientFactory::configureElasticsearch(
            $configuration,
            $configDir,
            $httpClient,
            static fn() => throw new AuthenticationException('denied'),
        );

        $reflection = new ReflectionClass($configuration);
        $configProperty = $reflection->getProperty('config');
        $config = $configProperty->getValue($configuration);

        $this->assertArrayNotHasKey('core.elasticsearch', $config);
        $this->assertArrayNotHasKey('core.elasticsearchConfig', $config);
    }

    public function testConfigureOpenSearchAttachesClientAndConfigUsingEnvironmentBaseUri(): void
    {
        $configDir = $this->createSearchConfigDirectory('opensearch');
        $_ENV['OPENSEARCH_BASE_URI'] = 'http://env-opensearch:9200';
        $_ENV['SEARCH_WAIT_TIMEOUT'] = '1';

        $response = new MockResponse('{"status":"green"}', ['http_code' => 200]);
        $httpClient = new MockHttpClient([$response]);
        $configuration = $this->createConfiguration();

        SearchClientFactory::configureOpenSearch($configuration, $configDir, $httpClient);

        $this->assertSame(['http://localhost:9201'], $configuration->getOpenSearchConfig()->getHosts());
        $this->assertSame('pmf', $configuration->getOpenSearchConfig()->getIndex());
        $this->assertInstanceOf(\OpenSearch\Client::class, $configuration->getOpenSearch());
        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    private function createConfiguration(): Configuration
    {
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');

        return new Configuration($dbHandle);
    }

    private function createSearchConfigDirectory(string $type): string
    {
        $configDir = sys_get_temp_dir() . '/pmf-search-config-' . $type . '-' . uniqid('', true);
        mkdir($configDir, 0777, true);

        if ($type === 'elasticsearch') {
            file_put_contents($configDir . '/constants_elasticsearch.php', "<?php\n");
            file_put_contents(
                $configDir . '/elasticsearch.php',
                "<?php\n\$PMF_ES = ['hosts' => ['http://localhost:9200'], 'index' => 'pmf'];\n",
            );
        }

        if ($type === 'opensearch') {
            file_put_contents($configDir . '/constants_opensearch.php', "<?php\n");
            file_put_contents(
                $configDir . '/opensearch.php',
                "<?php\n\$PMF_OS = ['hosts' => ['http://localhost:9201'], 'index' => 'pmf'];\n",
            );
        }

        return $configDir;
    }
}
