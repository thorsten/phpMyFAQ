<?php

/**
 * Namespace-level overrides of extension_loaded() and phpversion() for the
 * phpMyFAQ\Configuration\Storage namespace, so RedisConfigurationStoreTest can
 * simulate a missing or present redis extension.
 *
 * PHP resolves an unqualified function call inside a namespace once per call
 * site and caches the result. If RedisConfigurationStore ran before these
 * functions existed, the global functions would stay bound for the rest of the
 * process and the test could not override them any more. tests/bootstrap.php
 * therefore loads this file before any test runs. Without an explicit override
 * the functions behave exactly like the global ones.
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration\Storage {
    function extension_loaded(string $extension): bool
    {
        if ($extension === 'redis' && \phpMyFAQ\Configuration\RedisConfigurationStoreTestShim::$redisExtensionLoaded !== null) {
            return \phpMyFAQ\Configuration\RedisConfigurationStoreTestShim::$redisExtensionLoaded;
        }

        return \extension_loaded($extension);
    }

    function phpversion(?string $extension = null): string|false
    {
        if ($extension === 'redis' && \phpMyFAQ\Configuration\RedisConfigurationStoreTestShim::$redisExtensionVersion !== null) {
            return \phpMyFAQ\Configuration\RedisConfigurationStoreTestShim::$redisExtensionVersion;
        }

        return \phpversion($extension);
    }
}

namespace phpMyFAQ\Configuration {
    final class RedisConfigurationStoreTestShim
    {
        /** null: behave like the real extension_loaded('redis') */
        public static ?bool $redisExtensionLoaded = null;

        /** null: behave like the real phpversion('redis') */
        public static string|false|null $redisExtensionVersion = null;
    }
}
