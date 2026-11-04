<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\News;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class NewsControllerTest extends TestCase
{
    /**
     * Security regression (GHSA-j4rc-96xj-gvqc follow-up): the public news API must not leak
     * the author's e-mail address.
     */
    public function testListDoesNotExposeAuthorEmail(): void
    {
        $news = $this->createMock(News::class);
        $news->method('getLatestData')->willReturn([
            [
                'id' => 1,
                'lang' => 'en',
                'header' => 'Hello, World!',
                'content' => 'Hello, phpMyFAQ!',
                'authorName' => 'Jane Doe',
                'authorEmail' => 'jane.doe@corp.example',
            ],
        ]);

        $reflection = new ReflectionClass(NewsController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $parent = $reflection->getParentClass();
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static fn(string $id) => match ($id) {
                'phpmyfaq.news' => $news,
                default => null,
            });
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('configuration')->setValue($controller, $this->createMock(Configuration::class));

        $response = $controller->list();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayNotHasKey('authorEmail', $payload[0]);
        $this->assertSame('Jane Doe', $payload[0]['authorName']);
        $this->assertStringNotContainsString('jane.doe@corp.example', $response->getContent());
    }
}
