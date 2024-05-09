<?php

/**
 * Twig extension to return the FAQ question by FAQ ID.
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
 * @since     2024-05-01
 */

namespace phpMyFAQ\Template;

use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FaqTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('faqQuestion', $this->getFaqQuestion(...)),
        ];
    }

    private function getFaqQuestion(int $faqId): string
    {
        $faq = new Faq(Configuration::getConfigurationInstance());
        return $faq->getQuestion($faqId);
    }
}
