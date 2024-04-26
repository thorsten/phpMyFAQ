<?php

namespace phpMyFAQ\Template;

use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FormatDateTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('formatDate', $this->formatDate(...)),
        ];
    }

    private function formatDate(string $string): string
    {
        $faqConfig = Configuration::getConfigurationInstance();
        $date = new Date($faqConfig);
        return $date->format($string);
    }
}
