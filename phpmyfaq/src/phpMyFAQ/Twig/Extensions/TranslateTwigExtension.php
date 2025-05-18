<?php

/**
 * Twig extension to translate the given translation key
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
 * @since     2024-04-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Translation;
use Twig\Attribute\AsTwigFilter;
use Twig\Extension\AbstractExtension;

class TranslateTwigExtension extends AbstractExtension
{
    #[AsTwigFilter('translate')]
    public static function translate(string $translationKey): string
    {
        return Translation::get($translationKey) ?? $translationKey;
    }
}
