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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-05-21
 */

namespace phpMyFAQ\Template;

use phpMyFAQ\Utils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FormatBytesTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('formatBytes', $this->formatBytes(...)),
        ];
    }

    public function formatBytes(int $bytes, int $precision = 2): string
    {
        return Utils::formatBytes($bytes, $precision);
    }
}
