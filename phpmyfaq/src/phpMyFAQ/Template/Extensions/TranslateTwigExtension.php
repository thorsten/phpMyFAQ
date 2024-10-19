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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Template\Extensions;

use phpMyFAQ\Translation;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TranslateTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('translate', $this->translate(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('translate', $this->translate(...)),
        ];
    }

    public function translate(string $translationKey): string
    {
        return Translation::get($translationKey) ?? $translationKey;
    }
}
