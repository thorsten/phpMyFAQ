<?php

declare(strict_types=1);

namespace phpMyFAQ\Permission;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RightGrant::class)]
final class RightGrantTest extends TestCase
{
    public function testGrantWithoutRestrictionsCoversEverything(): void
    {
        $grant = new RightGrant();

        self::assertTrue($grant->isUnrestricted());
        self::assertTrue($grant->coversCategory(7));
        self::assertTrue($grant->coversLanguage('fr'));
    }

    public function testRestrictedGrantCoversOnlyItsCategoriesAndLanguages(): void
    {
        $grant = new RightGrant([1, 2], ['de']);

        self::assertFalse($grant->isUnrestricted());
        self::assertTrue($grant->coversCategory(2));
        self::assertFalse($grant->coversCategory(3));
        self::assertTrue($grant->coversLanguage('de'));
        self::assertFalse($grant->coversLanguage('en'));
    }

    public function testCategoryIdsAreNormalizedToUniqueIntegers(): void
    {
        $grant = new RightGrant(['3', 3, 4], ['de', 'de']);

        self::assertSame([3, 4], $grant->categoryIds);
        self::assertSame(['de'], $grant->languages);
    }

    public function testEmptyCategoryListIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RightGrant([], null);
    }

    public function testEmptyLanguageListIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RightGrant(null, []);
    }

    public function testStoredEmptyRestrictionsMeanUnrestricted(): void
    {
        self::assertTrue(RightGrant::fromRestrictions([], [])->isUnrestricted());

        $grant = RightGrant::fromRestrictions([5], []);

        self::assertSame([5], $grant->categoryIds);
        self::assertNull($grant->languages);
    }
}
