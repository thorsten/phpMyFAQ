<?php

/**
 * Configuration class for Hello World Plugin
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
 * @since     2024-12-28
 */

declare(strict_types=1);

namespace phpMyFAQ\Plugin\HelloWorld;

use phpMyFAQ\Plugin\PluginConfigurationInterface;

class HelloWorldPluginConfiguration implements PluginConfigurationInterface
{
    public function __construct(
        public string $greeting = 'Hello, World!',
        public string $username = 'John Doe',
    ) {
    }
}
