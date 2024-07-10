<?php

/**
 * The main PluginManager class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-07-10
 */

namespace phpMyFAQ\Plugin;

use phpMyFAQ\System;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class PluginManager
 */
class PluginManager
{
    /** @var PluginInterface[] */
    private array $plugins = [];

    private EventDispatcher $dispatcher;

    private array $config = [];

    /** @var string[] */
    private array $loadedPlugins = [];

    private ContainerBuilder $container;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
        $this->container = new ContainerBuilder();
    }

    /**
     * Registers a plugin
     *
     * @param string $pluginClass
     * @throws PluginException
     * @return void
     */
    public function registerPlugin(string $pluginClass): void
    {
        $plugin = new $pluginClass($this);
        if ($this->isCompatible($plugin)) {
            $this->plugins[$plugin->getName()] = $plugin;
            $this->container
                ->register($plugin->getName(), $pluginClass)
                ->setArguments([$this]);
        } else {
            throw new PluginException(sprintf('Plugin %s is not compatible.', $plugin->getName()));
        }
    }

    /**
     * Loads and registers all plugins
     * @throws PluginException
     */
    public function loadPlugins(): void
    {
        $pluginDir = PMF_ROOT_DIR . '/content/plugins/';
        $pluginFiles = glob($pluginDir . '*/*Plugin.php');

        foreach ($pluginFiles as $pluginFile) {
            require_once $pluginFile;
            $className = basename($pluginFile, '.php');
            $namespace = $this->getNamespaceFromFile($pluginFile);
            $fullClassName = $namespace . '\\' . $className;
            $this->registerPlugin($fullClassName);
        }

        foreach ($this->plugins as $plugin) {
            if ($this->areDependenciesMet($plugin)) {
                $this->loadedPlugins[] = $plugin->getName();
                $plugin->registerEvents($this->dispatcher);
                if (!empty($plugin->getConfig())) {
                    $this->loadPluginConfig($plugin->getName(), $plugin->getConfig());
                }
            } else {
                throw new PluginException(sprintf('Dependencies for plugin %s are not met.', $plugin->getName()));
            }
        }
    }

    /**
     * Handles the triggered event
     * @param string     $eventName
     * @param mixed|null $data
     * @return string
     */
    public function triggerEvent(string $eventName, mixed $data = null): string
    {
        $event = new PluginEvent($data);
        $this->dispatcher->dispatch($event, $eventName);

        return $event->getOutput();
    }

    /**
     * Loads the configuration for a plugin
     * @param string   $pluginName
     * @param string[] $config
     */
    public function loadPluginConfig(string $pluginName, array $config): void
    {
        $this->config[$pluginName] = $config;
    }

    /**
     * Returns the configuration for a plugin
     * @param string $pluginName
     * @return array
     */
    public function getPluginConfig(string $pluginName): array
    {
        return $this->config[$pluginName] ?? [];
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Returns the namespace from a file
     * @param string $file
     * @return string|null
     */
    private function getNamespaceFromFile(string $file): ?string
    {
        $src = file_get_contents($file);
        if (preg_match('/^namespace\s+(.+?);/m', $src, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Checks if a plugin is compatible with the current version
     * @param PluginInterface $plugin
     * @return bool
     */
    private function isCompatible(PluginInterface $plugin): bool
    {
        return version_compare($plugin->getVersion(), System::getPluginVersion(), '>=');
    }

    /**
     * Checks if a plugin's dependencies are met
     * @param PluginInterface $plugin
     * @return bool
     */
    private function areDependenciesMet(PluginInterface $plugin): bool
    {
        if (empty($plugin->getDependencies())) {
            return true;
        }

        foreach ($plugin->getDependencies() as $dependency) {
            if (!in_array($dependency, $this->loadedPlugins)) {
                return false;
            }
        }
        return true;
    }
}
