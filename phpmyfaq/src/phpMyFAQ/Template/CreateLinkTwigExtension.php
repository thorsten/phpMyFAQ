<?php

/**
 * Twig extension to return the URLs for categories and FAQs.
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
 * @since     2024-08-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Template;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Link;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class CreateLinkTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('categoryLink', $this->categoryLink(...)),
            new TwigFunction('faqLink', $this->faqLink(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('categoryLink', $this->categoryLink(...)),
            new TwigFilter('faqLink', $this->faqLink(...)),
        ];
    }

    private function categoryLink(int $categoryId): string
    {
        $configuration = Configuration::getConfigurationInstance();
        $url = sprintf(
            '%sindex.php?action=show&cat=%d',
            $configuration->getDefaultUrl(),
            $categoryId
        );

        $category = new Category($configuration);
        $categoryData = $category->getCategoryData($categoryId);

        $link = new Link($url, $configuration);
        $link->itemTitle = $categoryData->getName();

        return $link->toString();
    }

    private function faqLink(int $categoryId, int $faqId, string $faqLanguage): string
    {
        $configuration = Configuration::getConfigurationInstance();
        $url = sprintf(
            '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
            $configuration->getDefaultUrl(),
            $categoryId,
            $faqId,
            $faqLanguage
        );

        $faq = new Faq($configuration);
        $link = new Link($url, $configuration);
        $link->itemTitle = $faq->getQuestion($faqId);

        return $link->toString();
    }
}
