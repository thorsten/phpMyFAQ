<?php

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Mail;
use phpMyFAQ\Queue\DatabaseMessageBus;
use phpMyFAQ\Queue\Message\SendMailMessage;
use phpMyFAQ\Queue\Transport\DatabaseTransport;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\Utils;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(MailHelper::class)]
#[UsesClass(Mail::class)]
#[UsesClass(DatabaseMessageBus::class)]
#[UsesClass(SendMailMessage::class)]
#[UsesClass(Translation::class)]
#[UsesClass(Utils::class)]
final class MailHelperTest extends TestCase
{
    public function testSendMailToNewUserQueuesWelcomeMail(): void
    {
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $transport = $this->createMock(DatabaseTransport::class);
        $transport
            ->expects($this->once())
            ->method('enqueue')
            ->with(
                $this->callback(static function (string $payload): bool {
                    $decoded = json_decode($payload, true);

                    return is_array($decoded)
                        && ($decoded['class'] ?? null) === SendMailMessage::class
                        && ($decoded['payload']['recipient'] ?? null) === 'new.user@example.com';
                }),
                $this->isArray(),
                'mail',
            )
            ->willReturn(1);
        $messageBus = new DatabaseMessageBus($transport);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('phpmyfaq.queue.message-bus')->willReturn(true);
        $container->method('get')->with('phpmyfaq.queue.message-bus')->willReturn($messageBus);

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
        $configuration->method('getDefaultUrl')->willReturn('https://localhost/');
        $configuration->method('getLogger')->willReturn($this->createStub(Logger::class));

        $user = $this->createMock(User::class);
        $user->method('getLogin')->willReturn('new-user');
        $user
            ->method('getUserData')
            ->willReturnCallback(static function (string $key): string {
                return match ($key) {
                    'display_name' => 'New User',
                    'email' => 'new.user@example.com',
                    default => '',
                };
            });

        $helper = new MailHelper($configuration);

        $result = $helper->sendMailToNewUser($user, 'secret-password');

        $this->assertTrue($result);

        $mail = $this->readProperty($helper, 'mail');
        $this->assertInstanceOf(Mail::class, $mail);
        $this->assertStringContainsString('New User', $mail->message);
        $this->assertStringContainsString('new-user', $mail->message);
        $this->assertStringContainsString('secret-password', $mail->message);
        $this->assertStringContainsString('https://localhost/', $mail->message);
        $this->assertSame('[phpMyFAQ] Registration: new user', $mail->subject);
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflectionProperty = new ReflectionProperty($object, $property);

        return $reflectionProperty->getValue($object);
    }
}
