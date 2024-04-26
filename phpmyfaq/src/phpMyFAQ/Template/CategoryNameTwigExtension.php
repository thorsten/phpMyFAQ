<?php

namespace phpMyFAQ\Template;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CategoryNameTwigExtension extends AbstractExtension
{
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
