<?php

declare(strict_types=1);

namespace phpMyFAQ\Template;

use phpMyFAQ\Configuration;
use phpMyFAQ\Tags;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class TagNameTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('tagName', $this->getTagName(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('tagName', $this->getTagName(...)),
        ];
    }

    public function getTagName(int $tagId): string
    {
        $tags = new Tags(Configuration::getConfigurationInstance());
        return $tags->getTagNameById($tagId);
    }
}
