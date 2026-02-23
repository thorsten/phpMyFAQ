<?php

/**
 * Configuration class for ReadingTime Plugin
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

namespace phpMyFAQ\Plugin\ReadingTime;

use phpMyFAQ\Plugin\PluginConfigurationInterface;

class ReadingTimePluginConfiguration implements PluginConfigurationInterface
{
    public function __construct(
        public int $wordsPerMinute = 200,
        public bool $showIcon = true,
    ) {
    }
}
