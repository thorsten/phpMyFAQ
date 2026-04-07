<?php

declare(strict_types=1);

namespace phpMyFAQ\Session;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[CoversClass(AbstractSession::class)]
class AbstractSessionTest extends TestCase
{
    private Session $sessionMock;
    private AbstractSession $abstractSession;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(Session::class);
        $this->abstractSession = new AbstractSession($this->sessionMock);
    }

    public function testGetDelegatesToSession(): void
    {
        $this->sessionMock
            ->expects($this->once())
            ->method('get')
            ->with('test-key')
            ->willReturn('test-value');

        static::assertSame('test-value', $this->abstractSession->get('test-key'));
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $this->sessionMock
            ->expects($this->once())
            ->method('get')
            ->with('missing')
            ->willReturn(null);

        static::assertNull($this->abstractSession->get('missing'));
    }

    public function testSetDelegatesToSession(): void
    {
        $this->sessionMock
            ->expects($this->once())
            ->method('set')
            ->with('key', 'value');

        $this->abstractSession->set('key', 'value');
    }

    public function testSetAcceptsVariousTypes(): void
    {
        $this->sessionMock
            ->expects($this->exactly(3))
            ->method('set');

        $this->abstractSession->set('int', 42);
        $this->abstractSession->set('array', ['a', 'b']);
        $this->abstractSession->set('bool', true);
    }
}
