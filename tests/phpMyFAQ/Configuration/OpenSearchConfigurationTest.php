<?php

namespace phpMyFAQ\Configuration;

use PHPUnit\Framework\TestCase;

/**
 * Test class for OpenSearchConfiguration
 */
class OpenSearchConfigurationTest extends TestCase
{
    private string $testConfigDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Erstelle temporäres Verzeichnis für Testkonfigurationen
        $this->testConfigDir = sys_get_temp_dir() . '/opensearch_config_tests_' . uniqid();
        mkdir($this->testConfigDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Aufräumen nach Tests
        $this->removeDirectory($this->testConfigDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                is_dir($path) ? $this->removeDirectory($path) : unlink($path);
            }
            rmdir($dir);
        }
    }

    private function createConfigFile(string $filename, array $config): string
    {
        $filepath = $this->testConfigDir . '/' . $filename;
        $content = '<?php' . PHP_EOL;
        $content .= '$PMF_OS = ' . var_export($config, true) . ';' . PHP_EOL;
        file_put_contents($filepath, $content);
        return $filepath;
    }

    public function testConstructorWithValidConfiguration(): void
    {
        $config = [
            'hosts' => ['localhost:9200', 'opensearch.example.com:9200'],
            'index' => 'phpmyfaq_opensearch'
        ];

        $configFile = $this->createConfigFile('valid_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertInstanceOf(OpenSearchConfiguration::class, $osConfig);
        $this->assertEquals($config['hosts'], $osConfig->getHosts());
        $this->assertEquals($config['index'], $osConfig->getIndex());
    }

    public function testConstructorWithMinimalConfiguration(): void
    {
        $config = [
            'hosts' => [],
            'index' => ''
        ];

        $configFile = $this->createConfigFile('minimal_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertEquals([], $osConfig->getHosts());
        $this->assertEquals('', $osConfig->getIndex());
    }

    public function testConstructorWithSingleNodeConfiguration(): void
    {
        $config = [
            'hosts' => ['https://localhost:9200'],
            'index' => 'single_node_index'
        ];

        $configFile = $this->createConfigFile('single_node_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertCount(1, $osConfig->getHosts());
        $this->assertEquals('https://localhost:9200', $osConfig->getHosts()[0]);
        $this->assertEquals('single_node_index', $osConfig->getIndex());
    }

    public function testConstructorWithMultiNodeClusterConfiguration(): void
    {
        $config = [
            'hosts' => [
                'https://node1.opensearch.cluster.local:9200',
                'https://node2.opensearch.cluster.local:9200',
                'https://node3.opensearch.cluster.local:9200'
            ],
            'index' => 'production_cluster_index'
        ];

        $configFile = $this->createConfigFile('multi_node_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertCount(3, $osConfig->getHosts());
        $this->assertEquals($config['hosts'], $osConfig->getHosts());
        $this->assertEquals('production_cluster_index', $osConfig->getIndex());
    }

    public function testConstructorWithAwsOpenSearchService(): void
    {
        $config = [
            'hosts' => [
                'https://search-my-domain.us-east-1.es.amazonaws.com'
            ],
            'index' => 'aws_opensearch_index'
        ];

        $configFile = $this->createConfigFile('aws_opensearch_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertEquals($config['hosts'], $osConfig->getHosts());
        $this->assertEquals('aws_opensearch_index', $osConfig->getIndex());
    }

    public function testConstructorWithSecurityEnabledCluster(): void
    {
        $config = [
            'hosts' => [
                'https://admin:password@secure-opensearch.example.com:9200',
                'https://admin:password@secure-opensearch-2.example.com:9200'
            ],
            'index' => 'secure_index_with_auth'
        ];

        $configFile = $this->createConfigFile('secure_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertEquals($config['hosts'], $osConfig->getHosts());
        $this->assertEquals('secure_index_with_auth', $osConfig->getIndex());
    }

    public function testGetHostsReturnsCorrectArray(): void
    {
        $hosts = [
            'opensearch-cluster-1.internal:9200',
            'opensearch-cluster-2.internal:9200'
        ];

        $config = [
            'hosts' => $hosts,
            'index' => 'test_index'
        ];

        $configFile = $this->createConfigFile('hosts_test.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertIsArray($osConfig->getHosts());
        $this->assertEquals($hosts, $osConfig->getHosts());
        $this->assertSame($hosts, $osConfig->getHosts()); // Test reference equality
    }

    public function testGetIndexReturnsCorrectString(): void
    {
        $indexName = 'my_custom_opensearch_index_2025';

        $config = [
            'hosts' => ['localhost:9200'],
            'index' => $indexName
        ];

        $configFile = $this->createConfigFile('index_test.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertIsString($osConfig->getIndex());
        $this->assertEquals($indexName, $osConfig->getIndex());
    }

    public function testConstructorWithDevelopmentConfiguration(): void
    {
        $config = [
            'hosts' => ['http://localhost:9200'],
            'index' => 'dev_local_index'
        ];

        $configFile = $this->createConfigFile('dev_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertEquals(['http://localhost:9200'], $osConfig->getHosts());
        $this->assertEquals('dev_local_index', $osConfig->getIndex());
    }

    public function testConstructorWithProductionConfiguration(): void
    {
        $config = [
            'hosts' => [
                'https://prod-opensearch-1.company.com:443',
                'https://prod-opensearch-2.company.com:443',
                'https://prod-opensearch-3.company.com:443'
            ],
            'index' => 'prod_faq_knowledge_base'
        ];

        $configFile = $this->createConfigFile('prod_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertCount(3, $osConfig->getHosts());
        $this->assertEquals($config['hosts'], $osConfig->getHosts());
        $this->assertEquals('prod_faq_knowledge_base', $osConfig->getIndex());
    }

    public function testReadonlyPropertiesCannotBeModified(): void
    {
        $config = [
            'hosts' => ['localhost:9200'],
            'index' => 'readonly_test'
        ];

        $configFile = $this->createConfigFile('readonly_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        // Test dass alle Properties readonly sind
        $reflection = new \ReflectionClass(OpenSearchConfiguration::class);

        $hostsProperty = $reflection->getProperty('hosts');
        $this->assertTrue($hostsProperty->isReadOnly());

        $indexProperty = $reflection->getProperty('index');
        $this->assertTrue($indexProperty->isReadOnly());
    }

    public function testConstructorWithEmptyHostsArray(): void
    {
        $config = [
            'hosts' => [],
            'index' => 'empty_hosts_index'
        ];

        $configFile = $this->createConfigFile('empty_hosts_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertEmpty($osConfig->getHosts());
        $this->assertEquals('empty_hosts_index', $osConfig->getIndex());
    }

    public function testConstructorWithSpecialCharactersInIndex(): void
    {
        $config = [
            'hosts' => ['localhost:9200'],
            'index' => 'test-index_with.special-chars_2025'
        ];

        $configFile = $this->createConfigFile('special_chars_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertEquals('test-index_with.special-chars_2025', $osConfig->getIndex());
    }

    public function testConstructorWithDockerComposeConfiguration(): void
    {
        $config = [
            'hosts' => [
                'http://opensearch-node1:9200',
                'http://opensearch-node2:9200'
            ],
            'index' => 'docker_compose_index'
        ];

        $configFile = $this->createConfigFile('docker_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertEquals($config['hosts'], $osConfig->getHosts());
        $this->assertEquals('docker_compose_index', $osConfig->getIndex());
    }

    public function testConstructorWithKubernetesConfiguration(): void
    {
        $config = [
            'hosts' => [
                'https://opensearch-cluster.opensearch-system.svc.cluster.local:9200'
            ],
            'index' => 'k8s_cluster_index'
        ];

        $configFile = $this->createConfigFile('k8s_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertEquals($config['hosts'], $osConfig->getHosts());
        $this->assertEquals('k8s_cluster_index', $osConfig->getIndex());
    }

    public function testMultipleInstancesWithDifferentConfigurations(): void
    {
        // Test dass verschiedene Instanzen unabhängig voneinander funktionieren
        $config1 = [
            'hosts' => ['http://dev-opensearch:9200'],
            'index' => 'dev_index'
        ];

        $config2 = [
            'hosts' => ['https://prod-opensearch-1:9200', 'https://prod-opensearch-2:9200'],
            'index' => 'prod_index'
        ];

        $configFile1 = $this->createConfigFile('config1.php', $config1);
        $configFile2 = $this->createConfigFile('config2.php', $config2);

        $osConfig1 = new OpenSearchConfiguration($configFile1);
        $osConfig2 = new OpenSearchConfiguration($configFile2);

        $this->assertNotEquals($osConfig1->getHosts(), $osConfig2->getHosts());
        $this->assertNotEquals($osConfig1->getIndex(), $osConfig2->getIndex());

        $this->assertEquals(['http://dev-opensearch:9200'], $osConfig1->getHosts());
        $this->assertEquals(['https://prod-opensearch-1:9200', 'https://prod-opensearch-2:9200'], $osConfig2->getHosts());
        $this->assertEquals('dev_index', $osConfig1->getIndex());
        $this->assertEquals('prod_index', $osConfig2->getIndex());
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(OpenSearchConfiguration::class);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testConstructorWithCustomPortConfiguration(): void
    {
        $config = [
            'hosts' => [
                'https://opensearch-master:9200',
                'https://opensearch-data-1:9201',
                'https://opensearch-data-2:9202'
            ],
            'index' => 'custom_port_index'
        ];

        $configFile = $this->createConfigFile('custom_port_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertEquals($config['hosts'], $osConfig->getHosts());
        $this->assertEquals('custom_port_index', $osConfig->getIndex());
    }

    public function testConstructorWithPathBasedUrls(): void
    {
        $config = [
            'hosts' => [
                'https://example.com/opensearch',
                'https://backup.example.com/search-service'
            ],
            'index' => 'path_based_index'
        ];

        $configFile = $this->createConfigFile('path_based_config.php', $config);
        $osConfig = new OpenSearchConfiguration($configFile);

        $this->assertEquals($config['hosts'], $osConfig->getHosts());
        $this->assertEquals('path_based_index', $osConfig->getIndex());
    }
}
