<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
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
        $this->assertSame($expected, json_decode($response->getContent(), true));
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
