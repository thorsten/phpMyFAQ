<?php

namespace phpMyFAQ\Configuration;

use phpMyFAQ\ConfigurationMethodsTrait;
use PHPUnit\Framework\TestCase;

class ConfigurationMethodsTraitTest extends TestCase
{
    public function testGetDefaultLanguageReturnsEnWhenConfigMissing(): void
    {
        $configuration = new class() {
            use ConfigurationMethodsTrait;

            public array $config = [];
        };

        $this->assertSame('en', $configuration->getDefaultLanguage());
    }

    public function testGetDefaultLanguageExtractsLanguageCode(): void
    {
        $configuration = new class() {
            use ConfigurationMethodsTrait;

            public array $config = ['main.language' => 'language_de.php'];
        };

        $this->assertSame('de', $configuration->getDefaultLanguage());
    }

    public function testGetRootPathReturnsConstant(): void
    {
        $configuration = new class() {
            use ConfigurationMethodsTrait;

            public array $config = [];
        };

        $this->assertSame(PMF_ROOT_DIR, $configuration->getRootPath());
    }
}
