<?php

/**
 * The main Plugin interface
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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface PluginInterface
{
    /**
     * Returns the name of the plugin
     */
    public function getName(): string;

    /**
     * Returns the version of the plugin
     */
    public function getVersion(): string;

    /**
     * Returns the description of the plugin
     */
    public function getDescription(): string;

    /**
     * Returns the author of the plugin
     */
    public function getAuthor(): string;

    /**
     * Returns the dependencies of the plugin
     */
    public function getDependencies(): array;

    /**
     * Returns the configuration of the plugin (optional)
     */
    public function getConfig(): ?PluginConfigurationInterface;

    /**
     * Register the events
     */
    public function registerEvents(EventDispatcherInterface $eventDispatcher): void;

    /**
     * Returns an array of CSS file paths (relative to plugin directory)
     * Plugins should provide pre-compiled CSS files
     *
     * @return string[] Array of CSS file paths, e.g., ['assets/style.css']
     */
    public function getStylesheets(): array;

    /**
     * Returns the path to the translations directory (relative to plugin directory)
     * Returns null if the plugin doesn't provide translations
     *
     * @return string|null Path to the translations directory, e.g., 'translations'
     */
    public function getTranslationsPath(): ?string;

    /**
     * Returns an array of JavaScript file paths (relative to plugin directory)
     * Plugins should provide pre-compiled JavaScript files (not TypeScript source)
     *
     * @return string[] Array of JavaScript file paths, e.g., ['assets/script.js']
     */
    public function getScripts(): array;
}
