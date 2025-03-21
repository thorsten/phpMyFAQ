<?php

/**
 * Twig extension to return the language name from the language code.
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
 * @since     2024-05-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Template\Extensions;

use phpMyFAQ\Language\LanguageCodes;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class LanguageCodeTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getFromLanguageCode', $this->getFromLanguageCode(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('getFromLanguageCode', $this->getFromLanguageCode(...)),
        ];
    }

    public function getFromLanguageCode(string $languageCode): string
    {
        return LanguageCodes::get($languageCode);
    }
}
