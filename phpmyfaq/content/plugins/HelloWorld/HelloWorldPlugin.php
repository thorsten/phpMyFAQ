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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-07-10
 */

namespace phpMyFAQ\Plugin\HelloWorld;

use phpMyFAQ\Plugin\PluginEvent;
use phpMyFAQ\Plugin\PluginInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class HelloWorldPlugin implements PluginInterface
{
    public function getName(): string
    {
        return 'HelloWorld';
    }

    public function getVersion(): string
    {
        return '0.1.0';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getConfig(): array
    {
        return [];
    }

    public function registerEvents(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->addListener('content.loaded', [$this, 'onContentLoaded']);
    }

    public function onContentLoaded(PluginEvent $event): void
    {
        $content = $event->getData();
        $output = "phpMyFAQ says: Content Loaded: " . $content . "<br>";
        $event->setOutput($output);
    }
}
