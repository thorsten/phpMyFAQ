<?php

declare(strict_types=1);

namespace phpMyFAQ\Translation\Exception;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TranslationException::class)]
#[CoversClass(ApiException::class)]
#[CoversClass(RateLimitException::class)]
#[CoversClass(UnsupportedLanguageException::class)]
final class TranslationExceptionsTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<TranslationException>}>
     */
    public static function specificExceptionProvider(): iterable
    {
        yield 'api' => [ApiException::class];
        yield 'rate limit' => [RateLimitException::class];
        yield 'unsupported language' => [UnsupportedLanguageException::class];
    }

    public function testBaseExceptionIsAPlainException(): void
    {
        $exception = new TranslationException('Translation failed', 7);

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertSame('Translation failed', $exception->getMessage());
        $this->assertSame(7, $exception->getCode());
    }

    #[DataProvider('specificExceptionProvider')]
    public function testSpecificExceptionsCanBeCaughtAsTranslationException(string $class): void
    {
        $exception = new $class('details');

        $this->assertInstanceOf(TranslationException::class, $exception);
        $this->assertSame('details', $exception->getMessage());
    }

    public function testSpecificExceptionsAreDistinguishable(): void
    {
        $this->assertNotInstanceOf(RateLimitException::class, new ApiException());
        $this->assertNotInstanceOf(ApiException::class, new RateLimitException());
        $this->assertNotInstanceOf(UnsupportedLanguageException::class, new ApiException());
    }
}
