<?php

/**
 * Twig extension to return the category name by category ID.
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
 * @since     2024-04-26
 */

namespace phpMyFAQ\Template\Extensions;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CategoryNameTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('categoryName', $this->getCategoryName(...)),
        ];
    }
    public function getFilters(): array
    {
        return [
            new TwigFilter('categoryName', $this->getCategoryName(...)),
        ];
    }

    private function getCategoryName(int $categoryId): string
    {
        $category = new Category(Configuration::getConfigurationInstance());

        $categoryData = $category->getCategoryData($categoryId);
        return $categoryData->getName();
    }
}
