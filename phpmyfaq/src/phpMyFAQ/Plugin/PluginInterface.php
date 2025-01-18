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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-07-10
 */

namespace phpMyFAQ\Plugin;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface PluginInterface
{
    /**
     * Returns the name of the plugin
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the version of the plugin
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Returns the description of the plugin
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Returns the author of the plugin
     *
     * @return string
     */
    public function getAuthor(): string;

    /**
     * Returns the dependencies of the plugin
     *
     * @return array
     */
    public function getDependencies(): array;

    /**
     * Returns the configuration of the plugin
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * Register the events
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function registerEvents(EventDispatcherInterface $dispatcher): void;
}
