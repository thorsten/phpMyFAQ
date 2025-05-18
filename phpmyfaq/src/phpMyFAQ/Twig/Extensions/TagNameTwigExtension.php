<?php

declare(strict_types=1);

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Tags;
use Twig\Attribute\AsTwigFilter;
use Twig\Extension\AbstractExtension;

class TagNameTwigExtension extends AbstractExtension
{
    #[asTwigFilter('tagName')]
    public static function getTagName(int $tagId): string
    {
        $tags = new Tags(Configuration::getConfigurationInstance());
        return $tags->getTagNameById($tagId);
    }
}
