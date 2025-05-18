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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-08-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Link;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;
use Twig\Extension\AbstractExtension;

class CreateLinkTwigExtension extends AbstractExtension
{
    #[asTwigFilter('categoryLink')]
    #[asTwigFunction('categoryLink')]
    public static function categoryLink(int $categoryId): string
    {
        $configuration = Configuration::getConfigurationInstance();
        $url = sprintf(
            '%sindex.php?action=show&cat=%d',
            $configuration->getDefaultUrl(),
            $categoryId
        );

        $category = new Category($configuration);
        $categoryEntity = $category->getCategoryData($categoryId);

        $link = new Link($url, $configuration);
        $link->itemTitle = $categoryEntity->getName();

        return $link->toString();
    }

    #[asTwigFilter('faqLink')]
    #[asTwigFunction('faqLink')]
    public static function faqLink(int $categoryId, int $faqId, string $faqLanguage): string
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
