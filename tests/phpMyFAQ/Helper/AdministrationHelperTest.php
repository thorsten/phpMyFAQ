<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;

/**
 * Class AdministrationTest
 */
class AdministrationHelperTest extends TestCase
{
    /** @var AdministrationHelper */
    protected AdministrationHelper $instance;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->instance = new AdministrationHelper();
    }

    public function testAddMenuEntry(): void
    {
        $expected = '<a class="nav-link" href="?action=edit">Categories</a>' . "\n";
        $actual = $this->instance->addMenuEntry('', 'edit', 'ad_menu_categ_edit', '', false);

        $this->assertEquals($expected, $actual);
    }

    public function testRenderMetaRobotsDropdown(): void
    {
        $expected = '<option selected>index, follow</option><option>index, nofollow</option>' .
            '<option>noindex, follow</option><option>noindex, nofollow</option>';
        $actual = $this->instance->renderMetaRobotsDropdown('index, follow');

        $this->assertEquals($expected, $actual);
    }

    public function testRenderReleaseTypeOptions(): void
    {
        $optionsDevelopment = AdministrationHelper::renderReleaseTypeOptions(ReleaseType::DEVELOPMENT->value);
        $optionsStable = AdministrationHelper::renderReleaseTypeOptions(ReleaseType::STABLE->value);
        $optionsNightly = AdministrationHelper::renderReleaseTypeOptions(ReleaseType::NIGHTLY->value);

        // Assert the HTML output for each release type
        $expectedDevelopment = '<option value="development" selected>Development</option>' .
            '<option value="stable">Stable</option>' .
            '<option value="nightly">Nightly</option>';
        $expectedStable = '<option value="development">Development</option>' .
            '<option value="stable" selected>Stable</option>' .
            '<option value="nightly">Nightly</option>';
        $expectedNightly = '<option value="development">Development</option>' .
            '<option value="stable">Stable</option>' .
            '<option value="nightly" selected>Nightly</option>';

        $this->assertEquals($expectedDevelopment, $optionsDevelopment);
        $this->assertEquals($expectedStable, $optionsStable);
        $this->assertEquals($expectedNightly, $optionsNightly);
    }
}
