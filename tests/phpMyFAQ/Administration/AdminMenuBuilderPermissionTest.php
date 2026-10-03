<?php

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AdminMenuBuilder::class)]
#[UsesNamespace('phpMyFAQ')]
final class AdminMenuBuilderPermissionTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private AdminMenuBuilder $menuBuilder;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();

        Translation::resetInstance();
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en');

        $this->menuBuilder = new AdminMenuBuilder();
    }

    protected function tearDown(): void
    {
        Translation::resetInstance();
    }

    private function rightId(User $user, string $name): int
    {
        foreach ($user->perm->getAllRightsData() as $right) {
            if ($right['name'] === $name) {
                return (int) $right['right_id'];
            }
        }

        $this->fail('Unknown right ' . $name);
    }

    /**
     * A fresh non-admin account holding exactly the given rights.
     *
     * @param array<int, string> $rights
     */
    private function createUserWithRights(array $rights): User
    {
        $user = new User($this->configuration);
        $this->assertTrue($user->createUser('menu-' . bin2hex(random_bytes(3)), 'password1234'));
        $userId = $user->getUserId();

        foreach ($rights as $right) {
            $this->assertTrue($user->perm->grantUserRight($userId, $this->rightId($user, $right)));
        }

        $loaded = new User($this->configuration);
        $this->assertTrue($loaded->getUserById($userId, true));

        return $loaded;
    }

    public function testSuperAdminMayUseEveryMenuEntry(): void
    {
        $admin = new User($this->configuration);
        $this->assertTrue($admin->getUserById(1, true));
        $this->assertTrue($admin->isSuperAdmin());

        $this->menuBuilder->setUser($admin);

        $this->assertStringContainsString('href="./faqs"', $this->menuBuilder->addMenuEntry('edit_faq', 'msgHeaderFAQOverview', 'faqs'));
        $this->assertStringContainsString('href="./config"', $this->menuBuilder->addMenuEntry('editconfig*add_user', 'ad_menu_config', 'config'));
    }

    public function testRegularUserOnlySeesEntriesForGrantedRights(): void
    {
        $this->menuBuilder->setUser($this->createUserWithRights(['add_faq']));

        $this->assertStringContainsString('href="./faq/add"', $this->menuBuilder->addMenuEntry('add_faq', 'ad_entry_add', 'faq/add'));
        $this->assertSame('', $this->menuBuilder->addMenuEntry('edit_faq', 'ad_menu_entry_edit', 'faqs'));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function restrictionProvider(): iterable
    {
        yield 'single granted' => ['add_faq', true];
        yield 'single missing' => ['edit_faq', false];
        yield 'or with one granted' => ['edit_faq+add_faq', true];
        yield 'or with none granted' => ['edit_faq+delete_faq', false];
        yield 'and with one missing' => ['add_faq*edit_faq', false];
        yield 'and with all granted' => ['add_faq*addnews', true];
        yield 'or of ands' => ['edit_faq*delete_faq+add_faq*addnews', true];
        yield 'unknown right' => ['no_such_right', false];
        yield 'empty restriction' => ['', false];
    }

    #[DataProvider('restrictionProvider')]
    public function testPermissionExpressions(string $restrictions, bool $visible): void
    {
        $this->menuBuilder->setUser($this->createUserWithRights(['add_faq', 'addnews']));

        $entry = $this->menuBuilder->addMenuEntry($restrictions, 'ad_menu_entry_add', 'faq/add');

        $this->assertSame($visible, $entry !== '');
    }

    public function testEntriesCanSkipThePermissionCheck(): void
    {
        $this->menuBuilder->setUser($this->createUserWithRights([]));

        $entry = $this->menuBuilder->addMenuEntry('edit_faq', 'ad_menu_entry_edit', 'faqs', checkPerm: false);

        $this->assertStringContainsString('class="nav-link"', $entry);
        $this->assertStringContainsString('href="./faqs"', $entry);
    }

    public function testUnknownCaptionsAreRenderedVisibly(): void
    {
        $entry = $this->menuBuilder->addMenuEntry('', 'no.such.translation.key', 'route', checkPerm: false);

        $this->assertStringContainsString('No string for no.such.translation.key', $entry);
    }

    /**
     * @return iterable<string, array{bool, array<int, int>, bool, bool}>
     */
    public static function accessProvider(): iterable
    {
        yield 'guest' => [false, [1, 2], false, false];
        yield 'logged in without rights' => [true, [], false, false];
        yield 'logged in with rights' => [true, [1], false, true];
        yield 'super admin without rights' => [true, [], true, true];
    }

    /**
     * @param array<int, int> $rights
     */
    #[DataProvider('accessProvider')]
    public function testCanAccessContent(bool $loggedIn, array $rights, bool $superAdmin, bool $expected): void
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('getAllUserRights')->willReturn($rights);

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn($loggedIn);
        $currentUser->method('getUserId')->willReturn(42);
        $currentUser->method('isSuperAdmin')->willReturn($superAdmin);
        $currentUser->perm = $permission;

        $this->assertSame($expected, $this->menuBuilder->canAccessContent($currentUser));
    }

    public function testTranslationProviderOptions(): void
    {
        $options = AdminMenuBuilder::renderTranslationProviderOptions('deepl');

        $this->assertSame(6, substr_count($options, '<option'));
        $this->assertStringContainsString('<option value="deepl" selected>DeepL</option>', $options);
        $this->assertStringContainsString('<option value="none">None</option>', $options);
        $this->assertSame(1, substr_count($options, ' selected'));
    }

    public function testMailProviderOptions(): void
    {
        $options = AdminMenuBuilder::renderMailProviderOptions('ses');

        $this->assertSame(5, substr_count($options, '<option'));
        $this->assertStringContainsString('<option value="ses" selected>Amazon SES</option>', $options);
        $this->assertSame(1, substr_count($options, ' selected'));
    }

    public function testCacheAdapterOptions(): void
    {
        $options = AdminMenuBuilder::renderCacheAdapterOptions('redis');

        $this->assertSame('<option value="filesystem">Filesystem (default)</option><option value="redis" selected>Redis</option>', $options);
    }

    public function testLayoutModeOptionsUseTranslatedLabels(): void
    {
        $options = AdminMenuBuilder::renderLayoutModeOptions('dark');

        $this->assertSame(4, substr_count($options, '<option'));
        $this->assertStringContainsString('<option value="dark" selected>' . Translation::get('ad_layout_mode_dark') . '</option>', $options);
        $this->assertStringContainsString('<option value="high-contrast">', $options);
        $this->assertStringNotContainsString('<option value="auto" selected', $options);
    }
}
