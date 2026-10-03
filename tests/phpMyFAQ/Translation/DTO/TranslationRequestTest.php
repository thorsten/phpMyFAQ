<?php

declare(strict_types=1);

namespace phpMyFAQ\Translation\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TranslationRequest::class)]
final class TranslationRequestTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $fields = ['question' => 'What is phpMyFAQ?', 'answer' => '<p>An FAQ system.</p>'];

        $request = new TranslationRequest('faq', 'en', 'de', $fields);

        $this->assertSame('faq', $request->getContentType());
        $this->assertSame('en', $request->getSourceLang());
        $this->assertSame('de', $request->getTargetLang());
        $this->assertSame($fields, $request->getFields());
    }

    public function testFieldsMayBeEmpty(): void
    {
        $request = new TranslationRequest('category', 'de', 'fr', []);

        $this->assertSame([], $request->getFields());
    }

    public function testRequestIsImmutable(): void
    {
        $this->assertTrue(new \ReflectionClass(TranslationRequest::class)->isReadOnly());
    }
}
