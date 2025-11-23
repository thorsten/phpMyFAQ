<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\TestCase;

/**
 * Class HelperTest
 */
class HelperTest extends TestCase
{
    /** @var Helper */
    protected Helper $instance;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->instance = new Helper();
    }

    public function testAddMenuEntryWithRouteWithoutPermission(): void
    {
        $expected = '<a class="nav-link" href="./edit">Categories</a>' . "\n";
        $actual = $this->instance->addMenuEntry('', 'msgHeaderCategoryOverview', 'edit', false);

        $this->assertEquals($expected, $actual);
    }

    public function testAddMenuEntryWithProperPermission(): void
    {
        $expected = '';
        $actual = $this->instance->addMenuEntry('', 'msgHeaderCategoryOverview');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws Exception
     */
    public function testAddMenuEntryWithProperPermissionAndActive(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $user = new CurrentUser(Configuration::getConfigurationInstance());
        $user->login('admin', 'password');
        $user->setLoggedIn(true);

        $this->instance->setUser($user);
        $expected = '<a class="nav-link" href="./faq/add">Add new FAQ</a>' . "\n";
        $actual = $this->instance->addMenuEntry(PermissionType::FAQ_ADD->value, 'msgAddFAQ', 'faq/add');

        $this->assertEquals($expected, $actual);

        session_destroy();
    }

    public function testAddMenuEntryWithProperPermissionAndMultipleRestrictions(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $user = new CurrentUser(Configuration::getConfigurationInstance());
        $user->login('admin', 'password');
        $user->setLoggedIn(true);

        $this->instance->setUser($user);
        $expected = '<a class="nav-link" href="./user">Users</a>' . "\n";
        $actual = $this->instance->addMenuEntry(
            'add_user+edit_user+delete_user',
            'ad_menu_user_administration',
            'user',
        );

        $this->assertEquals($expected, $actual);

        session_destroy();
    }

    public function testRenderMetaRobotsDropdown(): void
    {
        $expected =
            '<option selected>index, follow</option><option>index, nofollow</option>'
            . '<option>noindex, follow</option><option>noindex, nofollow</option>';
        $actual = $this->instance->renderMetaRobotsDropdown('index, follow');

        $this->assertEquals($expected, $actual);
    }

    public function testSortingKeyOptions(): void
    {
        $expected =
            '<option value="id" selected>ID (default)</option><option value="thema">Title</option>'
            . '<option value="visits">Number of visitors</option><option value="updated">Date</option>'
            . '<option value="author">Author</option>';
        $actual = Helper::sortingKeyOptions('id');

        $this->assertEquals($expected, $actual);
    }

    public function testSortingOrderOptions(): void
    {
        $expected = '<option value="ASC" selected>ascending</option><option value="DESC">descending</option>';
        $actual = Helper::sortingOrderOptions('ASC');

        $this->assertEquals($expected, $actual);
    }

    public function testSortingPopularFaqsOptions(): void
    {
        $expected =
            '<option value="visits" selected>list most visited entries</option>'
            . '<option value="voting">list most voted entries</option>';
        $actual = Helper::sortingPopularFaqsOptions('visits');

        $this->assertEquals($expected, $actual);
    }

    public function testSearchRelevanceOptions(): void
    {
        $expected =
            '<option value="thema,content,keywords">Question - Answer - Keywords</option>'
            . '<option value="thema,keywords,content">Question - Keywords - Answer</option>'
            . '<option value="content,thema,keywords">Answer - Question - Keywords</option>'
            . '<option value="content,keywords,thema">Answer - Keywords - Question</option>'
            . '<option value="keywords,content,thema">Keywords - Answer - Question</option>'
            . '<option value="keywords,thema,content">Keywords - Question - Answer</option>';
        $actual = Helper::searchRelevanceOptions(0);

        $this->assertEquals($expected, $actual);
    }

    public function testRenderReleaseTypeOptions(): void
    {
        $optionsDevelopment = Helper::renderReleaseTypeOptions(ReleaseType::DEVELOPMENT->value);
        $optionsStable = Helper::renderReleaseTypeOptions(ReleaseType::STABLE->value);
        $optionsNightly = Helper::renderReleaseTypeOptions(ReleaseType::NIGHTLY->value);

        // Assert the HTML output for each release type
        $expectedDevelopment =
            '<option value="development" selected>Development</option>'
            . '<option value="stable">Stable</option>'
            . '<option value="nightly">Nightly</option>';
        $expectedStable =
            '<option value="development">Development</option>'
            . '<option value="stable" selected>Stable</option>'
            . '<option value="nightly">Nightly</option>';
        $expectedNightly =
            '<option value="development">Development</option>'
            . '<option value="stable">Stable</option>'
            . '<option value="nightly" selected>Nightly</option>';

        $this->assertEquals($expectedDevelopment, $optionsDevelopment);
        $this->assertEquals($expectedStable, $optionsStable);
        $this->assertEquals($expectedNightly, $optionsNightly);
    }
}
