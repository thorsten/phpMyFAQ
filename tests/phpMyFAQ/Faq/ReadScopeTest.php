<?php

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\PermissionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReadScope::class)]
class ReadScopeTest extends TestCase
{
    public function testUnrestrictedScopeAddsNothingToTheQuery(): void
    {
        $scope = ReadScope::unrestricted();

        self::assertTrue($scope->isUnrestricted());
        self::assertSame('', $scope->toSqlFragment());
        self::assertTrue($scope->allowsCategory(42));
        self::assertTrue($scope->allowsLanguage('de'));
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
        self::assertFalse($scope->allowsCategory(1));
        self::assertFalse($scope->allowsLanguage('en'));
    }

    /**
     * An empty allowed list means "holds the right nowhere", which is not the same as the null
     * that means "unrestricted". Rendering nothing for it would silently grant full access.
     */
    public function testEmptyCategoryListMatchesNoRows(): void
    {
        $scope = ReadScope::forUser($this->permission(hasRight: true, categories: []), 5);

        self::assertSame(' AND 1 = 0', $scope->toSqlFragment());
    }

    public function testEmptyLanguageListMatchesNoRows(): void
    {
        $scope = ReadScope::forUser($this->permission(hasRight: true, languages: []), 5);

        self::assertSame(' AND 1 = 0', $scope->toSqlFragment());
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
        self::assertTrue($scope->allowsCategory(2));
        self::assertFalse($scope->allowsCategory(3));
        self::assertTrue($scope->allowsLanguage('de'));
        self::assertFalse($scope->allowsLanguage('en'));
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
        $permission->expects($this->once())->method('getAllowedCategoriesForRight')->willReturn(null);
        $permission->expects($this->once())->method('getAllowedLanguagesForRight')->willReturn(null);

        $scope = ReadScope::forUser($permission, 5);

        // Rendering repeatedly must not go back to the permission layer.
        $scope->toSqlFragment();
        $scope->toSqlFragment();
        $scope->allowsCategory(1);
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
}
