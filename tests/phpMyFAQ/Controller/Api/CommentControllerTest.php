<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Faq;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class CommentControllerTest extends TestCase
{
    private function buildController(Faq $faq, Comments $comments): CommentController
    {
        $controller = (new ReflectionClass(CommentController::class))->newInstanceWithoutConstructor();

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($faq, $comments) {
                return match ($id) {
                    'phpmyfaq.faq' => $faq,
                    'phpmyfaq.comments' => $comments,
                    default => null,
                };
            });

        $parent = (new ReflectionClass(CommentController::class))->getParentClass();
        // currentUser stays null -> resolves to the anonymous user (-1) in getCurrentUserGroupId().
        $parent->getProperty('container')->setValue($controller, $container);

        return $controller;
    }

    private function request(string $recordId): Request
    {
        return new Request([], [], ['recordId' => $recordId]);
    }

    /**
     * Lead security regression: an anonymous requester who cannot see the parent
     * FAQ must not receive its comments (usernames, e-mail addresses, bodies).
     */
    public function testListReturnsNotFoundAndDoesNotReadCommentsWhenFaqIsNotAccessible(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->method('setUser')->willReturnSelf();
        $faq->method('setGroups')->willReturnSelf();
        $faq->expects($this->once())->method('isFaqAccessibleForUser')->with(123)->willReturn(false);

        $comments = $this->createMock(Comments::class);
        $comments->expects($this->never())->method('getCommentsData');

        $controller = $this->buildController($faq, $comments);

        $response = $controller->list($this->request('123'));

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame([], json_decode($response->getContent(), true));
    }

    public function testListReturnsCommentsWhenFaqIsAccessible(): void
    {
        $expected = [
            [
                'id' => 88,
                'recordId' => 123,
                'type' => 'faq',
                'username' => 'phpMyFAQ User',
                'email' => 'user@example.org',
                'comment' => 'Foo! Bar?',
            ],
        ];

        $faq = $this->createMock(Faq::class);
        $faq->method('setUser')->willReturnSelf();
        $faq->method('setGroups')->willReturnSelf();
        $faq->method('isFaqAccessibleForUser')->with(123)->willReturn(true);

        $comments = $this->createMock(Comments::class);
        $comments->expects($this->once())->method('getCommentsData')->willReturn($expected);

        $controller = $this->buildController($faq, $comments);

        $response = $controller->list($this->request('123'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        unset($expected[0]['email']);
        $this->assertSame($expected, json_decode($response->getContent(), true));
    }

    /**
     * Security regression: the public endpoint must never disclose the commenter's
     * e-mail address, the frontend only ever renders it obfuscated.
     */
    public function testListDoesNotDiscloseCommenterEmailAddresses(): void
    {
        $comment = new Comment();
        $comment
            ->setId(9001)
            ->setRecordId(123)
            ->setType('faq')
            ->setUsername('Commenter One')
            ->setEmail('commenter-sentinel@lab.local')
            ->setComment('A comment')
            ->setDate('2026-11-17T20:00:00+01:00');

        $faq = $this->createMock(Faq::class);
        $faq->method('setUser')->willReturnSelf();
        $faq->method('setGroups')->willReturnSelf();
        $faq->method('isFaqAccessibleForUser')->with(123)->willReturn(true);

        $comments = $this->createMock(Comments::class);
        $comments->method('getCommentsData')->willReturn([$comment]);

        $controller = $this->buildController($faq, $comments);

        $response = $controller->list($this->request('123'));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $payload);
        $this->assertArrayNotHasKey('email', $payload[0]);
        $this->assertStringNotContainsString('commenter-sentinel@lab.local', $response->getContent());
        $this->assertSame('Commenter One', $payload[0]['username']);
        $this->assertSame('A comment', $payload[0]['comment']);
    }

    public function testListReturnsNotFoundWhenAccessibleFaqHasNoComments(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->method('setUser')->willReturnSelf();
        $faq->method('setGroups')->willReturnSelf();
        $faq->method('isFaqAccessibleForUser')->willReturn(true);

        $comments = $this->createMock(Comments::class);
        $comments->method('getCommentsData')->willReturn([]);

        $controller = $this->buildController($faq, $comments);

        $response = $controller->list($this->request('123'));

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
