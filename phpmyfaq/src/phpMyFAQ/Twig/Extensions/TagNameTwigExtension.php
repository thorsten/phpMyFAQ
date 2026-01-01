<?php

/**
 * Twig extension to get the tag name by its ID.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-05-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Tags;
use Twig\Attribute\AsTwigFilter;
use Twig\Extension\AbstractExtension;

class TagNameTwigExtension extends AbstractExtension
{
    #[AsTwigFilter(name: 'tagName')]
    public static function getTagName(int $tagId): string
    {
        $tags = new Tags(Configuration::getConfigurationInstance());
        return $tags->getTagNameById($tagId);
    }
}
