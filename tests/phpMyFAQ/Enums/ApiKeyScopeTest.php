<?php

declare(strict_types=1);

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiKeyScope::class)]
final class ApiKeyScopeTest extends TestCase
{
    public function testCasesNotEmpty(): void
    {
        $this->assertNotEmpty(ApiKeyScope::cases());
    }

    public function testTryFromRoundtripsForAllCases(): void
    {
        foreach (ApiKeyScope::cases() as $case) {
            $this->assertSame($case, ApiKeyScope::tryFrom($case->value));
        }
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(static fn(ApiKeyScope $case): string => $case->value, ApiKeyScope::cases());

        $this->assertSame(array_values(array_unique($values)), $values);
    }

    public function testValuesFollowResourceDotActionNaming(): void
    {
        foreach (ApiKeyScope::cases() as $case) {
            $this->assertMatchesRegularExpression('/^[a-z]+\.(read|write)$/', $case->value);
        }
    }

    public function testUnknownScopeStringIsRejected(): void
    {
        $this->assertNull(ApiKeyScope::tryFrom('admin.everything'));
        $this->assertNull(ApiKeyScope::tryFrom('FAQ.READ'));
    }

    public function testEveryScopeRequiresAPermission(): void
    {
        foreach (ApiKeyScope::cases() as $case) {
            $this->assertInstanceOf(PermissionType::class, $case->requiredPermission());
        }
    }

    /**
     * @return iterable<string, array{ApiKeyScope, PermissionType}>
     */
    public static function requiredPermissionProvider(): iterable
    {
        yield 'faq.read' => [ApiKeyScope::FAQ_READ, PermissionType::FAQS_VIEW];
        yield 'faq.write' => [ApiKeyScope::FAQ_WRITE, PermissionType::FAQ_EDIT];
        yield 'category.read' => [ApiKeyScope::CATEGORY_READ, PermissionType::CATEGORIES_VIEW];
        yield 'category.write' => [ApiKeyScope::CATEGORY_WRITE, PermissionType::CATEGORY_EDIT];
        yield 'news.read' => [ApiKeyScope::NEWS_READ, PermissionType::NEWS_VIEW];
        yield 'news.write' => [ApiKeyScope::NEWS_WRITE, PermissionType::NEWS_EDIT];
        yield 'attachment.read' => [ApiKeyScope::ATTACHMENT_READ, PermissionType::ATTACHMENT_DOWNLOAD];
        yield 'comment.write' => [ApiKeyScope::COMMENT_WRITE, PermissionType::COMMENT_ADD];
        yield 'question.write' => [ApiKeyScope::QUESTION_WRITE, PermissionType::QUESTION_ADD];
        yield 'group.read' => [ApiKeyScope::GROUP_READ, PermissionType::GROUP_EDIT];
        yield 'user.read' => [ApiKeyScope::USER_READ, PermissionType::USER_EDIT];
    }

    #[DataProvider('requiredPermissionProvider')]
    public function testRequiredPermission(ApiKeyScope $scope, PermissionType $expected): void
    {
        $this->assertSame($expected, $scope->requiredPermission());
    }

    public function testRequiredPermissionProviderCoversEveryCase(): void
    {
        $covered = array_map(
            static fn(array $row): ApiKeyScope => $row[0],
            iterator_to_array(self::requiredPermissionProvider(), preserve_keys: false),
        );

        $this->assertEqualsCanonicalizing(ApiKeyScope::cases(), $covered);
    }
}
