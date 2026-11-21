<?php

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\ConfigurationMethodsTrait;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Permission\RightGrant;
use phpMyFAQ\Permission\RightScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(ReadScope::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Database::class)]
#[UsesClass(Sqlite3::class)]
#[UsesClass(RightGrant::class)]
#[UsesClass(RightScope::class)]
#[UsesTrait(ConfigurationMethodsTrait::class)]
class ReadScopeTest extends TestCase
{
    private static ?Configuration $configuration = null;

    private static ?string $databaseFile = null;

    private ?Configuration $previousConfiguration = null;

    /**
     * ReadScope escapes values through the global Configuration singleton. The test needs
     * one of its own instead of whatever an earlier test left behind. It is built here,
     * outside the per-test coverage window, because constructing a Configuration touches
     * far more classes than this test is about.
     */
    public static function setUpBeforeClass(): void
    {
        $databaseFile = tempnam(sys_get_temp_dir(), 'pmf-read-scope-test-');
        if ($databaseFile === false || !copy(PMF_TEST_DIR . '/test.db', $databaseFile)) {
            throw new \RuntimeException('Unable to copy the prepared SQLite test database.');
        }

        $driver = new Sqlite3();
        $driver->connect($databaseFile, '', '');

        self::$databaseFile = $databaseFile;
        self::$configuration = new Configuration($driver);
    }

    public static function tearDownAfterClass(): void
    {
        self::$configuration = null;

        if (self::$databaseFile !== null) {
            @unlink(self::$databaseFile);
            self::$databaseFile = null;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $singleton = new ReflectionProperty(Configuration::class, 'configuration');
        $this->previousConfiguration = $singleton->getValue();
        $singleton->setValue(null, self::$configuration);
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Configuration::class, 'configuration')->setValue(null, $this->previousConfiguration);

        parent::tearDown();
    }

    public function testUnrestrictedScopeAddsNothingToTheQuery(): void
    {
        $scope = ReadScope::unrestricted();

        self::assertTrue($scope->isUnrestricted());
        self::assertSame('', $scope->toSqlFragment());
        self::assertTrue($scope->allows(42, 'de'));
    }

    /**
     * The common case has to stay free: an unscoped grant must leave the FAQ queries byte for
     * byte as they were, otherwise every category listing pays for a feature nobody enabled.
     */
    public function testGrantWithoutRestrictionsAddsNothingToTheQuery(): void
    {
        $scope = ReadScope::forUser($this->permission(hasRight: true), 5);

        self::assertTrue($scope->isUnrestricted());
        self::assertSame('', $scope->toSqlFragment());
    }

    public function testMissingRightMatchesNoRows(): void
    {
        $scope = ReadScope::forUser($this->permission(hasRight: false), 5);

        self::assertFalse($scope->isUnrestricted());
        self::assertSame(' AND 1 = 0', $scope->toSqlFragment());
        self::assertFalse($scope->allows(1, 'en'));
    }

    /**
     * A scope without any grant means "holds the right nowhere", which is not the same as an
     * unrestricted grant. Rendering nothing for it would silently grant full access.
     */
    public function testRightWithoutAnyGrantMatchesNoRows(): void
    {
        $scope = ReadScope::forUser($this->permissionWithScope(RightScope::none()), 5);

        self::assertFalse($scope->isUnrestricted());
        self::assertSame(' AND 1 = 0', $scope->toSqlFragment());
        self::assertSame(' AND 1 = 0', $scope->toCategoryRelationConstraint('fcr'));
        self::assertFalse($scope->allows(1, 'en'));
    }

    public function testCategoryRestrictionRendersAnExistsSubQuery(): void
    {
        $scope = ReadScope::forUser($this->permission(hasRight: true, categories: [2, 3]), 5);

        $fragment = $scope->toSqlFragment();

        self::assertStringContainsString('EXISTS (SELECT 1 FROM faqcategoryrelations pmfrs', $fragment);
        self::assertStringContainsString('pmfrs.record_id = fd.id', $fragment);
        self::assertStringContainsString('pmfrs.record_lang = fd.lang', $fragment);
        self::assertStringContainsString('pmfrs.category_id IN (2, 3)', $fragment);
        self::assertStringNotContainsString('1 = 0', $fragment);
    }

    /**
     * Tags alias faqdata as "d", so a hard-coded "fd" would produce invalid SQL there.
     */
    public function testFragmentHonoursTheGivenTableAlias(): void
    {
        $scope = ReadScope::forUser($this->permission(hasRight: true, categories: [7]), 5);

        $fragment = $scope->toSqlFragment('d');

        self::assertStringContainsString('pmfrs.record_id = d.id', $fragment);
        self::assertStringNotContainsString('fd.id', $fragment);
    }

    public function testCategoryAndLanguageRestrictionsAreCombined(): void
    {
        $scope = ReadScope::forUser(
            $this->permission(hasRight: true, categories: [2], languages: ['de']),
            5,
        );

        $fragment = $scope->toSqlFragment();

        self::assertStringContainsString("fd.lang IN ('de')", $fragment);
        self::assertStringContainsString('pmfrs.category_id IN (2)', $fragment);
        self::assertFalse($scope->isUnrestricted());
        self::assertTrue($scope->allows(2, 'de'));
        self::assertFalse($scope->allows(3, 'de'));
        self::assertFalse($scope->allows(2, 'en'));
        self::assertFalse($scope->allows(3, 'en'));
    }

