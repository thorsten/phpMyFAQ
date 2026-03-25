<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AdminMenuBuilderTest extends TestCase
{
    protected AdminMenuBuilder $instance;

    private string $databaseFile;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-helper-test-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        new Configuration($dbHandle);

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->instance = new AdminMenuBuilder();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->databaseFile) && file_exists($this->databaseFile)) {
            @unlink($this->databaseFile);
        }
    }

    private function createUserWithRights(array $rights): User
    {
        $permission = new class($rights) implements PermissionInterface {
            public function __construct(
                private readonly array $rights,
            ) {
            }

            public function hasPermission(int $userId, mixed $right): bool
            {
                return in_array((string) $right, array_column($this->rights, 'name'), true);
            }

            public function getAllRightsData(): array
            {
                return $this->rights;
            }

            public function getAllUserRights(int $userId): array
            {
                return array_column($this->rights, 'right_id');
            }
        };

        $user = $this->createMock(User::class);
        $user->perm = $permission;
        $user->method('getUserId')->willReturn(1);
        $user->method('isSuperAdmin')->willReturn(false);

        return $user;
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
        $user = $this->createUserWithRights([
            ['name' => PermissionType::FAQ_ADD->value, 'right_id' => 1],
        ]);

        $this->instance->setUser($user);
        $expected = '<a class="nav-link" href="./faq/add">Add new FAQ</a>' . "\n";
        $actual = $this->instance->addMenuEntry(PermissionType::FAQ_ADD->value, 'msgAddFAQ', 'faq/add');

        $this->assertEquals($expected, $actual);
    }

    public function testAddMenuEntryWithProperPermissionAndMultipleRestrictions(): void
    {
        $user = $this->createUserWithRights([
            ['name' => 'add_user', 'right_id' => 1],
            ['name' => 'edit_user', 'right_id' => 2],
            ['name' => 'delete_user', 'right_id' => 3],
        ]);

        $this->instance->setUser($user);
        $expected = '<a class="nav-link" href="./user">Users</a>' . "\n";
        $actual = $this->instance->addMenuEntry(
            'add_user+edit_user+delete_user',
            'ad_menu_user_administration',
            'user',
        );

        $this->assertEquals($expected, $actual);
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
        $actual = AdminMenuBuilder::sortingKeyOptions('id');

        $this->assertEquals($expected, $actual);
    }

    public function testSortingOrderOptions(): void
    {
        $expected = '<option value="ASC" selected>ascending</option><option value="DESC">descending</option>';
        $actual = AdminMenuBuilder::sortingOrderOptions('ASC');

        $this->assertEquals($expected, $actual);
    }

    public function testSortingPopularFaqsOptions(): void
    {
        $expected =
            '<option value="visits" selected>list most visited entries</option>'
            . '<option value="voting">list most voted entries</option>';
        $actual = AdminMenuBuilder::sortingPopularFaqsOptions('visits');

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
        $actual = AdminMenuBuilder::searchRelevanceOptions(0);

        $this->assertEquals($expected, $actual);
    }

    public function testRenderReleaseTypeOptions(): void
    {
        $optionsDevelopment = AdminMenuBuilder::renderReleaseTypeOptions(ReleaseType::DEVELOPMENT->value);
        $optionsStable = AdminMenuBuilder::renderReleaseTypeOptions(ReleaseType::STABLE->value);
        $optionsNightly = AdminMenuBuilder::renderReleaseTypeOptions(ReleaseType::NIGHTLY->value);

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
