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
 * @copyright 2024-2026 phpMyFAQ Team
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

    public function __construct(
        private readonly \phpMyFAQ\Configuration $configuration
    ) {
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
        if (!empty($this->loadedPlugins)) {
            return;
        }

        $pluginDir = PMF_ROOT_DIR . '/content/plugins/';
        $pluginFiles = glob($pluginDir . '*/*Plugin.php');

        foreach ($pluginFiles as $pluginFile) {
            require_once $pluginFile;
            $className = basename($pluginFile, '.php');
            $namespace = $this->getNamespaceFromFile($pluginFile);
            $fullClassName = $namespace . '\\' . $className;
            $this->registerPlugin($fullClassName);
        }

        // Fetch plugin states and config from database
        $dbPlugins = $this->getPluginsFromDatabase();
        
        foreach ($this->plugins as $plugin) {
            $pluginName = $plugin->getName();
            $isActive = false;

            // Apply configuration and check status from DB
            if (isset($dbPlugins[$pluginName])) {
                $isActive = (bool)$dbPlugins[$pluginName]['active'];
                if (!empty($dbPlugins[$pluginName]['config']) && $plugin->getConfig()) {
                    $configArray = json_decode($dbPlugins[$pluginName]['config'], true);
                    if (is_array($configArray)) {
                        $configObject = $plugin->getConfig();
                        foreach ($configArray as $key => $value) {
                            if (property_exists($configObject, $key)) {
                                try {
                                    $rp = new \ReflectionProperty($configObject, $key);
                                    if ($type = $rp->getType()) {
                                        $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;
                                        switch ($typeName) {
                                            case 'int':
                                                $value = (int)$value;
                                                break;
                                            case 'bool':
                                                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                                                break;
                                            case 'float':
                                                $value = (float)$value;
                                                break;
                                        }
                                    }
                                } catch (\ReflectionException $e) {
                                    // Fallback to direct assignment if reflection fails
                                }
                                $configObject->$key = $value;
                            }
                        }
                    }
                }
            } else {

                // I will default to inactive (false) for new plugins found on disk but not in DB.
                $isActive = false; 
            }

            // Allow checking if it IS active.
            // But we only REGISTER events and LOAD scripts if active.

            if ($isActive && $this->areDependenciesMet($plugin)) {
                $this->loadedPlugins[] = $plugin->getName();

                $plugin->registerEvents($this->eventDispatcher);

                if (!empty($plugin->getConfig())) {
                    $this->loadPluginConfig($plugin->getName(), $plugin->getConfig());
                    // Apply DB config overrides here if possible
                }

                // Register plugin translations
                $translationsPath = null;
                if (method_exists($plugin, 'getTranslationsPath')) {
                    $translationsPath = $plugin->getTranslationsPath();
                }

                if ($translationsPath !== null) {
                    $pluginDir = $this->getPluginDirectory($plugin->getName());
                    $absoluteTranslationsPath = $pluginDir . '/' . $translationsPath;

                    if (is_dir($absoluteTranslationsPath)) {
                        $translation = Translation::getInstance();
                        if (method_exists($translation, 'registerPluginTranslations')) {
                            $translation->registerPluginTranslations(
                                $plugin->getName(),
                                $absoluteTranslationsPath,
                            );
                        }
                    }
                }


                // Register plugin stylesheets
                if (method_exists($plugin, 'getStylesheets')) {
                    $this->registerPluginStylesheets($plugin->getName(), $plugin->getStylesheets());
                }

                // Register plugin scripts
                if (method_exists($plugin, 'getScripts')) {
                    $this->registerPluginScripts($plugin->getName(), $plugin->getScripts());
                }
            } elseif (!$this->areDependenciesMet($plugin)) {

                $missingDeps = $this->getMissingDependencies($plugin);
                $this->incompatiblePlugins[$plugin->getName()] = [
                    'plugin' => $plugin,
                    'reason' => sprintf('Missing dependencies: %s', implode(', ', $missingDeps)),
                ];
                // Remove plugin from the plugins array since it's incompatible (actually, maybe keep it but mark inactive?)
                // Original code removed it. I'll stick to original behavior for incompatible ones.
                unset($this->plugins[$plugin->getName()]);
            }
        }
    }

    /**
     * Activates a plugin
     */
    public function activatePlugin(string $pluginName): void
    {
        $this->updatePluginStatus($pluginName, true);
    }

    /**
     * Deactivates a plugin
     */
    public function deactivatePlugin(string $pluginName): void
    {
        $this->updatePluginStatus($pluginName, false);
    }

    /**
     * Checks if a plugin is active
     */
    public function isPluginActive(string $pluginName): bool
    {
        return in_array($pluginName, $this->loadedPlugins, true);
    }

    /**
     * Saves plugin configuration
     */
    public function savePluginConfig(string $pluginName, array $configData): void
    {
        $jsonConfig = json_encode($configData);
        $db = $this->configuration->getDb();
        $table = \phpMyFAQ\Database::getTablePrefix() . 'faqplugins';

        // Check if exists
        $select = sprintf('SELECT name FROM %s WHERE name = ?', $table);
        $stmt = $db->prepare($select);
        $db->execute($stmt, [$pluginName]);
        $result = $db->fetchAll($stmt);

        if (count($result) > 0) {
            $update = sprintf('UPDATE %s SET config = ? WHERE name = ?', $table);
            $stmt = $db->prepare($update);
            $db->execute($stmt, [$jsonConfig, $pluginName]);
        } else {
            $insert = sprintf('INSERT INTO %s (name, active, config) VALUES (?, 0, ?)', $table);
            $stmt = $db->prepare($insert);
            $db->execute($stmt, [$pluginName, $jsonConfig]);
        }
    }

    /**
     * Updates plugin status in DB
     */
    private function updatePluginStatus(string $pluginName, bool $active): void
    {
        $db = $this->configuration->getDb();
        $table = \phpMyFAQ\Database::getTablePrefix() . 'faqplugins';
        $activeInt = $active ? 1 : 0;

        // Check if exists
        $select = sprintf('SELECT name FROM %s WHERE name = ?', $table);
        $stmt = $db->prepare($select);
        $db->execute($stmt, [$pluginName]);
        $result = $db->fetchAll($stmt);

        if (count($result) > 0) {
            $query = sprintf('UPDATE %s SET active = ? WHERE name = ?', $table);
            $params = [$activeInt, $pluginName];
        } else {
            $query = sprintf('INSERT INTO %s (name, active) VALUES (?, ?)', $table);
            $params = [$pluginName, $activeInt];
        }
        $stmt = $db->prepare($query);
        $db->execute($stmt, $params);
    }

    /**
     * Fetch plugins from DB
     */
    private function getPluginsFromDatabase(): array
    {
        $db = $this->configuration->getDb();
        $table = \phpMyFAQ\Database::getTablePrefix() . 'faqplugins';
        
        // Ensure table exists to avoid crashes during update/install if not yet run
        try {
            $result = $db->query("SELECT name, active, config FROM $table");
        } catch (\Exception $e) {
            // Table might not exist yet
            return [];
        }

        $plugins = [];
        while ($row = $db->fetchObject($result)) {
            $plugins[$row->name] = [
                'active' => $row->active,
                'config' => $row->config
            ];
        }
        return $plugins;
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
     */
    public function loadPluginConfig(string $pluginName, PluginConfigurationInterface $pluginConfiguration): void
    {
        $this->config[$pluginName] = $pluginConfiguration;
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
     * @param string[] $stylesheets Relative paths to CSS files
     */
    private function registerPluginStylesheets(string $pluginName, array $stylesheets): void
    {
        $pluginDir = $this->getPluginDirectory($pluginName);
        $validatedStylesheets = [];

        // Normalize the path for comparison (slashes and case)
        $realPluginDir = str_replace('\\', '/', realpath($pluginDir) ?: $pluginDir);
        $realPluginDir = rtrim($realPluginDir, '/') . '/';

        foreach ($stylesheets as $stylesheet) {
            $fullPath = $pluginDir . '/' . $stylesheet;
            $absolutePath = str_replace('\\', '/', realpath($fullPath) ?: $fullPath);

            // Security check: Must be inside the plugin directory (case-insensitive for Windows)
            if (str_starts_with(strtolower($absolutePath), strtolower($realPluginDir)) && file_exists($fullPath)) {
                $validatedStylesheets[] = 'content/plugins/' . $pluginName . '/' . $stylesheet;
            }
        }

        if ($validatedStylesheets !== []) {
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

        foreach ($this->pluginStylesheets as $pluginStylesheet) {
            $allStylesheets = array_merge($allStylesheets, $pluginStylesheet);
        }

        return $allStylesheets;
    }

    /**
     * Returns stylesheets for a specific plugin
     *
     * @return string[]
     */
    public function getPluginStylesheets(string $pluginName): array
    {
        return $this->pluginStylesheets[$pluginName] ?? [];
    }

    private function registerPluginScripts(string $pluginName, array $scripts): void
    {
        $pluginDir = $this->getPluginDirectory($pluginName);
        $validatedScripts = [];

        // Normalize the path for comparison (slashes and case)
        $realPluginDir = str_replace('\\', '/', realpath($pluginDir) ?: $pluginDir);
        $realPluginDir = rtrim($realPluginDir, '/') . '/';

        foreach ($scripts as $script) {
            $fullPath = $pluginDir . '/' . $script;
            $absolutePath = str_replace('\\', '/', realpath($fullPath) ?: $fullPath);

            // Security check: Must be inside the plugin directory (case-insensitive for Windows)
            if (str_starts_with(strtolower($absolutePath), strtolower($realPluginDir)) && file_exists($fullPath)) {
                $validatedScripts[] = 'content/plugins/' . $pluginName . '/' . $script;
            }
        }

        if ($validatedScripts !== []) {
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

        foreach ($this->pluginScripts as $pluginScript) {
            $allScripts = array_merge($allScripts, $pluginScript);
        }

        return $allScripts;
    }

    /**
     * Returns scripts for a specific plugin
     *
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