    /**
     * "German only" through one grant and "category 1 only" through another do not add up to
     * "category 2 in English": each grant pairs its own categories with its own languages.
     */
    public function testRestrictionsOfSeparateGrantsAreNotMerged(): void
    {
        $scope = ReadScope::forUser($this->permissionWithScope($this->germanOrCategoryOne()), 5);

        self::assertFalse($scope->isUnrestricted());
        self::assertFalse($scope->allows(2, 'en'));
        self::assertTrue($scope->allows(2, 'de'));
        self::assertTrue($scope->allows(1, 'en'));
    }

    public function testSeparateGrantsRenderOneAlternativeEach(): void
    {
        $scope = ReadScope::forUser($this->permissionWithScope($this->germanOrCategoryOne()), 5);

        self::assertSame(
            " AND ((fd.lang IN ('de')) OR (EXISTS (SELECT 1 FROM faqcategoryrelations pmfrs"
            . ' WHERE pmfrs.record_id = fd.id AND pmfrs.record_lang = fd.lang'
            . ' AND pmfrs.category_id IN (1))))',
            $scope->toSqlFragment(),
        );
        self::assertSame(
            " AND ((fd.lang IN ('de')) OR (fcr.category_id IN (1)))",
            $scope->toSqlFragment('fd', 'fcr'),
        );
    }

    public function testCategoryRelationConstraintPairsEachCategoryWithTheLanguagesOfItsGrant(): void
    {
        $scope = ReadScope::forUser($this->permissionWithScope($this->germanOrCategoryOne()), 5);

        self::assertSame(
            " AND ((fcr.record_lang IN ('de')) OR (fcr.category_id IN (1)))",
            $scope->toCategoryRelationConstraint('fcr'),
        );
    }

    /**
     * A query that joins faqcategoryrelations itself must constrain that alias instead of
     * relying on EXISTS, or it could still project a denied category id for an FAQ that also
     * belongs to an allowed category.
     */
    public function testOuterRelationAliasReplacesTheExistsSubQuery(): void
    {
        $scope = ReadScope::forUser($this->permission(hasRight: true, categories: [2, 3]), 5);

        $fragment = $scope->toSqlFragment('fd', 'fcr');

        self::assertStringContainsString('fcr.category_id IN (2, 3)', $fragment);
        self::assertStringNotContainsString('EXISTS', $fragment);
    }

    public function testCategoryRelationConstraintRendersNothingWithoutCategoryRestriction(): void
    {
        self::assertSame('', ReadScope::unrestricted()->toCategoryRelationConstraint('fcr'));
        self::assertSame(
            '',
            ReadScope::forUser($this->permission(hasRight: true), 5)->toCategoryRelationConstraint('fcr'),
        );
    }

    public function testCategoryRelationConstraintMatchesNoRowsWithoutTheRight(): void
    {
        $scope = ReadScope::forUser($this->permission(hasRight: false), 5);

        self::assertSame(' AND 1 = 0', $scope->toCategoryRelationConstraint('fcr'));
    }

    public function testCategoryRelationConstraintHonoursTheGivenAlias(): void
    {
        $scope = ReadScope::forUser($this->permission(hasRight: true, categories: [7]), 5);

        self::assertSame(' AND ((rel.category_id IN (7)))', $scope->toCategoryRelationConstraint('rel'));
    }

    /**
     * The read right is one decision, so it must be read once — not re-derived per query.
     */
    public function testTheRightIsEvaluatedExactlyOnce(): void
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission->expects($this->once())
            ->method('hasPermission')
            ->with(5, PermissionType::FAQS_VIEW->value)
            ->willReturn(true);
        $permission
            ->expects($this->once())
            ->method('getScopeForRight')
            ->with(5, PermissionType::FAQS_VIEW->value)
            ->willReturn(RightScope::unrestricted());

        $scope = ReadScope::forUser($permission, 5);

        // Rendering repeatedly must not go back to the permission layer.
        $scope->toSqlFragment();
        $scope->toSqlFragment();
        $scope->allows(1, 'en');
    }

    /**
     * @param array<int>|null    $categories
     * @param array<string>|null $languages
     */
    private function permission(
        bool $hasRight,
        ?array $categories = null,
        ?array $languages = null,
    ): PermissionInterface {
        return $this->permissionWithScope(
            $categories === null && $languages === null
                ? RightScope::unrestricted()
                : RightScope::ofGrants(new RightGrant($categories, $languages)),
            $hasRight,
        );
    }

    private function permissionWithScope(RightScope $scope, bool $hasRight = true): PermissionInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn($hasRight);
        $permission->method('getScopeForRight')->willReturn($scope);

        return $permission;
    }

    /**
     * A direct grant for German only next to a group grant for category 1 only.
     */
    private function germanOrCategoryOne(): RightScope
    {
        return RightScope::ofGrants(new RightGrant(languages: ['de']), new RightGrant([1]));
    }
}
