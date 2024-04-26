<?php

namespace phpMyFAQ\Template;

use phpMyFAQ\Date;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class IsoDateTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('createIsoDate', $this->createIsoDate(...)),
        ];
    }

    private function createIsoDate(string $string): string
    {
        return Date::createIsoDate($string);
    }
}
