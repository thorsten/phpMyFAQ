<?php

/**
 * Twig extension to translate the permission string.
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

use phpMyFAQ\Translation;
use Twig\Attribute\AsTwigFilter;
use Twig\Extension\AbstractExtension;

class PermissionTranslationTwigExtension extends AbstractExtension
{
    #[asTwigFilter('permission')]
    public static function getPermissionTranslation(string $string): string
    {
        $key = sprintf('permission::%s', $string);
        return Translation::has($key) ? Translation::get($key) : '';
    }
}
