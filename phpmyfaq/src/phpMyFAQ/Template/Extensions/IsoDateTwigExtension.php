<?php

/**
 * Twig extension to create an ISO date.
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

namespace phpMyFAQ\Template\Extensions;

use phpMyFAQ\Date;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class IsoDateTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('createIsoDate', $this->createIsoDate(...)),
        ];
    }

    private function createIsoDate(string $string): string
    {
        return Date::createIsoDate($string);
    }
}
