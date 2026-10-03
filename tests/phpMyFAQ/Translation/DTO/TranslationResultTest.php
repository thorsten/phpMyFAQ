<?php

declare(strict_types=1);

namespace phpMyFAQ\Translation\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TranslationResult::class)]
final class TranslationResultTest extends TestCase
{
    public function testSuccessfulResultCarriesTranslatedFieldsAndNoError(): void
    {
        $fields = ['question' => 'Was ist phpMyFAQ?'];

        $result = new TranslationResult($fields, true);

        $this->assertTrue($result->isSuccess());
        $this->assertSame($fields, $result->getTranslatedFields());
        $this->assertNull($result->getError());
    }

    public function testFailedResultCarriesErrorMessage(): void
    {
        $result = new TranslationResult([], false, 'Rate limit exceeded');

        $this->assertFalse($result->isSuccess());
        $this->assertSame([], $result->getTranslatedFields());
        $this->assertSame('Rate limit exceeded', $result->getError());
    }

    public function testResultIsImmutable(): void
    {
        $this->assertTrue(new \ReflectionClass(TranslationResult::class)->isReadOnly());
    }
}
