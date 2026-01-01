<?php

/**
 * Twig extension to format bytes
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-05-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Utils;
use Twig\Attribute\AsTwigFilter;
use Twig\Extension\AbstractExtension;

class FormatBytesTwigExtension extends AbstractExtension
{
    #[AsTwigFilter(name: 'formatBytes')]
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        return Utils::formatBytes($bytes, $precision);
    }
}
