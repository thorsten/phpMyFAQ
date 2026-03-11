<?php

declare(strict_types=1);

namespace {
    if (!class_exists('Redis')) {
        class Redis
        {
            public const OPT_READ_TIMEOUT = 3;

            public function hSet(string $key, string $field, string $value): int|false
            {
                return 1;
            }

            public function hGetAll(string $key): array|false
            {
                return [];
            }

            public function hSetNx(string $key, string $field, string $value): bool
            {
                return true;
            }

            public function hDel(string $key, string ...$fields): int|false
            {
                return 1;
            }

            public function hGet(string $key, string $field): string|false
            {
                return false;
            }

            public function info(?string $section = null): array|false
            {
                return [];
            }

            public function multi(): self|false
            {
                return $this;
            }

            public function del(string $key): int|false
            {
                return 1;
            }

            public function hMSet(string $key, array $pairs): bool
            {
                return true;
            }

            public function exec(): array|false
            {
                return [true, true];
            }

            public function connect(string $host, int $port = 6379, float $timeout = 0.0): bool
            {
                return true;
            }

            public function auth(string|array $credentials): bool
            {
                return true;
            }

            public function select(int $database): bool
            {
                return true;
            }

            public function setOption(int $option, mixed $value): bool
            {
                return true;
            }
        }
    }

    if (!class_exists('RedisException')) {
        class RedisException extends \RuntimeException {}
    }
}

namespace phpMyFAQ\Configuration\Storage {
    function extension_loaded(string $extension): bool
    {
        return $extension === 'redis'
            ? \phpMyFAQ\Configuration\RedisConfigurationStoreTestShim::$redisExtensionLoaded
            : \extension_loaded($extension);
    }

    function phpversion(?string $extension = null): string|false
    {
        if ($extension === 'redis') {
            return \phpMyFAQ\Configuration\RedisConfigurationStoreTestShim::$redisExtensionVersion;
        }

        return \phpversion($extension);
    }
}

namespace phpMyFAQ\Configuration {
    use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings;
    use phpMyFAQ\Configuration\Storage\RedisConfigurationStore;
    use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
    use PHPUnit\Framework\Attributes\CoversClass;
    use PHPUnit\Framework\Attributes\UsesClass;
    use PHPUnit\Framework\TestCase;
    use Redis;
    use RedisException;
    use ReflectionProperty;
    use RuntimeException;

    #[AllowMockObjectsWithoutExpectations]
    #[CoversClass(RedisConfigurationStore::class)]
    #[UsesClass(ConfigurationStorageSettings::class)]
    class RedisConfigurationStoreTest extends TestCase
    {
        private ConfigurationStorageSettings $settings;

        protected function setUp(): void
        {
            parent::setUp();

            $this->settings = new ConfigurationStorageSettings(true, 'tcp://redis:6379?database=1', 'pmf:config:', 1.0);
            RedisConfigurationStoreTestShim::$redisExtensionLoaded = true;
            RedisConfigurationStoreTestShim::$redisExtensionVersion = '6.1.0';
        }

        public function testUpdateConfigValueReturnsTrueWhenRedisHashSetSucceeds(): void
        {
            $redis = $this->createMock(Redis::class);
            $redis
                ->expects($this->once())
                ->method('hSet')
                ->with('pmf:config:items', 'main.language', 'en')
                ->willReturn(1);

            $store = $this->createStoreWithRedisClient($redis);

            $this->assertTrue($store->updateConfigValue('main.language', 'en'));
        }

        public function testUpdateConfigValueWrapsRedisExceptions(): void
        {
            $redis = $this->createMock(Redis::class);
            $redis->method('hSet')->willThrowException(new RedisException('write failed'));

            $store = $this->createStoreWithRedisClient($redis);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Redis updateConfigValue failed: write failed');
            $store->updateConfigValue('main.language', 'en');
        }

        public function testFetchAllReturnsMappedObjects(): void
        {
            $redis = $this->createMock(Redis::class);
            $redis
                ->expects($this->once())
                ->method('hGetAll')
                ->with('pmf:config:items')
                ->willReturn([
                    'main.language' => 'en',
                    'main.titleFAQ' => 'Test FAQ',
                ]);

            $store = $this->createStoreWithRedisClient($redis);
            $rows = $store->fetchAll();

            $this->assertCount(2, $rows);
            $this->assertSame('main.language', $rows[0]->config_name);
            $this->assertSame('en', $rows[0]->config_value);
            $this->assertSame('main.titleFAQ', $rows[1]->config_name);
            $this->assertSame('Test FAQ', $rows[1]->config_value);
        }

        public function testFetchAllReturnsEmptyArrayForEmptyHash(): void
        {
            $redis = $this->createMock(Redis::class);
            $redis->method('hGetAll')->willReturn([]);

            $store = $this->createStoreWithRedisClient($redis);

            $this->assertSame([], $store->fetchAll());
        }

