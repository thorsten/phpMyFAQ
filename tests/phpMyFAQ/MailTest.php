<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Mail\Builtin;
use phpMyFAQ\Mail\Smtp;
use phpMyFAQ\Queue\DatabaseMessageBus;
use phpMyFAQ\Queue\Message\SendMailMessage;
use phpMyFAQ\Queue\Transport\DatabaseTransport;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class MailTest extends TestCase
{
    private Mail $mail;
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        Request::setTrustedHosts(['^.*$']); // Trust all hosts for testing

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $this->configuration = $configuration;

        $this->mail = new Mail($configuration);
    }

    public function testCreateBoundaryReturnsString(): void
    {
        $result = Mail::createBoundary();

        $this->assertIsString($result);
        $this->assertStringStartsWith('-----', $result);
        $this->assertSame(37, strlen($result));
        $this->assertMatchesRegularExpression('/^-----[a-f0-9]{32}$/', $result);
    }

    public function testGetServerNameWithNoHostHeaders(): void
    {
        unset($_SERVER['HTTP_HOST']);
        unset($_SERVER['SERVER_NAME']);

        $result = Mail::getServerName();

        $this->assertSame('localhost.localdomain', $result);
    }

    /**
     * @throws Exception
     */
    public function testSetFromWithValidAddress(): void
    {
        $result = $this->mail->setFrom('example@example.com', 'John Doe');
        $this->assertTrue($result);
    }

    public function testSetFromWithInvalidAddress(): void
    {
        $this->assertFalse($this->mail->setFrom('invalid-email'));
    }

    public function testValidateEmailWithValidAddress(): void
    {
        $result = Mail::validateEmail('example@example.com');
        $this->assertTrue($result);
    }

    public function testValidateEmailWithInvalidAddress(): void
    {
        $result = Mail::validateEmail('invalid-email');
        $this->assertFalse($result);
    }

    public function testValidateEmailWithEmptyAddress(): void
    {
        $result = Mail::validateEmail('');
        $this->assertFalse($result);
    }

    public function testValidateEmailWithZeroAddress(): void
    {
        $result = Mail::validateEmail('0');
        $this->assertFalse($result);
    }

    public function testValidateEmailWithUnsafeCharacters(): void
    {
        $result = Mail::validateEmail("example@\r\nexample.com");
        $this->assertFalse($result);
    }

    /**
     * @throws Exception
     */
    public function testAddCcWithValidAddress(): void
    {
        $result = $this->mail->addCc('example@example.com', 'John Doe');
        $this->assertTrue($result);
    }

    public function testAddCcWithInvalidAddress(): void
    {
        $this->assertFalse($this->mail->addCc('invalid-email'));
    }

    /**
     * @throws Exception
     */
    public function testAddToWithValidAddress(): void
    {
        $result = $this->mail->addTo('example@example.com', 'John Doe');
        $this->assertTrue($result);
    }

    public function testAddToWithInvalidAddress(): void
    {
        $this->assertFalse($this->mail->addTo('invalid-email'));
    }

    public function testAddToRejectsDuplicateAddress(): void
    {
        $logger = $this->createMock(\Monolog\Logger::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('already added in To'));

        $configuration = $this->createConfiguredMock(Configuration::class, [
            'get' => false,
            'getVersion' => '4.2.0-alpha',
            'getAdminEmail' => 'admin@example.com',
            'getTitle' => 'phpMyFAQ',
            'getLogger' => $logger,
        ]);

        $mail = new Mail($configuration);

        $this->assertTrue($mail->addTo('duplicate@example.com', 'Jane Doe'));
        $this->assertFalse($mail->addTo('duplicate@example.com', 'Jane Doe'));
    }

    public function testGetDateWithValidTimestamp(): void
    {
        $timestamp = strtotime('2023-01-01 12:00:00');
        $result = Mail::getDate($timestamp);

        $this->assertEquals(date(format: 'r', timestamp: $timestamp), $result);
    }

    public function testGetTimeWithRequestTimeSet(): void
    {
        $_SERVER['REQUEST_TIME'] = strtotime('2023-01-01 12:00:00');
        $result = Mail::getTime();

        $this->assertEquals($_SERVER['REQUEST_TIME'], $result);
    }

    public function testGetTimeWithNoRequestTime(): void
    {
        $requestTimeToRestore = $_SERVER['REQUEST_TIME'] ?? null;
        unset($_SERVER['REQUEST_TIME']);
        $result = Mail::getTime();

        $this->assertLessThanOrEqual(time(), $result);
        $this->assertGreaterThanOrEqual(time() - 1, $result); // Allow for up to 1 second of difference

        $_SERVER['REQUEST_TIME'] = $requestTimeToRestore;
    }

    public function testWrapLinesWithDefaultWidth(): void
    {
        $message = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum acnunc quis neque tempor varius.';
        $result = $this->mail->wrapLines($message);

        $expectedResult = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum\r\nacnunc quis neque tempor varius.";
        $this->assertSame($expectedResult, $result);
    }

    public function testWrapLinesWithCustomWidth(): void
    {
        $message = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum ac nunc quis neque tempor varius.';
        $result = $this->mail->wrapLines($message, 30);

        $expectedResult = "Lorem ipsum dolor sit amet,\r\nconsectetur adipiscing elit.\r\nVestibulum ac nunc quis neque\r\ntempor varius.";
        $this->assertSame($expectedResult, $result);
    }

    public function testFixEOL(): void
    {
        $text = "Line 1\r\nLine 2\rLine 3\nLine 4\r\n";
        $result = $this->mail->fixEOL($text);

        $expectedResult = "Line 1\r\nLine 2\r\nLine 3\r\nLine 4\r\n";
        $this->assertSame($expectedResult, $result);
    }

    public function testGetMUAWithBuiltin(): void
    {
        $result = Mail::getMUA('builtin');
        $this->assertInstanceOf(Builtin::class, $result);
    }

    public function testGetMUAWithSMTP(): void
    {
        $result = Mail::getMUA('smtp');
        $this->assertInstanceOf(Smtp::class, $result);
    }

    public function testConstructorUsesSmtpAgentWhenRemoteSmtpIsEnabled(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static fn(string $item): mixed => match ($item) {
                'mail.remoteSMTP' => true,
                default => null,
            });
        $configuration->method('getVersion')->willReturn('4.2.0-alpha');
        $configuration->method('getAdminEmail')->willReturn('invalid-email');
        $configuration->method('getTitle')->willReturn('phpMyFAQ');
        $configuration->method('getLogger')->willReturn($this->createStub(\Monolog\Logger::class));

        $mail = new Mail($configuration);

        $this->assertSame('smtp', $mail->agent);
    }

    /**
     * @throws Exception
     */
    public function testSetReplyToWithValidAddress(): void
    {
        $result = $this->mail->setReplyTo('example@example.com', 'John Doe');
        $this->assertTrue($result);
    }

    public function testSetReplyToWithInvalidAddress(): void
    {
        $this->assertFalse($this->mail->setReplyTo('invalid-email'));
    }

    public function testSendThrowsWhenNoRecipientsAreConfigured(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You need at least to set one recipient among TO, CC and BCC!');

        $mail = $this->createCapturingMail($this->configuration, 7);
        $mail->message = 'No recipients here';
        $mail->send();
    }

    /**
     * @throws Exception
     */
    public function testSendUsesUndisclosedRecipientWhenOnlyBccIsPresent(): void
    {
        $mail = $this->createCapturingMail($this->configuration, 3);
        $mail->subject = 'CC only subject';
        $mail->message = 'CC only body';
        $mail->setFrom('admin@example.com', 'Admin User');

        $bccProperty = new \ReflectionProperty(Mail::class, 'bcc');
        $bccProperty->setValue($mail, ['bcc@example.com' => 'Blind Copy']);

        $this->assertSame(3, $mail->send(true));
        $this->assertSame('<Undisclosed-Recipient:;>', $mail->capturedRecipients);
        $this->assertStringContainsString('Blind Copy', (string) $mail->capturedHeaders['BCC']);
        $this->assertStringContainsString('CC only body', $mail->capturedBody);
    }

    /**
     * @throws Exception
     */
    public function testSendBuildsMultipartBodyWithAlternativeAndAttachment(): void
    {
        $attachmentPath = tempnam(sys_get_temp_dir(), 'phpmyfaq-mail-attachment-');
        file_put_contents($attachmentPath, 'attachment-content');

        $mail = $this->createCapturingMail($this->configuration, 9);
        $mail->subject = 'Multipart subject';
        $mail->message = '<p>Main HTML body</p>';
        $mail->messageAlt = 'Plain text body';
        $mail->setFrom('admin@example.com', 'Admin User');
        $mail->attachments[] = [
            'name' => 'example.txt',
            'path' => $attachmentPath,
            'mimetype' => 'text/plain',
            'disposition' => 'attachment',
            'cid' => null,
        ];
        $mail->addTo('user@example.com', 'User Name');

        try {
            $this->assertSame(9, $mail->send(true));
            $this->assertStringContainsString('multipart/mixed', (string) $mail->capturedHeaders['Content-Type']);
            $this->assertStringContainsString('multipart/alternative', $mail->capturedBody);
            $this->assertStringContainsString('Plain text body', $mail->capturedBody);
            $this->assertStringContainsString('<p>Main HTML body</p>', $mail->capturedBody);
            $this->assertStringContainsString('filename="example.txt"', $mail->capturedBody);
        } finally {
            @unlink($attachmentPath);
        }
    }

    /**
     * @throws Exception
     */
    public function testSendFallsBackToSynchronousDeliveryWhenQueueContainerIsInvalid(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static function (string $item): mixed {
                return match ($item) {
                    'mail.remoteSMTP' => false,
                    'mail.useQueue' => true,
                    'core.container' => 'not-a-container',
                    default => null,
                };
            });
        $configuration->method('getVersion')->willReturn('4.2.0-alpha');
        $configuration->method('getAdminEmail')->willReturn('admin@example.com');
        $configuration->method('getTitle')->willReturn('phpMyFAQ');
        $configuration->method('getLogger')->willReturn($this->createStub(\Monolog\Logger::class));

        $mail = $this->createCapturingMail($configuration, 5);
        $mail->subject = 'Fallback subject';
        $mail->message = 'Fallback message';
        $mail->addTo('user@example.com');

        $this->assertSame(5, $mail->send());
    }

    public function testSafeEmailWithSafeEmailEnabled(): void
    {
        $configurationMock = $this->createStub(Configuration::class);
        $configurationMock->method('get')->willReturn(true);

        $instance = new Mail($configurationMock);

        $result = $instance->safeEmail('test@example.com');
        $this->assertSame('test_AT_example_DOT_com', $result);
    }

    public function testSafeEmailWithSafeEmailDisabled(): void
    {
        $configurationMock = $this->createStub(Configuration::class);
        $configurationMock->method('get')->willReturn(false);

        $instance = new Mail($configurationMock);

        $result = $instance->safeEmail('test@example.com');
        $this->assertSame('test@example.com', $result);
    }

    public function testPrivateSetEmailToThrowsWhenMoreThanTwoAddressesAlreadyExist(): void
    {
        $target = [
            'first@example.com' => null,
            'second@example.com' => null,
            'third@example.com' => null,
        ];

        $method = new \ReflectionMethod(Mail::class, 'setEmailTo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Too many e-mail addresses, first@example.com, have been already added as 'From'!");

        $method->invokeArgs($this->mail, [&$target, 'From', 'new@example.com', 'New User']);
    }

    public function testPrivateCreateProviderThrowsForUnsupportedProvider(): void
    {
        $method = new \ReflectionMethod(Mail::class, 'createProvider');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unsupported mail provider: invalid');

        $method->invoke($this->mail, 'invalid');
    }

    public function testGetMuaThrowsErrorForUnknownAgentClass(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Class "phpMyFAQ\\Mail\\Invalidagent" not found');

        Mail::getMUA('invalid-agent');
    }

    /**
     * @throws Exception
     */
    public function testSendQueuesMessageWhenQueueBusIsAvailable(): void
    {
        $logger = $this->createStub(\Monolog\Logger::class);

        $transport = $this->createMock(DatabaseTransport::class);
        $transport
            ->expects($this->once())
            ->method('enqueue')
            ->with(
                $this->callback(static function (string $payload): bool {
                    $decoded = json_decode($payload, true);

                    return (
                        is_array($decoded)
                        && ($decoded['class'] ?? null)
                        === SendMailMessage::class
                        && isset($decoded['payload']['metadata']['envelope'])
                    );
                }),
                $this->isArray(),
                'mail',
            )
            ->willReturn(1);
        $messageBus = new DatabaseMessageBus($transport);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('has')->with('phpmyfaq.queue.message-bus')->willReturn(true);
        $container->expects($this->once())->method('get')->with('phpmyfaq.queue.message-bus')->willReturn($messageBus);

        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static function (string $item) use ($container): mixed {
                return match ($item) {
                    'mail.remoteSMTP' => false,
                    'mail.useQueue' => true,
                    'core.container' => $container,
                    default => null,
                };
            });
        $configuration->method('getVersion')->willReturn('4.2.0-alpha');
        $configuration->method('getAdminEmail')->willReturn('admin@example.com');
        $configuration->method('getTitle')->willReturn('phpMyFAQ');
        $configuration->method('getMailProvider')->willReturn('smtp');
        $configuration->method('getLogger')->willReturn($logger);

        $mail = new Mail($configuration);
        $mail->addTo('user@example.com');
        $mail->subject = 'Queued subject';
        $mail->message = 'Queued message';

        $this->assertSame(1, $mail->send());
    }

    /**
     * @throws Exception
     */
    public function testSendCanBypassQueueWhenDisabled(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('has');
        $container->expects($this->never())->method('get');

        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static function (string $item) use ($container): mixed {
                return match ($item) {
                    'mail.remoteSMTP' => false,
                    'mail.useQueue' => false,
                    'core.container' => $container,
                    default => null,
                };
            });
        $configuration->method('getVersion')->willReturn('4.2.0-alpha');
        $configuration->method('getAdminEmail')->willReturn('admin@example.com');
        $configuration->method('getTitle')->willReturn('phpMyFAQ');
        $configuration->method('getMailProvider')->willReturn('smtp');
        $configuration->method('getLogger')->willReturn($this->createStub(\Monolog\Logger::class));

        $mail = new class($configuration) extends Mail {
            public function sendPreparedEnvelope(string $recipients, array $headers, string $body): int
            {
                return 7;
            }
        };

        $mail->addTo('user@example.com');
        $mail->subject = 'Direct subject';
        $mail->message = 'Direct message';

        $this->assertSame(7, $mail->send());
    }

    private function createCapturingMail(Configuration $configuration, int $returnValue): Mail
    {
        return new class($configuration, $returnValue) extends Mail {
            public string $capturedRecipients = '';
            /** @var array<string, string|int> */
            public array $capturedHeaders = [];
            public string $capturedBody = '';

            public function __construct(Configuration $configuration, private readonly int $returnValue)
            {
                parent::__construct($configuration);
            }

            public function sendPreparedEnvelope(string $recipients, array $headers, string $body): int
            {
                $this->capturedRecipients = $recipients;
                $this->capturedHeaders = $headers;
                $this->capturedBody = $body;

                return $this->returnValue;
            }
        };
    }
}
