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
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Date;
use Twig\Attribute\AsTwigFilter;
use Twig\Extension\AbstractExtension;

class IsoDateTwigExtension extends AbstractExtension
{
    #[AsTwigFilter(name: 'createIsoDate')]
    public static function createIsoDate(string $string): string
    {
        return Date::createIsoDate($string);
    }
}
