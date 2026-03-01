<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Date;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Mail;
use phpMyFAQ\Strings;
use phpMyFAQ\Utils;
use phpMyFAQ\Service\Gravatar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(FaqController::class)]
#[UsesClass(Comment::class)]
#[UsesClass(Strings::class)]
#[UsesClass(Utils::class)]
final class FaqControllerTest extends TestCase
{
    public function testPrepareCommentsDataFormatsCommentPayload(): void
    {
        $controller = $this->createControllerWithoutConstructor();

        $date = $this->createMock(Date::class);
        $date->expects($this->once())
            ->method('format')
            ->with('2026-03-01 12:00:00')
            ->willReturn('Mar 1, 2026');

        $mail = $this->createMock(Mail::class);
        $mail->expects($this->once())
            ->method('safeEmail')
            ->with('john@example.com')
            ->willReturn('john [at] example.com');

        $gravatar = $this->createMock(Gravatar::class);
        $gravatar->expects($this->once())
            ->method('getImage')
            ->with('john@example.com', ['class' => 'img-thumbnail'])
            ->willReturn('<img src="avatar.jpg">');

        $this->setProperty($controller, 'date', $date);
        $this->setProperty($controller, 'mail', $mail);
        $this->setProperty($controller, 'gravatar', $gravatar);

        $comment = (new Comment())
            ->setId(42)
            ->setUsername('John Doe')
            ->setEmail('john@example.com')
            ->setDate('2026-03-01 12:00:00')
            ->setComment('Visit https://example.com');

        $result = $this->invokePrepareCommentsData($controller, [$comment]);

        self::assertSame(42, $result['comments'][0]['id']);
        self::assertSame('John Doe', $result['comments'][0]['username']);
        self::assertSame('Visit example.com', $result['comments'][0]['comment']);
        self::assertSame('<img src="avatar.jpg">', $result['gravatarImages'][42]);
        self::assertSame('john [at] example.com', $result['safeEmails'][42]);
        self::assertSame('Mar 1, 2026', $result['formattedDates'][42]);
    }

    private function createControllerWithoutConstructor(): FaqController
    {
        return (new ReflectionClass(FaqController::class))->newInstanceWithoutConstructor();
    }

    private function invokePrepareCommentsData(FaqController $controller, array $comments): array
    {
        $method = new \ReflectionMethod(FaqController::class, 'prepareCommentsData');

        /** @var array<string, array<int|string, mixed>> $result */
        $result = $method->invoke($controller, $comments);

        return $result;
    }

    private function setProperty(object $object, string $property, mixed $value): void
    {
        $reflectionProperty = new \ReflectionProperty($object, $property);
        $reflectionProperty->setValue($object, $value);
    }
}