        public function testInsertDeleteAndRenameKeyHandleResultFlags(): void
        {
            $redis = $this->createMock(Redis::class);
            $redis
                ->expects($this->once())
                ->method('hSetNx')
                ->with('pmf:config:items', 'new.key', 'value')
                ->willReturn(true);
            $redis->expects($this->exactly(3))->method('hDel')->willReturnOnConsecutiveCalls(0, 1, 1);
            $redis->expects($this->exactly(2))->method('hGet')->willReturnOnConsecutiveCalls(false, 'stored-value');
            $redis
                ->expects($this->once())
                ->method('hSet')
                ->with('pmf:config:items', 'new.name', 'stored-value')
                ->willReturn(1);

            $store = $this->createStoreWithRedisClient($redis);

            $this->assertTrue($store->insert('new.key', 'value'));
            $this->assertFalse($store->delete('missing.key'));
            $this->assertTrue($store->delete('existing.key'));
            $this->assertFalse($store->renameKey('old.name', 'new.name'));
            $this->assertTrue($store->renameKey('old.name', 'new.name'));
        }

        public function testRenameKeyRollsBackNewKeyWhenDeletingOldKeyFails(): void
        {
            $redis = $this->createMock(Redis::class);
            $redis
                ->expects($this->once())
                ->method('hGet')
                ->with('pmf:config:items', 'old.name')
                ->willReturn('stored-value');
            $redis
                ->expects($this->once())
                ->method('hSet')
                ->with('pmf:config:items', 'new.name', 'stored-value')
                ->willReturn(1);
            $redis->expects($this->exactly(2))->method('hDel')->willReturnOnConsecutiveCalls(0, 1);

            $store = $this->createStoreWithRedisClient($redis);

            $this->assertFalse($store->renameKey('old.name', 'new.name'));
        }

        public function testGetInstalledRedisVersionFormatsServerAndExtensionVersions(): void
        {
            $redis = $this->createMock(Redis::class);
            $redis->expects($this->once())->method('info')->with('server')->willReturn(['redis_version' => '7.2.5']);

            $store = $this->createStoreWithRedisClient($redis);
            $version = $store->getInstalledRedisVersion();

            $this->assertSame('7.2.5 (ext-redis 6.1.0)', $version);
        }

        public function testWarmFromRowsBuildsHashAndReturnsTrueOnSuccessfulExec(): void
        {
            $redis = $this->createMock(Redis::class);
            $rows = [
                (object) ['config_name' => 'main.language', 'config_value' => 'en'],
                (object) ['config_name' => 'main.titleFAQ', 'config_value' => 'Test FAQ'],
                (object) ['config_name' => null, 'config_value' => 'ignored'],
            ];

            $redis->expects($this->once())->method('multi');
            $redis->expects($this->once())->method('del')->with('pmf:config:items');
            $redis
                ->expects($this->once())
                ->method('hMSet')
                ->with('pmf:config:items', [
                    'main.language' => 'en',
                    'main.titleFAQ' => 'Test FAQ',
                ]);
            $redis->expects($this->once())->method('exec')->willReturn([true, true]);

            $store = $this->createStoreWithRedisClient($redis);

            $this->assertTrue($store->warmFromRows($rows));
        }

        public function testWarmFromRowsHandlesEmptyAndUnusableRowsWithoutRedisWrites(): void
        {
            $redis = $this->createMock(Redis::class);
            $redis->expects($this->never())->method('multi');

            $store = $this->createStoreWithRedisClient($redis);

            $this->assertTrue($store->warmFromRows([]));
            $this->assertTrue($store->warmFromRows([(object) ['config_value' => 'missing-name']]));
        }

        public function testWarmFromRowsReturnsFalseWhenExecIndicatesFailure(): void
        {
            $redis = $this->createMock(Redis::class);
            $redis->method('multi');
            $redis->method('del');
            $redis->method('hMSet');
            $redis->method('exec')->willReturn([true, false]);

            $store = $this->createStoreWithRedisClient($redis);

            $this->assertFalse($store->warmFromRows([(object) [
                'config_name' => 'main.language',
                'config_value' => 'en',
            ]]));
        }

        public function testFetchAllThrowsRuntimeExceptionForInvalidDsn(): void
        {
            $store = new RedisConfigurationStore(
                new ConfigurationStorageSettings(true, 'not-a-valid-dsn', 'pmf:config:', 1.0),
            );

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Invalid Redis DSN for configuration storage.');
            $store->fetchAll();
        }

        public function testFetchAllThrowsRuntimeExceptionForUnsupportedScheme(): void
        {
            $store = new RedisConfigurationStore(
                new ConfigurationStorageSettings(true, 'http://localhost:6379', 'pmf:config:', 1.0),
            );

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Unsupported Redis DSN scheme "http" for configuration storage.');
            $store->fetchAll();
        }

        public function testFetchAllThrowsRuntimeExceptionWhenRedisExtensionIsUnavailable(): void
        {
            RedisConfigurationStoreTestShim::$redisExtensionLoaded = false;

            $store = new RedisConfigurationStore($this->settings);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Redis configuration storage requires the PHP redis extension');
            $store->fetchAll();
        }

        private function createStoreWithRedisClient(Redis $redis): RedisConfigurationStore
        {
            $store = new RedisConfigurationStore($this->settings);

            $reflectionProperty = new ReflectionProperty($store, 'redisClient');
            $reflectionProperty->setValue($store, $redis);

            return $store;
        }
    }

    final class RedisConfigurationStoreTestShim
    {
        public static bool $redisExtensionLoaded = true;

        public static string|false $redisExtensionVersion = '6.1.0';
    }
}
