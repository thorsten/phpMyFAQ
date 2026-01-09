<?php

/**
 * The Hello World Plugin example class
 *
 * The PluginEvent class is used to pass data between plugins and the application.
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

namespace phpMyFAQ\Plugin\HelloWorld;

require_once __DIR__ . '/HelloWorldPluginConfiguration.php';

use phpMyFAQ\Plugin\PluginEvent;
use phpMyFAQ\Plugin\PluginInterface;
use phpMyFAQ\Plugin\PluginConfigurationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class HelloWorldPlugin implements PluginInterface
{

    public function getName(): string
    {
        return 'HelloWorld';
    }

    public function getVersion(): string
    {
        return '0.3.0';
    }

    public function getDescription(): string
    {
        return 'A simple Hello World plugin';
    }

    public function getAuthor(): string
    {
        return 'phpMyFAQ Team';
    }

    public function getAdvDescription(): string
    {
        return 'A simple Hello World plugin that demonstrates event handling in phpMyFAQ and with Configuration options.';
    }

    public function getImplementation(): string
    {
        return '{{ phpMyFAQPlugin(\'hello.world\', \'Hello, World!\') | raw }} oder {{ phpMyFAQPlugin(\'user.login\', \'John Doe\') | raw }}';
    }

    public function getDependencies(): array
    {
        return [];
    }

    private ?HelloWorldPluginConfiguration $config = null;

    public function getConfig(): ?PluginConfigurationInterface
    {
        if ($this->config === null) {
            if (!class_exists(HelloWorldPluginConfiguration::class)) {
                require_once __DIR__ . '/HelloWorldPluginConfiguration.php';
            }
            $this->config = new HelloWorldPluginConfiguration();
        }
        return $this->config;
    }

    public function getTranslationsPath(): ?string
    {
        return null; // No translations for this simple plugin
    }

    public function registerEvents(EventDispatcherInterface $eventDispatcher): void
    {
        $eventDispatcher->addListener('hello.world', [$this, 'onContentLoaded']);
        $eventDispatcher->addListener('user.login', [$this, 'onUserLogin']);
    }

    public function onContentLoaded(PluginEvent $event): void
    {
        $content = $event->getData();
        $greeting = $this->getConfig()->greeting;
        $output = 'phpMyFAQ says ' . $greeting . ' (Content: ' . $content . ')';
        $event->setOutput($output);
    }

    public function onUserLogin(PluginEvent $event): void
    {
        $username = $event->getData();
        $configuredUsername = $this->getConfig()->username;
        $output = 'Welcome back, ' . $username . '! (Configured: ' . $configuredUsername . ')<br>';
        $event->setOutput($output);
    }
}
