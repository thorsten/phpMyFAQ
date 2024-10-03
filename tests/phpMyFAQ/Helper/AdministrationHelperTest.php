<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\TestCase;

/**
 * Class AdministrationTest
 */
class AdministrationHelperTest extends TestCase
{
    /** @var AdministrationHelper */
    protected AdministrationHelper $instance;

    /**
     * @throws Exception
     */
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

    public function testAddMenuEntryWithoutPermission(): void
    {
        $expected = '<a class="nav-link" href="?action=edit">Categories</a>' . "\n";
        $actual = $this->instance->addMenuEntry('', 'edit', 'msgHeaderCategoryOverview', '', false);

        $this->assertEquals($expected, $actual);
    }

    public function testAddMenuEntryWithProperPermission(): void
    {
        $expected = '';
        $actual = $this->instance->addMenuEntry('', 'edit', 'msgHeaderCategoryOverview');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws Exception
     */
    public function testAddMenuEntryWithProperPermissionAndActive(): void
    {
        session_start();

        $user = new CurrentUser(Configuration::getConfigurationInstance());
        $user->login('admin', 'password');
        $user->setLoggedIn(true);

        $this->instance->setUser($user);
        $expected = '<a class="nav-link" href="?action=editentry">Add new FAQ</a>' . "\n";
        $actual = $this->instance->addMenuEntry(
            PermissionType::FAQ_ADD->value,
            'editentry',
            'ad_entry_add',
            'editentry'
        );

        $this->assertEquals($expected, $actual);

        session_destroy();
    }

    public function testAddMenuEntryWithProperPermissionAndMultipleRestrictions(): void
    {
        session_start();

        $user = new CurrentUser(Configuration::getConfigurationInstance());
        $user->login('admin', 'password');
        $user->setLoggedIn(true);

        $this->instance->setUser($user);
        $expected = '<a class="nav-link" href="?action=user">Users</a>' . "\n";
        $actual = $this->instance->addMenuEntry(
            'add_user+edit_user+delete_user',
            'user',
            'ad_menu_user_administration',
            'user'
        );

        $this->assertEquals($expected, $actual);

        session_destroy();
    }

    public function testRenderMetaRobotsDropdown(): void
    {
        $expected = '<option selected>index, follow</option><option>index, nofollow</option>' .
            '<option>noindex, follow</option><option>noindex, nofollow</option>';
        $actual = $this->instance->renderMetaRobotsDropdown('index, follow');

        $this->assertEquals($expected, $actual);
    }

    public function testSortingKeyOptions(): void
    {
        $expected = '<option value="id" selected>ID<br>(default)</option><option value="thema">Title</option>' .
            '<option value="visits">Number of visitors</option><option value="updated">Date</option>' .
            '<option value="author">Author</option>';
        $actual = AdministrationHelper::sortingKeyOptions('id');

        $this->assertEquals($expected, $actual);
    }

    public function testSortingOrderOptions(): void
    {
        $expected = '<option value="ASC" selected>ascending</option><option value="DESC">descending</option>';
        $actual = AdministrationHelper::sortingOrderOptions('ASC');

        $this->assertEquals($expected, $actual);
    }

    public function testSortingPopularFaqsOptions(): void
    {
        $expected = '<option value="visits" selected>list most visited entries</option>' .
            '<option value="voting">list most voted entries</option>';
        $actual = AdministrationHelper::sortingPopularFaqsOptions('visits');

        $this->assertEquals($expected, $actual);
    }

    public function testSearchRelevanceOptions(): void
    {
        $expected = '<option value="thema,content,keywords">Question - Answer - Keywords</option>' .
            '<option value="thema,keywords,content">Question - Keywords - Answer</option>' .
            '<option value="content,thema,keywords">Answer - Question - Keywords</option>' .
            '<option value="content,keywords,thema">Answer - Keywords - Question</option>' .
            '<option value="keywords,content,thema">Keywords - Answer - Question</option>' .
            '<option value="keywords,thema,content">Keywords - Question - Answer</option>';
        $actual = AdministrationHelper::searchRelevanceOptions(0);

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
