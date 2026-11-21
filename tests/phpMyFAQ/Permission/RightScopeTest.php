<?php

declare(strict_types=1);

namespace phpMyFAQ\Permission;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RightScope::class)]
#[UsesClass(RightGrant::class)]
final class RightScopeTest extends TestCase
{
    /**
     * The scope from the advisory: German in every category through a direct grant, and
     * category 1 in every language through a group.
     */
    private static function germanEverywherePlusCategoryOne(): RightScope
    {
        return RightScope::ofGrants(new RightGrant(languages: ['de']), new RightGrant([1]));
    }

    public function testNoGrantAllowsNothing(): void
    {
        $scope = RightScope::none();

        self::assertTrue($scope->isEmpty());
        self::assertFalse($scope->isUnrestricted());
        self::assertFalse($scope->allows([1], 'en'));
        self::assertFalse($scope->allows([], 'en'));
        self::assertFalse($scope->allowsLanguageInAnyCategory('en'));
        self::assertSame([], $scope->categoryIdsIn('en'));
        self::assertSame([], $scope->languagesInEveryCategory());
        self::assertFalse($scope->covers([1], ['en']));
    }

    public function testUnrestrictedGrantAllowsEverything(): void
    {
        $scope = RightScope::unrestricted();

        self::assertFalse($scope->isEmpty());
        self::assertTrue($scope->isUnrestricted());
        self::assertTrue($scope->allows([1, 2], 'en'));
        self::assertTrue($scope->allows([], 'en'));
        self::assertNull($scope->categoryIdsIn('en'));
        self::assertNull($scope->languagesInEveryCategory());
        self::assertTrue($scope->covers(null, null));
    }

    public function testRestrictionsOfSeparateGrantsAreNotMerged(): void
    {
        $scope = self::germanEverywherePlusCategoryOne();

        self::assertFalse($scope->isUnrestricted());

        // Neither grant covers category 2 in English.
        self::assertFalse($scope->allowsCategory(2, 'en'));
        self::assertFalse($scope->allows([2], 'en'));

        // Each grant still covers what it was given for.
        self::assertTrue($scope->allows([2], 'de'));
        self::assertTrue($scope->allows([1], 'en'));
        self::assertTrue($scope->allows([1, 2], 'de'));
    }

    public function testEveryCategoryMustBeCoveredInTheLanguage(): void
    {
        $scope = self::germanEverywherePlusCategoryOne();

        self::assertFalse($scope->allows([1, 2], 'en'));
    }

    public function testCategoriesMayBeCoveredByDifferentGrantsOfTheSameLanguage(): void
    {
        $scope = RightScope::ofGrants(new RightGrant([1], ['en']), new RightGrant([2], ['en', 'de']));

        self::assertTrue($scope->allows([1, 2], 'en'));
        self::assertFalse($scope->allows([1, 2], 'de'));
    }

    public function testUncategorizedContentNeedsAGrantWithoutCategoryRestriction(): void
    {
        $scope = self::germanEverywherePlusCategoryOne();

        self::assertTrue($scope->allowsUncategorized('de'));
        self::assertTrue($scope->allows([], 'de'));
        self::assertFalse($scope->allowsUncategorized('en'));
        self::assertFalse($scope->allows([], 'en'));
    }

    public function testAllowsLanguageAnswersForAnyCategory(): void
    {
        $scope = RightScope::ofGrants(new RightGrant([1], ['en']));

        self::assertTrue($scope->allowsLanguageInAnyCategory('en'));
        self::assertFalse($scope->allowsLanguageInAnyCategory('de'));
    }

    public function testAllowsCategoryInAnyLanguageAnswersForAnyLanguage(): void
    {
        $scope = RightScope::ofGrants(new RightGrant([1], ['en']));

        self::assertTrue($scope->allowsCategoryInAnyLanguage(1));
        self::assertFalse($scope->allowsCategoryInAnyLanguage(2));
        self::assertTrue(self::germanEverywherePlusCategoryOne()->allowsCategoryInAnyLanguage(2));
        self::assertFalse(RightScope::none()->allowsCategoryInAnyLanguage(1));
    }

