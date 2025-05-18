<?php

/**
 * Twig extension to format the date
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Template
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-27
 */

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use Twig\Attribute\AsTwigFilter;
use Twig\Extension\AbstractExtension;

class FormatDateTwigExtension extends AbstractExtension
{
    #[asTwigFilter('formatDate')]
    public static function formatDate(string $string): string
    {
        $faqConfig = Configuration::getConfigurationInstance();
        $date = new Date($faqConfig);
        return $date->format($string);
    }
}
