<?php

declare(strict_types=1);

/**
 * Twig extension to return the FAQ question by FAQ ID.
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
 * @since     2024-05-01
 */

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use Twig\Attribute\AsTwigFilter;
use Twig\Extension\AbstractExtension;

class FaqTwigExtension extends AbstractExtension
{
    #[asTwigFilter('faqQuestion')]
    public static function getFaqQuestion(int $faqId): string
    {
        $faq = new Faq(Configuration::getConfigurationInstance());
        return $faq->getQuestion($faqId);
    }
}
