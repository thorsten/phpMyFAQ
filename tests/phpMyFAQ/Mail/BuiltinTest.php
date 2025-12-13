<?php

namespace phpMyFAQ\Mail;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[AllowMockObjectsWithoutExpectations]
class BuiltinTest extends TestCase
{
    private Builtin $builtin;

    protected function setUp(): void
    {
        $this->builtin = new Builtin();
    }

    public function testImplementsMailUserAgentInterface(): void
    {
        $this->assertInstanceOf(MailUserAgentInterface::class, $this->builtin);
    }

    public function testSendMethodExists(): void
    {
        $this->assertTrue(method_exists($this->builtin, 'send'));
    }

    /**
     * @throws \ReflectionException
     */
    public function testSendMethodSignature(): void
    {
        $reflection = new ReflectionMethod($this->builtin, 'send');

        $this->assertEquals('send', $reflection->getName());
        $this->assertEquals(3, $reflection->getNumberOfParameters());
        $this->assertEquals('int', $reflection->getReturnType()?->getName());
    }
}
