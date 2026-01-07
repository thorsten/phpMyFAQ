<?php

/**
 * The Hello World Plugin Configuration
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
 * @since     2026-01-06
 */

declare(strict_types=1);

namespace phpMyFAQ\Plugin\HelloWorld;

use phpMyFAQ\Plugin\PluginConfigurationInterface;

class HelloWorldConfiguration implements PluginConfigurationInterface
{
    public string $greeting = 'Hello, World!';
}
