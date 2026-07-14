<?php

namespace phpMyFAQ\Plugin;

use PHPUnit\Framework\TestCase;

class PluginDiscoveryTest extends TestCase
{
    private string $pluginDir;

    private string $cacheFile;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir() . '/pmf-plugin-discovery-' . bin2hex(random_bytes(8));
        $this->pluginDir = $base . '/plugins';
        $this->cacheFile = $base . '/cache/plugins.php';

        mkdir($this->pluginDir . '/Demo', 0o777, true);
        file_put_contents(
            $this->pluginDir . '/Demo/DemoPlugin.php',
            "<?php\n\nnamespace Demo;\n\nclass DemoPlugin\n{\n}\n",
        );
    }

    protected function tearDown(): void
    {
        $base = dirname($this->pluginDir);
        exec('rm -rf ' . escapeshellarg($base));
    }

    public function testDiscoversPluginClassesWithoutCache(): void
    {
        $discovery = new PluginDiscovery($this->pluginDir);

        $classMap = $discovery->getClassMap();

        $this->assertSame(
            [$this->pluginDir . '/Demo/DemoPlugin.php' => 'Demo\\DemoPlugin'],
            $classMap,
        );
    }

    public function testWritesManifestOnFirstDiscovery(): void
    {
        $discovery = new PluginDiscovery($this->pluginDir, $this->cacheFile);

        $discovery->getClassMap();

        $this->assertFileExists($this->cacheFile);
    }

    public function testReadsFromManifestWhileSignatureIsUnchanged(): void
    {
        $discovery = new PluginDiscovery($this->pluginDir, $this->cacheFile);
        $discovery->getClassMap();

        // Replace the cached class map with a sentinel; a cache hit must
        // return the sentinel instead of re-scanning the filesystem.
        $manifest = include $this->cacheFile;
        $manifest['classMap'] = ['sentinel.php' => 'Sentinel\\SentinelPlugin'];
        file_put_contents($this->cacheFile, "<?php\n\nreturn " . var_export($manifest, true) . ";\n");

        $classMap = (new PluginDiscovery($this->pluginDir, $this->cacheFile))->getClassMap();

        $this->assertSame(['sentinel.php' => 'Sentinel\\SentinelPlugin'], $classMap);
    }

    public function testRebuildsManifestWhenPluginIsAdded(): void
    {
        $discovery = new PluginDiscovery($this->pluginDir, $this->cacheFile);
        $discovery->getClassMap();

        mkdir($this->pluginDir . '/Extra', 0o777, true);
        file_put_contents(
            $this->pluginDir . '/Extra/ExtraPlugin.php',
            "<?php\n\nnamespace Extra;\n\nclass ExtraPlugin\n{\n}\n",
        );
        // mtime granularity is one second — force a visibly newer directory mtime
        touch($this->pluginDir, time() + 10);

        $classMap = (new PluginDiscovery($this->pluginDir, $this->cacheFile))->getClassMap();

        $this->assertArrayHasKey($this->pluginDir . '/Extra/ExtraPlugin.php', $classMap);
        $this->assertSame('Extra\\ExtraPlugin', $classMap[$this->pluginDir . '/Extra/ExtraPlugin.php']);
    }

    public function testRebuildsManifestWhenPluginIsRemoved(): void
    {
        mkdir($this->pluginDir . '/Extra', 0o777, true);
        file_put_contents(
            $this->pluginDir . '/Extra/ExtraPlugin.php',
            "<?php\n\nnamespace Extra;\n\nclass ExtraPlugin\n{\n}\n",
        );

        $discovery = new PluginDiscovery($this->pluginDir, $this->cacheFile);
        $this->assertCount(2, $discovery->getClassMap());

        unlink($this->pluginDir . '/Extra/ExtraPlugin.php');
        rmdir($this->pluginDir . '/Extra');
        touch($this->pluginDir, time() + 10);

        $classMap = (new PluginDiscovery($this->pluginDir, $this->cacheFile))->getClassMap();

        $this->assertArrayNotHasKey($this->pluginDir . '/Extra/ExtraPlugin.php', $classMap);
        $this->assertCount(1, $classMap);
    }

    public function testMapsPluginWithoutNamespaceToGlobalClassName(): void
    {
        mkdir($this->pluginDir . '/Legacy', 0o777, true);
        file_put_contents(
            $this->pluginDir . '/Legacy/LegacyPlugin.php',
            "<?php\n\nclass LegacyPlugin\n{\n}\n",
        );

        $classMap = (new PluginDiscovery($this->pluginDir))->getClassMap();

        $this->assertSame('\\LegacyPlugin', $classMap[$this->pluginDir . '/Legacy/LegacyPlugin.php']);
    }

    public function testReturnsEmptyMapForMissingDirectory(): void
    {
        $discovery = new PluginDiscovery($this->pluginDir . '/does-not-exist');

        $this->assertSame([], $discovery->getClassMap());
    }
}
