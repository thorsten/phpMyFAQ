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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-27
 */

namespace phpMyFAQ\Template;

use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FormatDateTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('formatDate', $this->formatDate(...)),
        ];
    }

    private function formatDate(string $string): string
    {
        $faqConfig = Configuration::getConfigurationInstance();
        $date = new Date($faqConfig);
        return $date->format($string);
    }
}
