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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-27
 */

namespace phpMyFAQ\Template\Extensions;

use phpMyFAQ\Translation;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PermissionTranslationTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('permission', $this->getPermissionTranslation(...)),
        ];
    }

    private function getPermissionTranslation(string $string): string
    {
        $translationCode = sprintf('permission::%s', $string);
        return Translation::get($translationCode);
    }
}
