<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[CoversClass(ForbiddenException::class)]
final class ForbiddenExceptionTest extends TestCase
{
    public function testDefaultsToAForbiddenHttpException(): void
    {
        $exception = new ForbiddenException();

        $this->assertInstanceOf(HttpExceptionInterface::class, $exception);
        $this->assertSame(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
        $this->assertSame('Forbidden', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame([], $exception->getHeaders());
        $this->assertNull($exception->getPrevious());
    }

    public function testCarriesMessagePreviousCodeAndHeaders(): void
    {
        $previous = new RuntimeException('cause');

        $exception = new ForbiddenException('No permission to edit FAQs', $previous, 42, ['X-Reason' => 'permission']);

        $this->assertSame(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
        $this->assertSame('No permission to edit FAQs', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame(['X-Reason' => 'permission'], $exception->getHeaders());
    }

    public function testStatusCodeCannotBeChangedByTheCaller(): void
    {
        $exception = new ForbiddenException(code: Response::HTTP_NOT_FOUND);

        $this->assertSame(Response::HTTP_FORBIDDEN, $exception->getStatusCode());
        $this->assertSame(Response::HTTP_NOT_FOUND, $exception->getCode());
    }
}
