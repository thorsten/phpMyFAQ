<?php

/**
 * Enhanced Example Plugin demonstrating CSS and translation features
 *
 * Add
 *
 *     {{ phpMyFAQPlugin('enhanced.greeting', 'John Doe') | raw }}
 *
 * into the Twig template.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-12-28
 */

declare(strict_types=1);

namespace phpMyFAQ\Plugin\EnhancedExample;

use phpMyFAQ\Plugin\PluginEvent;
use phpMyFAQ\Plugin\PluginInterface;
use phpMyFAQ\Plugin\PluginConfigurationInterface;
use phpMyFAQ\Translation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EnhancedExamplePlugin implements PluginInterface
{
    public function getName(): string
    {
        return 'EnhancedExample';
    }

    public function getVersion(): string
    {
        return '0.2.0';
    }

    public function getDescription(): string
    {
        return 'Example plugin demonstrating CSS and translation support';
    }

    public function getAuthor(): string
    {
        return 'phpMyFAQ Team';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getConfig(): ?PluginConfigurationInterface
    {
        return null;
    }

    public function getStylesheets(): array
    {
        return [
            'assets/style.css',      // Frontend styles
            'assets/admin-style.css' // Admin styles
        ];
    }

    public function getTranslationsPath(): ?string
    {
        return 'translations';
    }

    public function getScripts(): array
    {
        return [
            'assets/script.js',       // Frontend script
            'assets/admin-script.js'  // Admin script
        ];
    }

    public function registerEvents(EventDispatcherInterface $eventDispatcher): void
    {
        $eventDispatcher->addListener('enhanced.greeting', [$this, 'onGreeting']);
    }

    public function onGreeting(PluginEvent $event): void
    {
        $name = $event->getData();

        // Use plugin-namespaced translations
        $greeting = Translation::get('plugin.EnhancedExample.greeting');
        $message = Translation::get('plugin.EnhancedExample.welcomeMessage');

        $output = sprintf(
            '<div class="pmf-plugin-enhanced-greeting">
                <h3>%s, %s!</h3>
                <p>%s</p>
            </div>',
            htmlspecialchars($greeting ?? 'Hello'),
            htmlspecialchars($name),
            htmlspecialchars($message ?? 'Welcome to the Enhanced Example Plugin!')
        );

        $event->setOutput($output);
    }
}
