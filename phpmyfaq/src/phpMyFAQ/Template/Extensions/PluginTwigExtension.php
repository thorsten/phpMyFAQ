<?php

/**
 * Twig extension to trigger plugin events.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-26
 */

declare(strict_types=1);

namespace phpMyFAQ\Template\Extensions;

use phpMyFAQ\Configuration;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PluginTwigExtension extends AbstractExtension
{
    private Configuration $configuration;
    public function __construct()
    {
        $this->configuration = Configuration::getConfigurationInstance();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('phpMyFAQPlugin', [$this, 'triggerPluginEvent']),
        ];
    }

    public function triggerPluginEvent(string $eventName, mixed $data = null): string
    {
        return $this->configuration->getPluginManager()->triggerEvent($eventName, $data);
    }
}
