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
 * @copyright 2024-2026 phpMyFAQ Team
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
use phpMyFAQ\Link\Util\TitleSlugifier;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;
use Twig\Extension\AbstractExtension;

class CreateLinkTwigExtension extends AbstractExtension
{
    #[AsTwigFilter(name: 'categoryLink')]
    #[AsTwigFunction(name: 'categoryLink')]
    public static function categoryLink(int $categoryId): string
    {
        $configuration = Configuration::getConfigurationInstance();

        $category = new Category($configuration);
        $categoryEntity = $category->getCategoryData($categoryId);

        $urlString = '%scategory/%d/%s.html';
        $url = sprintf(
            $urlString,
            $configuration->getDefaultUrl(),
            $categoryId,
            TitleSlugifier::slug($categoryEntity->getName()),
        );

        $link = new Link($url, $configuration);
        $link->setTitle($categoryEntity->getName());

        return $link->toString();
    }

    #[AsTwigFilter(name: 'faqLink')]
    #[AsTwigFunction(name: 'faqLink')]
    public static function faqLink(int $categoryId, int $faqId, string $faqLanguage): string
    {
        $configuration = Configuration::getConfigurationInstance();

        $faq = new Faq($configuration);
        $question = $faq->getQuestion($faqId);

        $urlString = '%scontent/%d/%d/%s/%s.html';
        $url = sprintf(
            $urlString,
            $configuration->getDefaultUrl(),
            $categoryId,
            $faqId,
            $faqLanguage,
            TitleSlugifier::slug($question),
        );

        $link = new Link($url, $configuration);
        $link->setTitle($question);

        return $link->toString();
    }
}
