<?php

/**
 * Compiled DI container cache manager
 *
 * Compiles the service container once, dumps it to a PHP class, and serves the dumped
 * class on subsequent requests so services.php is not re-parsed per request.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-07-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Container;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Throwable;

final class ContainerCacheManager
{
    private readonly string $cacheDir;

    /** @var callable(string): void */
    private $logError;

    /**
     * @param callable(string): void|null $logError Defaults to error_log(); the container
     *                                              is not built yet, so no PSR logger exists.
     */
    public function __construct(string $cacheDir, ?callable $logError = null)
    {
        $this->cacheDir = rtrim(string: $cacheDir, characters: '/');
        $this->logError = $logError ?? error_log(...);

        if (!is_dir($this->cacheDir)) {
            mkdir(directory: $this->cacheDir, permissions: 0o755, recursive: true);
        }
    }

    /**
     * Returns the compiled container, dumping it to the cache on first use. Every service
     * and alias is marked public before compiling because phpMyFAQ resolves services from
     * the container at runtime. If compiling or dumping fails, the container falls back to
     * a fresh, uncompiled builder, so the application keeps working without the cache.
     *
     * @param callable(): ContainerBuilder $containerBuilderFactory
     */
    public function getContainer(callable $containerBuilderFactory): ContainerInterface
    {
        $containerClass = $this->containerClass();
        $cacheFile = $this->cacheDir . '/' . $containerClass . '.php';

        if (!class_exists($containerClass, autoload: false) && is_file($cacheFile)) {
            require_once $cacheFile;
        }

        if (class_exists($containerClass, autoload: false)) {
            /* @mago-expect analysis:unknown-class-instantiation - the dumped container class only exists at runtime */
            $container = new $containerClass();
            if ($container instanceof ContainerInterface) {
                return $container;
            }
        }

        try {
            $containerBuilder = $containerBuilderFactory();

            foreach ($containerBuilder->getDefinitions() as $definition) {
                $definition->setPublic(true);
            }

            foreach ($containerBuilder->getAliases() as $alias) {
                $alias->setPublic(true);
            }

            $containerBuilder->compile();

            $dump = new PhpDumper($containerBuilder)->dump(['class' => $containerClass]);
            if (is_string($dump)) {
                $this->writeCache($cacheFile, $dump);
            }

            return $containerBuilder;
        } catch (Throwable $throwable) {
            ($this->logError)(sprintf(
                'phpMyFAQ: cannot compile the DI container (%s), falling back to the uncompiled container: %s',
                $throwable::class,
                $throwable->getMessage(),
            ));

            return $containerBuilderFactory();
        }
    }

    /**
     * The class name embeds a cache-directory hash, so containers dumped into
     * different directories never collide inside one PHP process.
     */
    private function containerClass(): string
    {
        return 'PMFCompiledContainer_' . substr(md5($this->cacheDir), offset: 0, length: 12);
    }

    private function writeCache(string $cacheFile, string $dump): void
    {
        $temporaryFile = tempnam($this->cacheDir, prefix: 'container');
        if ($temporaryFile === false) {
            return;
        }

        file_put_contents($temporaryFile, $dump);
        rename($temporaryFile, $cacheFile);
    }
}
