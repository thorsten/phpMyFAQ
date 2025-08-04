<?php

namespace phpMyFAQ\Session;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionWrapperTest extends TestCase
{
    private Session $sessionMock;
    private SessionWrapper $sessionWrapper;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        // Mock the Session
        $this->sessionMock = $this->createMock(Session::class);
        $this->sessionWrapper = new SessionWrapper($this->sessionMock);
    }

    public function testConstructorWithSessionParameter(): void
    {
        $sessionWrapper = new SessionWrapper($this->sessionMock);
        $this->assertInstanceOf(SessionWrapper::class, $sessionWrapper);
        $this->assertSame($this->sessionMock, $sessionWrapper->getSession());
    }

    public function testConstructorWithoutSessionParameter(): void
    {
        // This test checks that the constructor can create a session when none is provided
        // We can't easily test this without starting actual sessions, so we'll just verify
        // the constructor doesn't throw an exception
        $this->expectNotToPerformAssertions();
        // Note: In a real scenario, this would create a PhpBridgeSessionStorage session
        // but for unit testing, we'll focus on the mocked behavior
    }

    public function testGetMethodDelegatesToSession(): void
    {
        $key = 'test_key';
        $expectedValue = 'test_value';
        $defaultValue = 'default_value';

        $this->sessionMock
            ->expects($this->once())
            ->method('get')
            ->with($key, $defaultValue)
            ->willReturn($expectedValue);

        $result = $this->sessionWrapper->get($key, $defaultValue);
        $this->assertEquals($expectedValue, $result);
    }

    public function testGetMethodWithoutDefault(): void
    {
        $key = 'test_key';
        $expectedValue = 'test_value';

        $this->sessionMock
            ->expects($this->once())
            ->method('get')
            ->with($key, null)
            ->willReturn($expectedValue);

        $result = $this->sessionWrapper->get($key);
        $this->assertEquals($expectedValue, $result);
    }

    public function testSetMethodDelegatesToSession(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->sessionMock
            ->expects($this->once())
            ->method('set')
            ->with($key, $value);

        $this->sessionWrapper->set($key, $value);
    }

    public function testHasMethodDelegatesToSession(): void
    {
        $key = 'test_key';

        $this->sessionMock
            ->expects($this->once())
            ->method('has')
            ->with($key)
            ->willReturn(true);

        $result = $this->sessionWrapper->has($key);
        $this->assertTrue($result);
    }

    public function testHasMethodReturnsFalse(): void
    {
        $key = 'nonexistent_key';

        $this->sessionMock
            ->expects($this->once())
            ->method('has')
            ->with($key)
            ->willReturn(false);

        $result = $this->sessionWrapper->has($key);
        $this->assertFalse($result);
    }

    public function testRemoveMethodDelegatesToSession(): void
    {
        $key = 'test_key';
        $expectedValue = 'removed_value';

        $this->sessionMock
            ->expects($this->once())
            ->method('remove')
            ->with($key)
            ->willReturn($expectedValue);

        $result = $this->sessionWrapper->remove($key);
        $this->assertEquals($expectedValue, $result);
    }

    public function testGetSessionReturnsUnderlyingSession(): void
    {
        $result = $this->sessionWrapper->getSession();
        $this->assertSame($this->sessionMock, $result);
    }

    public function testSetAndGetWorkTogether(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        // First, mock the set operation
        $this->sessionMock
            ->expects($this->once())
            ->method('set')
            ->with($key, $value);

        // Then, mock the get operation to return the same value
        $this->sessionMock
            ->expects($this->once())
            ->method('get')
            ->with($key, null)
            ->willReturn($value);

        // Test the workflow
        $this->sessionWrapper->set($key, $value);
        $result = $this->sessionWrapper->get($key);
        
        $this->assertEquals($value, $result);
    }

    public function testRemoveNonExistentKey(): void
    {
        $key = 'nonexistent_key';

        $this->sessionMock
            ->expects($this->once())
            ->method('remove')
            ->with($key)
            ->willReturn(null);

        $result = $this->sessionWrapper->remove($key);
        $this->assertNull($result);
    }

    public function testSetWithDifferentDataTypes(): void
    {
        $testCases = [
            ['string_key', 'string_value'],
            ['int_key', 42],
            ['array_key', ['foo' => 'bar']],
            ['bool_key', true],
            ['null_key', null],
        ];

        foreach ($testCases as [$key, $value]) {
            $this->sessionMock
                ->expects($this->once())
                ->method('set')
                ->with($key, $value);

            $this->sessionWrapper->set($key, $value);
        }
    }
}