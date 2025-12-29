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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-07-10
 */

declare(strict_types=1);

namespace phpMyFAQ\Plugin;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class PluginManager
 */
class PluginManager
{
    /** @var PluginInterface[] */
    private array $plugins = [];

    private readonly EventDispatcher $eventDispatcher;

    /** @var PluginConfigurationInterface[] */
    private array $config = [];

    /** @var string[] */
    private array $loadedPlugins = [];

    /** @var array<string, array{plugin: PluginInterface, reason: string}> */
    private array $incompatiblePlugins = [];

    /** @var array<string, string[]> Plugin stylesheets: [pluginName => [CSS paths]] */
    private array $pluginStylesheets = [];

    /** @var array<string, string[]> Plugin scripts: [pluginName => [js paths]] */
    private array $pluginScripts = [];

    private readonly ContainerBuilder $containerBuilder;

    public function __construct()
    {
        $this->eventDispatcher = new EventDispatcher();
        $this->containerBuilder = new ContainerBuilder();
    }

    /**
     * Registers a plugin
     */
    public function registerPlugin(string $pluginClass): void
    {
        $plugin = new $pluginClass();
        if ($this->isCompatible($plugin)) {
            $this->plugins[$plugin->getName()] = $plugin;
            $this->containerBuilder->register($plugin->getName(), $pluginClass);
        } else {
            $this->incompatiblePlugins[$plugin->getName()] = [
                'plugin' => $plugin,
                'reason' => sprintf(
                    'Plugin version %s is not compatible with system version %s',
                    $plugin->getVersion(),
                    System::getPluginVersion(),
                ),
            ];
        }
    }

    /**
     * Loads and registers all plugins
     * @throws Exception
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
                $plugin->registerEvents($this->eventDispatcher);
                if (!empty($plugin->getConfig())) {
                    $this->loadPluginConfig($plugin->getName(), $plugin->getConfig());
                }

                // Register plugin translations
                $translationsPath = $plugin->getTranslationsPath();
                if ($translationsPath !== null) {
                    $pluginDir = $this->getPluginDirectory($plugin->getName());
                    $absoluteTranslationsPath = $pluginDir . '/' . $translationsPath;

                    if (is_dir($absoluteTranslationsPath)) {
                        Translation::getInstance()->registerPluginTranslations(
                            $plugin->getName(),
                            $absoluteTranslationsPath,
                        );
                    }
                }

                // Register plugin stylesheets
                $stylesheets = $plugin->getStylesheets();
                if (!empty($stylesheets)) {
                    $this->registerPluginStylesheets($plugin->getName(), $stylesheets);
                }

                // Register plugin scripts
                $scripts = $plugin->getScripts();
                if (!empty($scripts)) {
                    $this->registerPluginScripts($plugin->getName(), $scripts);
                }
            } else {
                $missingDeps = $this->getMissingDependencies($plugin);
                $this->incompatiblePlugins[$plugin->getName()] = [
                    'plugin' => $plugin,
                    'reason' => sprintf('Missing dependencies: %s', implode(', ', $missingDeps)),
                ];
                // Remove plugin from the plugins array since it's incompatible
                unset($this->plugins[$plugin->getName()]);
            }
        }
    }

    /**
     * Handles the triggered event
     *
     * @param mixed|null $data
     */
    public function triggerEvent(string $eventName, mixed $data = null): string
    {
        $pluginEvent = new PluginEvent($data);
        $this->eventDispatcher->dispatch($pluginEvent, $eventName);

        return $pluginEvent->getOutput();
    }

    /**
     * Loads the configuration for a plugin
     *
     * @param string $pluginName
     * @param PluginConfigurationInterface $config
     */
    public function loadPluginConfig(string $pluginName, PluginConfigurationInterface $config): void
    {
        $this->config[$pluginName] = $config;
    }

