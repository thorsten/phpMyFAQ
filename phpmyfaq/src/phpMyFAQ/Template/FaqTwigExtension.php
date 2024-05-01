<?php

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
