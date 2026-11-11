<?php

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

/**
 * Runs the scope fragment against a private copy of the SQLite test database, so the tests
 * assert which FAQs a scoped editor gets to see rather than how the SQL is spelled.
 */
#[CoversClass(EditScope::class)]
#[UsesNamespace('phpMyFAQ')]
final class EditScopeTest extends TestCase
{
    use TestDatabaseTrait;

    private const int IN_SCOPE = 91001;
    private const int OUT_OF_SCOPE = 91002;
    private const int PARTLY_OUT_OF_SCOPE = 91003;
    private const int UNCATEGORIZED = 91004;
    private const int OTHER_LANGUAGE = 91005;

    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();

        $this->insertFaq(self::IN_SCOPE, 'en', [1]);
        $this->insertFaq(self::OUT_OF_SCOPE, 'en', [2]);
        $this->insertFaq(self::PARTLY_OUT_OF_SCOPE, 'en', [1, 2]);
        $this->insertFaq(self::UNCATEGORIZED, 'en', []);
        $this->insertFaq(self::OTHER_LANGUAGE, 'de', [1]);
    }

    public function testUnrestrictedGrantAddsNothingToTheQuery(): void
    {
        $scope = EditScope::forUser($this->permission(hasRight: true), 5);

        self::assertSame('', $scope->toSqlFragment());
    }

    public function testUnrestrictedGrantAllowsEveryFaq(): void
    {
        $scope = EditScope::forUser($this->permission(hasRight: true), 5);

        self::assertTrue($scope->allowsFaq([2, 3], 'de'));
        self::assertTrue($scope->allowsFaq([], 'en'));
    }

    public function testTheScopeIsResolvedForTheFaqEditRight(): void
    {
        $right = PermissionType::FAQ_EDIT->value;
        $permission = $this->createMock(PermissionInterface::class);
        $permission->expects(self::once())->method('hasPermission')->with(5, $right)->willReturn(true);
        $permission->expects(self::once())->method('getAllowedCategoriesForRight')->with(5, $right);
        $permission->expects(self::once())->method('getAllowedLanguagesForRight')->with(5, $right);

        EditScope::forUser($permission, 5);
    }

    public function testMissingRightMatchesNoFaq(): void
    {
        $scope = EditScope::forUser($this->permission(hasRight: false), 5);

        self::assertSame([], $this->faqIdsWithin($scope));
        self::assertFalse($scope->allowsFaq([1], 'en'));
    }

    /**
     * An empty allowed list means "holds the right nowhere", which is not the same as the null
     * that means "unrestricted". Rendering nothing for it would silently grant full access.
     */
    public function testEmptyCategoryListMatchesNoFaq(): void
    {
        $scope = EditScope::forUser($this->permission(hasRight: true, categories: []), 5);

        self::assertSame([], $this->faqIdsWithin($scope));
        self::assertFalse($scope->allowsFaq([1], 'en'));
    }

    public function testEmptyLanguageListMatchesNoFaq(): void
    {
        $scope = EditScope::forUser($this->permission(hasRight: true, languages: []), 5);

        self::assertSame([], $this->faqIdsWithin($scope));
        self::assertFalse($scope->allowsFaq([1], 'en'));
    }

    /**
     * The edit page rejects an FAQ as soon as one of its categories is outside the scope, and
     * an FAQ without any category too. The listings must not offer what the edit page refuses.
     */
    public function testCategoryRestrictionKeepsOnlyFaqsLyingEntirelyWithinTheScope(): void
    {
        $scope = EditScope::forUser($this->permission(hasRight: true, categories: [1]), 5);

        self::assertSame([self::IN_SCOPE, self::OTHER_LANGUAGE], $this->faqIdsWithin($scope));
    }

    public function testCategoryRestrictionIsEvaluatedTheSameWayOutsideOfSql(): void
    {
        $scope = EditScope::forUser($this->permission(hasRight: true, categories: [1]), 5);

        self::assertTrue($scope->allowsFaq([1], 'en'));
        self::assertFalse($scope->allowsFaq([2], 'en'));
        self::assertFalse($scope->allowsFaq([1, 2], 'en'));
        self::assertFalse($scope->allowsFaq([], 'en'));
    }

    public function testLanguageRestrictionKeepsOnlyFaqsInTheScopedLanguages(): void
    {
        $scope = EditScope::forUser($this->permission(hasRight: true, languages: ['de']), 5);

        self::assertSame([self::OTHER_LANGUAGE], $this->faqIdsWithin($scope));
        self::assertTrue($scope->allowsFaq([], 'de'));
        self::assertFalse($scope->allowsFaq([1], 'en'));
    }

    public function testCategoryAndLanguageRestrictionsApplyTogether(): void
    {
        $scope = EditScope::forUser($this->permission(hasRight: true, categories: [1, 2], languages: ['en']), 5);

        self::assertSame([self::IN_SCOPE, self::OUT_OF_SCOPE, self::PARTLY_OUT_OF_SCOPE], $this->faqIdsWithin($scope));
        self::assertFalse($scope->allowsFaq([1], 'de'));
    }

    /**
     * Tags alias faqdata as "d", so a hard-coded "fd" would produce invalid SQL there.
     */
    public function testFragmentHonoursTheGivenTableAlias(): void
    {
        $scope = EditScope::forUser($this->permission(hasRight: true, categories: [1], languages: ['en']), 5);

        self::assertSame([self::IN_SCOPE], $this->faqIdsWithin($scope, 'd'));
    }

    public function testLanguageCodesAreEscaped(): void
    {
        $scope = EditScope::forUser($this->permission(hasRight: true, languages: ["en') OR ('1' = '1"]), 5);

        self::assertSame([], $this->faqIdsWithin($scope));
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
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn($hasRight);
        $permission->method('getAllowedCategoriesForRight')->willReturn($categories);
        $permission->method('getAllowedLanguagesForRight')->willReturn($languages);

        return $permission;
    }

    /**
     * @param list<int> $categoryIds
     */
    private function insertFaq(int $id, string $language, array $categoryIds): void
    {
        $db = $this->configuration->getDb();
        $db->query(sprintf(
            'INSERT INTO %sfaqdata (id, lang, solution_id, status, sticky, thema, content, author, email, updated)'
            . " VALUES (%d, '%s', %d, 'draft', 0, 'Question %d', 'Answer', 'Tester', 'tester@example.org', '%s')",
            Database::getTablePrefix(),
            $id,
            $language,
            1000 + $id,
            $id,
            date('YmdHis'),
        ));

        foreach ($categoryIds as $categoryId) {
            $db->query(sprintf(
                'INSERT INTO %sfaqcategoryrelations (category_id, category_lang, record_id, record_lang)'
                . " VALUES (%d, '%s', %d, '%s')",
                Database::getTablePrefix(),
                $categoryId,
                $language,
                $id,
                $language,
            ));
        }
    }

    /**
     * @return list<int> the ids of the FAQs inserted by this test that the scope admits
     */
    private function faqIdsWithin(EditScope $scope, string $alias = 'fd'): array
    {
        $db = $this->configuration->getDb();
        $result = $db->query(sprintf(
            'SELECT %2$s.id AS id FROM %1$sfaqdata %2$s WHERE %2$s.id >= %3$d%4$s ORDER BY %2$s.id',
            Database::getTablePrefix(),
            $alias,
            self::IN_SCOPE,
            $scope->toSqlFragment($alias),
        ));

        self::assertNotFalse($result);

        return array_map(static fn(\stdClass $row): int => (int) $row->id, $db->fetchAll($result) ?? []);
    }
}
