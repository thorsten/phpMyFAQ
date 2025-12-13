<?php

namespace phpMyFAQ\Configuration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Test class for ElasticsearchConfiguration
 */
#[AllowMockObjectsWithoutExpectations]
class ElasticsearchConfigurationTest extends TestCase
{
    private string $testConfigDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Erstelle temporäres Verzeichnis für Testkonfigurationen
        $this->testConfigDir = sys_get_temp_dir() . '/elasticsearch_config_tests_' . uniqid();
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
        $content .= '$PMF_ES = ' . var_export($config, true) . ';' . PHP_EOL;
        file_put_contents($filepath, $content);
        return $filepath;
    }

    public function testConstructorWithValidConfiguration(): void
    {
        $config = [
            'hosts' => ['localhost:9200', 'elastic.example.com:9200'],
            'index' => 'phpmyfaq_test'
        ];

        $configFile = $this->createConfigFile('valid_config.php', $config);
        $esConfig = new ElasticsearchConfiguration($configFile);

        $this->assertInstanceOf(ElasticsearchConfiguration::class, $esConfig);
        $this->assertEquals($config['hosts'], $esConfig->getHosts());
        $this->assertEquals($config['index'], $esConfig->getIndex());
    }

    public function testConstructorWithMinimalConfiguration(): void
    {
        $config = [
            'hosts' => [],
            'index' => ''
        ];

        $configFile = $this->createConfigFile('minimal_config.php', $config);
        $esConfig = new ElasticsearchConfiguration($configFile);

        $this->assertEquals([], $esConfig->getHosts());
        $this->assertEquals('', $esConfig->getIndex());
    }

    public function testConstructorWithSingleHostConfiguration(): void
    {
        $config = [
            'hosts' => ['http://localhost:9200'],
            'index' => 'single_index'
        ];

        $configFile = $this->createConfigFile('single_host_config.php', $config);
        $esConfig = new ElasticsearchConfiguration($configFile);

        $this->assertCount(1, $esConfig->getHosts());
        $this->assertEquals('http://localhost:9200', $esConfig->getHosts()[0]);
        $this->assertEquals('single_index', $esConfig->getIndex());
    }

    public function testConstructorWithMultipleHostsConfiguration(): void
    {
        $config = [
            'hosts' => [
                'https://node1.elastic.example.com:9200',
                'https://node2.elastic.example.com:9200',
                'https://node3.elastic.example.com:9200'
            ],
            'index' => 'production_faq_index'
        ];

        $configFile = $this->createConfigFile('multi_host_config.php', $config);
        $esConfig = new ElasticsearchConfiguration($configFile);

        $this->assertCount(3, $esConfig->getHosts());
        $this->assertEquals($config['hosts'], $esConfig->getHosts());
        $this->assertEquals('production_faq_index', $esConfig->getIndex());
    }

    public function testGetHostsReturnsCorrectArray(): void
    {
        $hosts = [
            'cluster-node-1.elasticsearch.local:9200',
            'cluster-node-2.elasticsearch.local:9200'
        ];

        $config = [
            'hosts' => $hosts,
            'index' => 'test_index'
        ];

        $configFile = $this->createConfigFile('hosts_test.php', $config);
        $esConfig = new ElasticsearchConfiguration($configFile);

        $this->assertIsArray($esConfig->getHosts());
        $this->assertEquals($hosts, $esConfig->getHosts());
        $this->assertSame($hosts, $esConfig->getHosts()); // Test reference equality
    }

    public function testGetIndexReturnsCorrectString(): void
    {
        $indexName = 'my_custom_elasticsearch_index_2025';

        $config = [
            'hosts' => ['localhost:9200'],
            'index' => $indexName
        ];

        $configFile = $this->createConfigFile('index_test.php', $config);
        $esConfig = new ElasticsearchConfiguration($configFile);

        $this->assertIsString($esConfig->getIndex());
        $this->assertEquals($indexName, $esConfig->getIndex());
    }

    public function testConstructorWithComplexHostUrls(): void
    {
        $config = [
            'hosts' => [
                'https://user:pass@secure-elastic.com:9200',
                'http://192.168.1.100:9200',
                'https://elastic-cluster.internal:443/elasticsearch'
            ],
            'index' => 'complex_index_name_with_underscores'
        ];

        $configFile = $this->createConfigFile('complex_config.php', $config);
        $esConfig = new ElasticsearchConfiguration($configFile);

        $this->assertEquals($config['hosts'], $esConfig->getHosts());
        $this->assertEquals($config['index'], $esConfig->getIndex());
    }

    public function testReadonlyPropertiesCannotBeModified(): void
    {
        $config = [
            'hosts' => ['localhost:9200'],
            'index' => 'readonly_test'
        ];

        $configFile = $this->createConfigFile('readonly_config.php', $config);
        $esConfig = new ElasticsearchConfiguration($configFile);

        // Test dass alle Properties readonly sind
        $reflection = new \ReflectionClass(ElasticsearchConfiguration::class);

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
        $esConfig = new ElasticsearchConfiguration($configFile);

        $this->assertEmpty($esConfig->getHosts());
        $this->assertEquals('empty_hosts_index', $esConfig->getIndex());
    }

    public function testConstructorWithNumericIndexKeys(): void
    {
        // Test mit numerischen Keys im hosts Array
        $config = [
            'hosts' => [
                0 => 'first-host:9200',
                1 => 'second-host:9200',
                2 => 'third-host:9200'
            ],
            'index' => 'numeric_keys_index'
        ];

        $configFile = $this->createConfigFile('numeric_keys_config.php', $config);
        $esConfig = new ElasticsearchConfiguration($configFile);

        $this->assertCount(3, $esConfig->getHosts());
        $this->assertEquals('first-host:9200', $esConfig->getHosts()[0]);
        $this->assertEquals('second-host:9200', $esConfig->getHosts()[1]);
        $this->assertEquals('third-host:9200', $esConfig->getHosts()[2]);
    }

    public function testConstructorWithSpecialCharactersInIndex(): void
    {
        $config = [
            'hosts' => ['localhost:9200'],
            'index' => 'test-index_with.special-chars_2025'
        ];

        $configFile = $this->createConfigFile('special_chars_config.php', $config);
        $esConfig = new ElasticsearchConfiguration($configFile);

        $this->assertEquals('test-index_with.special-chars_2025', $esConfig->getIndex());
    }

    public function testMultipleInstancesWithDifferentConfigurations(): void
    {
        // Test dass verschiedene Instanzen unabhängig voneinander funktionieren
        $config1 = [
            'hosts' => ['host1:9200'],
            'index' => 'index1'
        ];

        $config2 = [
            'hosts' => ['host2:9200', 'host3:9200'],
            'index' => 'index2'
        ];

        $configFile1 = $this->createConfigFile('config1.php', $config1);
        $configFile2 = $this->createConfigFile('config2.php', $config2);

        $esConfig1 = new ElasticsearchConfiguration($configFile1);
        $esConfig2 = new ElasticsearchConfiguration($configFile2);

        $this->assertNotEquals($esConfig1->getHosts(), $esConfig2->getHosts());
        $this->assertNotEquals($esConfig1->getIndex(), $esConfig2->getIndex());

        $this->assertEquals(['host1:9200'], $esConfig1->getHosts());
        $this->assertEquals(['host2:9200', 'host3:9200'], $esConfig2->getHosts());
        $this->assertEquals('index1', $esConfig1->getIndex());
        $this->assertEquals('index2', $esConfig2->getIndex());
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(ElasticsearchConfiguration::class);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testConstructorWithDefaultValues(): void
    {
        // Test mit expliziter Standard-Konfiguration
        $configContent = '<?php' . PHP_EOL .
                        '$PMF_ES = [' . PHP_EOL .
                        '    "hosts" => [],' . PHP_EOL .
                        '    "index" => "",' . PHP_EOL .
                        '];';

        $configFile = $this->testConfigDir . '/default_config.php';
        file_put_contents($configFile, $configContent);

        $esConfig = new ElasticsearchConfiguration($configFile);

        $this->assertEquals([], $esConfig->getHosts());
        $this->assertEquals('', $esConfig->getIndex());
    }
}
