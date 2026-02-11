<?php

namespace phpMyFAQ\Queue;

use phpMyFAQ\Configuration;
use phpMyFAQ\Queue\Transport\DatabaseTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(MessageBusFactory::class)]
#[UsesClass(DatabaseMessageBus::class)]
class MessageBusFactoryTest extends TestCase
{
    public function testCreateReturnsDatabaseMessageBusForDatabaseTransport(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')->with('queue.transport')->willReturn('database');

        $transport = $this->createStub(DatabaseTransport::class);
        $factory = new MessageBusFactory($configuration, $transport);

        $bus = $factory->create();

        $this->assertInstanceOf(DatabaseMessageBus::class, $bus);
    }

    public function testCreateThrowsForUnsupportedTransport(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')->with('queue.transport')->willReturn('sqs');

        $transport = $this->createStub(DatabaseTransport::class);
        $factory = new MessageBusFactory($configuration, $transport);

        $this->expectException(RuntimeException::class);
        $factory->create();
    }
}