    /**
     * Returns the configuration for a plugin
     */
    public function getPluginConfig(string $pluginName): ?PluginConfigurationInterface
    {
        return $this->config[$pluginName] ?? null;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Returns the namespace from a file
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
     */
    private function isCompatible(PluginInterface $plugin): bool
    {
        return version_compare($plugin->getVersion(), System::getPluginVersion(), '>=');
    }

    /**
     * Checks if a plugin's dependencies are met
     */
    private function areDependenciesMet(PluginInterface $plugin): bool
    {
        if ($plugin->getDependencies() === []) {
            return true;
        }

        foreach ($plugin->getDependencies() as $dependency) {
            if (in_array($dependency, $this->loadedPlugins)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Gets the absolute directory path for a plugin
     */
    private function getPluginDirectory(string $pluginName): string
    {
        return PMF_ROOT_DIR . '/content/plugins/' . $pluginName;
    }

    /**
     * Registers stylesheets for a plugin
     *
     * @param string $pluginName
     * @param string[] $stylesheets Relative paths to CSS files
     */
    private function registerPluginStylesheets(string $pluginName, array $stylesheets): void
    {
        $pluginDir = $this->getPluginDirectory($pluginName);
        $validatedStylesheets = [];

        foreach ($stylesheets as $stylesheet) {
            // Security: Validate a path to prevent directory traversal
            $absolutePath = realpath($pluginDir . '/' . $stylesheet);

            if ($absolutePath && str_starts_with($absolutePath, $pluginDir) && file_exists($absolutePath)) {
                // Store relative path from web root for use in templates
                $webPath = 'content/plugins/' . $pluginName . '/' . $stylesheet;
                $validatedStylesheets[] = $webPath;
            }
        }

        if (!empty($validatedStylesheets)) {
            $this->pluginStylesheets[$pluginName] = $validatedStylesheets;
        }
    }

    /**
     * Returns all registered plugin stylesheets for template injection
     *
     * @return string[] Array of CSS paths ready for <link> tags
     */
    public function getAllPluginStylesheets(): array
    {
        $allStylesheets = [];

        foreach ($this->pluginStylesheets as $stylesheets) {
            $allStylesheets = array_merge($allStylesheets, $stylesheets);
        }

        return $allStylesheets;
    }

    /**
     * Returns stylesheets for a specific plugin
     *
     * @param string $pluginName
     * @return string[]
     */
    public function getPluginStylesheets(string $pluginName): array
    {
        return $this->pluginStylesheets[$pluginName] ?? [];
    }

    /**
     * Registers scripts for a plugin
     *
     * @param string $pluginName
     * @param string[] $scripts Relative paths to JavaScript files
     */
    private function registerPluginScripts(string $pluginName, array $scripts): void
    {
        $pluginDir = $this->getPluginDirectory($pluginName);
        $validatedScripts = [];

        foreach ($scripts as $script) {
            // Security: Validate path to prevent directory traversal
            $absolutePath = realpath($pluginDir . '/' . $script);

            if ($absolutePath && str_starts_with($absolutePath, $pluginDir) && file_exists($absolutePath)) {
                // Store relative path from web root for use in templates
                $webPath = 'content/plugins/' . $pluginName . '/' . $script;
                $validatedScripts[] = $webPath;
            }
        }

        if (!empty($validatedScripts)) {
            $this->pluginScripts[$pluginName] = $validatedScripts;
        }
    }

    /**
     * Returns all registered plugin scripts for template injection
     *
     * @return string[] Array of JavaScript paths ready for <script> tags
     */
    public function getAllPluginScripts(): array
    {
        $allScripts = [];

        foreach ($this->pluginScripts as $scripts) {
            $allScripts = array_merge($allScripts, $scripts);
        }

        return $allScripts;
    }

    /**
     * Returns scripts for a specific plugin
     *
     * @param string $pluginName
     * @return string[]
     */
    public function getPluginScripts(string $pluginName): array
    {
        return $this->pluginScripts[$pluginName] ?? [];
    }

    /**
     * Returns all incompatible plugins with their reasons
     *
     * @return array<string, array{plugin: PluginInterface, reason: string}>
     */
    public function getIncompatiblePlugins(): array
    {
        return $this->incompatiblePlugins;
    }

    /**
     * Gets the missing dependencies for a plugin
     *
     * @return string[]
     */
    private function getMissingDependencies(PluginInterface $plugin): array
    {
        $missingDeps = [];
        foreach ($plugin->getDependencies() as $dependency) {
            if (!in_array($dependency, $this->loadedPlugins)) {
                $missingDeps[] = $dependency;
            }
        }

        return $missingDeps;
    }
}
