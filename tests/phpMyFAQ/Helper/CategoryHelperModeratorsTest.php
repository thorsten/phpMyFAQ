<?php

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

/**
 * Resolves the notification recipients of categories against the SQLite test database. The
 * permission level is raised to "medium" explicitly, because moderator groups are only
 * consulted above the basic level and a freshly built test database starts at basic.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(CategoryHelper::class)]
#[UsesNamespace('phpMyFAQ')]
final class CategoryHelperModeratorsTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        $this->assertTrue($this->configuration->set('security.permLevel', 'medium'));
    }

    private function createUserWithEmail(string $login, string $email): int
    {
        $user = new User($this->configuration);
        $this->assertTrue($user->createUser($login, 'password-1234'));
        $this->assertTrue($user->userData()->set(['email'], [$email]));
        // New accounts start blocked and blocked users are not resolved as moderators.
        $this->assertTrue($user->setStatus('active'));

        return $user->getUserId();
    }

    private function helperFor(array $owners, array $moderatorGroups): CategoryHelper
    {
        $category = $this->createStub(Category::class);
        $category->method('getOwner')->willReturnCallback(static fn(int $id): int => $owners[$id] ?? 0);
        $category->method('getModeratorGroupId')->willReturnCallback(
            static fn(int $id): int => $moderatorGroups[$id] ?? 0,
        );

        $helper = new CategoryHelper();
        $helper->setCategory($category)->setConfiguration($this->configuration);

        return $helper;
    }

    public function testModeratorsAreTheOwnersAndTheModeratorGroupMembersWithoutDuplicates(): void
    {
        $owner = $this->createUserWithEmail('category-owner', 'owner@example.org');
        $moderator = $this->createUserWithEmail('category-moderator', 'moderator@example.org');
        $silent = $this->createUserWithEmail('silent-moderator', '');
        $db = $this->configuration->getDb();
        $db->query(sprintf(
            "INSERT INTO %sfaqgroup (group_id, name, description, auto_join) VALUES (77, 'moderators', '', 0)",
            Database::getTablePrefix(),
        ));
        foreach ([$owner, $moderator, $silent] as $member) {
            $db->query(sprintf(
                'INSERT INTO %sfaquser_group (user_id, group_id) VALUES (%d, 77)',
                Database::getTablePrefix(),
                $member,
            ));
        }

        $helper = $this->helperFor(owners: [5 => $owner, 6 => $owner], moderatorGroups: [5 => 77, 6 => 0]);

        $this->assertSame(['owner@example.org', 'moderator@example.org'], $helper->getModerators([5, 6]));
    }

    public function testOwnersWithoutAnEmailAddressAreSkipped(): void
    {
        $owner = $this->createUserWithEmail('mute-owner', '');

        $this->assertSame([], $this->helperFor(owners: [5 => $owner], moderatorGroups: [])->getModerators([5]));
    }

    public function testNoCategoryInstanceMeansNoRecipients(): void
    {
        $helper = new CategoryHelper();
        $helper->setConfiguration($this->configuration);

        $this->assertSame([], $helper->getModerators([5]));
    }
}
