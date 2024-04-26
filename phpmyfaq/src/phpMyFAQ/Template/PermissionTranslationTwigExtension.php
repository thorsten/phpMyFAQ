<?php

namespace phpMyFAQ\Template;

use phpMyFAQ\Translation;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PermissionTranslationTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('permission', $this->getPermissionTranslation(...)),
        ];
    }

    private function getPermissionTranslation(string $string): string
    {
        $translationCode = sprintf(
            'permission::%s',
            $string
        );
        return Translation::get($translationCode);
    }
}
