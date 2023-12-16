<?php

namespace phpMyFAQ\Template;

use phpMyFAQ\Helper\LanguageHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminTwigExtension extends AbstractExtension
{
    public function __construct()
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderLanguageOptions', [$this, 'renderLanguageOptions'], ['is_safe' => ['html']]),
        ];
    }

    public function renderLanguageOptions(string $selectedLanguage): string
    {
        $availableLanguages = LanguageHelper::getAvailableLanguages();
        if (count($availableLanguages) > 0) {
            return LanguageHelper::renderLanguageOptions(
                str_replace(['language_', '.php'], '', $selectedLanguage),
                false,
                true
            );
        } else {
            return '<option value="language_en.php">English</option>';
        }
    }
}
