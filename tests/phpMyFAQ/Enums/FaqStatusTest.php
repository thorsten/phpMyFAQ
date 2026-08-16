<?php

declare(strict_types=1);

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FaqStatusTest extends TestCase
{
    public function testBackedValuesMatchTheDatabaseRepresentation(): void
    {
        $this->assertSame('draft', FaqStatus::Draft->value);
        $this->assertSame('review', FaqStatus::Review->value);
        $this->assertSame('published', FaqStatus::Published->value);
    }

    /**
     * @return array<string, array{FaqStatus, FaqStatus, bool}>
     */
    public static function transitionProvider(): array
    {
        return [
            'draft to review stays editorial' => [FaqStatus::Draft, FaqStatus::Review, false],
            'review back to draft stays editorial' => [FaqStatus::Review, FaqStatus::Draft, false],
            'review to published needs publish right' => [FaqStatus::Review, FaqStatus::Published, true],
            'one-step publish needs publish right' => [FaqStatus::Draft, FaqStatus::Published, true],
            'unpublish to draft needs publish right' => [FaqStatus::Published, FaqStatus::Draft, true],
            'unpublish to review needs publish right' => [FaqStatus::Published, FaqStatus::Review, true],
        ];
    }

    #[DataProvider('transitionProvider')]
    public function testTransitionsToOrFromPublishedRequireThePublishRight(
        FaqStatus $from,
        FaqStatus $to,
        bool $expected,
    ): void {
        $this->assertSame($expected, $from->transitionRequiresPublishRight($to));
    }
}
