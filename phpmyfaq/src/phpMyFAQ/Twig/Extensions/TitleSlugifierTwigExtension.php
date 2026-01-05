<?php

/**
 * Twig extension to slugify titles
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-05
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Link\Util\TitleSlugifier;
use Twig\Attribute\AsTwigFilter;
use Twig\Extension\AbstractExtension;

class TitleSlugifierTwigExtension extends AbstractExtension
{
    #[AsTwigFilter(name: 'slugify')]
    public static function slugify(string $title): string
    {
        return TitleSlugifier::slug($title);
    }
}