    public function testCategoryIdsInAnyLanguageCollectEveryGrant(): void
    {
        self::assertSame([], RightScope::none()->categoryIdsInAnyLanguage());
        self::assertNull(RightScope::unrestricted()->categoryIdsInAnyLanguage());
        self::assertNull(self::germanEverywherePlusCategoryOne()->categoryIdsInAnyLanguage());
        self::assertSame(
            [1, 2, 3],
            RightScope::ofGrants(new RightGrant([1, 2], ['de']), new RightGrant([2, 3]))->categoryIdsInAnyLanguage(),
        );
    }

    public function testLanguagesInAnyCategoryCollectEveryGrant(): void
    {
        self::assertSame([], RightScope::none()->languagesInAnyCategory());
        self::assertNull(RightScope::unrestricted()->languagesInAnyCategory());
        self::assertNull(self::germanEverywherePlusCategoryOne()->languagesInAnyCategory());
        self::assertSame(
            ['de', 'en'],
            RightScope::ofGrants(new RightGrant([1], ['de']), new RightGrant(languages: ['de', 'en']))
                ->languagesInAnyCategory(),
        );
    }

    public function testCategoryIdsAreResolvedPerLanguage(): void
    {
        $scope = RightScope::ofGrants(
            new RightGrant(languages: ['de']),
            new RightGrant([1]),
            new RightGrant([2, 3], ['en']),
        );

        self::assertNull($scope->categoryIdsIn('de'));
        self::assertSame([1, 2, 3], $scope->categoryIdsIn('en'));
        self::assertSame([1], $scope->categoryIdsIn('fr'));
    }

    public function testLanguagesInEveryCategoryIgnoreCategoryRestrictedGrants(): void
    {
        self::assertSame(['de'], self::germanEverywherePlusCategoryOne()->languagesInEveryCategory());
        self::assertSame([], RightScope::ofGrants(new RightGrant([1]))->languagesInEveryCategory());
        self::assertNull(
            RightScope::ofGrants(new RightGrant([1]), new RightGrant())->languagesInEveryCategory(),
        );
    }

    public function testSeparateGrantsDoNotCoverAnUnrestrictedGrant(): void
    {
        $scope = self::germanEverywherePlusCategoryOne();

        self::assertFalse($scope->covers(null, null));
        self::assertFalse($scope->covers([2], ['en']));
        self::assertFalse($scope->covers([1, 2], null));
        self::assertFalse($scope->covers(null, ['de', 'en']));
    }

    public function testCoversWhatASingleGrantHolds(): void
    {
        $scope = self::germanEverywherePlusCategoryOne();

        self::assertTrue($scope->covers(null, ['de']));
        self::assertTrue($scope->covers([1], null));
        self::assertTrue($scope->covers([1], ['en', 'fr']));
        self::assertTrue($scope->covers([2, 3], ['de']));
    }

    public function testCoversACombinationSpreadOverSeveralGrants(): void
    {
        $scope = self::germanEverywherePlusCategoryOne();

        // Category 1 in English comes from the group, category 1 in German from either grant.
        self::assertTrue($scope->covers([1], ['de', 'en']));
        self::assertFalse($scope->covers([1, 2], ['de', 'en']));
    }

    public function testEmptyRestrictionListCoversNothing(): void
    {
        self::assertFalse(RightScope::unrestricted()->covers([], null));
        self::assertFalse(RightScope::unrestricted()->covers(null, []));
    }

    public function testCoversScopeComparesGrantByGrant(): void
    {
        $scope = self::germanEverywherePlusCategoryOne();

        self::assertTrue($scope->coversScope(RightScope::none()));
        self::assertTrue($scope->coversScope($scope));
        self::assertTrue($scope->coversScope(RightScope::ofGrants(new RightGrant([1], ['en']))));
        self::assertFalse($scope->coversScope(RightScope::ofGrants(new RightGrant([2], ['en']))));
        self::assertFalse($scope->coversScope(RightScope::unrestricted()));
        self::assertTrue(RightScope::unrestricted()->coversScope($scope));
    }
}
