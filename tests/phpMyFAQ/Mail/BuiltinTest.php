<?php

namespace phpMyFAQ\Mail;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class BuiltinTestState
{
    public static array $mailCalls = [];
    public static bool|string $safeMode = false;
    public static bool $mailReturnValue = true;

    public static function reset(): void
    {
        self::$mailCalls = [];
        self::$safeMode = false;
        self::$mailReturnValue = true;
    }
}

function ini_get(string $option): bool|string
{
    if ($option === 'safe_mode') {
        return BuiltinTestState::$safeMode;
    }

    return \ini_get($option);
}

function mail(
    string $recipients,
    string $subject,
    string $body,
    array|string $additionalHeaders = [],
    ?string $additionalParams = null,
): bool {
    BuiltinTestState::$mailCalls[] = [
        'recipients' => $recipients,
        'subject' => $subject,
        'body' => $body,
        'headers' => $additionalHeaders,
        'params' => $additionalParams,
    ];

    return BuiltinTestState::$mailReturnValue;
}

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(Builtin::class)]
class BuiltinTest extends TestCase
{
    private Builtin $builtin;

    protected function setUp(): void
    {
        parent::setUp();
        BuiltinTestState::reset();
        $this->builtin = new Builtin();
    }

    protected function tearDown(): void
    {
        BuiltinTestState::reset();
        parent::tearDown();
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

    public function testSendUsesBuiltinMailWithoutSenderWhenReturnPathIsMissing(): void
    {
        $result = $this->builtin->send(
            'user@example.com',
            [
                'Subject' => 'Test subject',
                'From' => 'sender@example.com',
                'X-Test' => 'header',
            ],
            'Mail body',
        );

        $this->assertSame(1, $result);
        $this->assertCount(1, BuiltinTestState::$mailCalls);
        $this->assertSame('user@example.com', BuiltinTestState::$mailCalls[0]['recipients']);
        $this->assertSame('Test subject', BuiltinTestState::$mailCalls[0]['subject']);
        $this->assertSame('Mail body', BuiltinTestState::$mailCalls[0]['body']);
        $this->assertSame("From: sender@example.com\nX-Test: header\n", BuiltinTestState::$mailCalls[0]['headers']);
        $this->assertNull(BuiltinTestState::$mailCalls[0]['params']);
    }

    public function testSendUsesReturnPathAsSenderParameterWhenAvailable(): void
    {
        $result = $this->builtin->send(
            'user@example.com',
            [
                'Subject' => 'Queued subject',
                'Return-Path' => '<bounce@example.com>',
                'From' => 'sender@example.com',
            ],
            'Mail body',
        );

        $this->assertSame(1, $result);
        $this->assertCount(1, BuiltinTestState::$mailCalls);
        $this->assertSame("From: sender@example.com\n", BuiltinTestState::$mailCalls[0]['headers']);
        $this->assertSame('-fbounce@example.com', BuiltinTestState::$mailCalls[0]['params']);
    }

    public function testSendSkipsSenderParameterWhenSafeModeIsEnabled(): void
    {
        BuiltinTestState::$safeMode = '1';
        BuiltinTestState::$mailReturnValue = false;

        $result = $this->builtin->send(
            'user@example.com',
            [
                'Subject' => 'Safe mode subject',
                'Return-Path' => '<bounce@example.com>',
            ],
            'Mail body',
        );

        $this->assertSame(0, $result);
        $this->assertCount(1, BuiltinTestState::$mailCalls);
        $this->assertSame("Return-Path: <bounce@example.com>\n", BuiltinTestState::$mailCalls[0]['headers']);
        $this->assertNull(BuiltinTestState::$mailCalls[0]['params']);
    }
}
