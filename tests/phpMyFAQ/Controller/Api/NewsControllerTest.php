<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use DateTime;
use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\NewsMessage;
use phpMyFAQ\Language;
use phpMyFAQ\News;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(NewsController::class)]
#[UsesNamespace('phpMyFAQ')]
class NewsControllerTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    /**
     * @throws MockException
     */
    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);
    }

    private function createRequest(): Request
    {
        return Request::create('/api/v4.0/news', 'GET');
    }

    public function testListReturnsJsonResponse(): void
    {
        $controller = new NewsController();
        $response = $controller->list($this->createRequest());

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testListReturnsValidStatusCode(): void
    {
        $controller = new NewsController();
        $response = $controller->list($this->createRequest());

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testListReturnsJsonData(): void
    {
        $controller = new NewsController();
        $response = $controller->list($this->createRequest());

        $this->assertJson($response->getContent());
    }

    public function testListReturnsArrayData(): void
    {
        $controller = new NewsController();
        $response = $controller->list($this->createRequest());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testListResponseContentIsNotNull(): void
    {
        $controller = new NewsController();
        $response = $controller->list($this->createRequest());

        $this->assertNotNull($response->getContent());
    }

    /**
     * Security regression: the author's e-mail address must never reach the unauthenticated
     * public news endpoint (GET /news).
     */
    public function testListDoesNotExposeAuthorEmail(): void
    {
        $newsMessage = new NewsMessage()
            ->setCreated(new DateTime())
            ->setLanguage('en')
            ->setHeader('Public news')
            ->setMessage('Hello, phpMyFAQ!')
            ->setAuthor('Jane Doe')
            ->setEmail('jane.doe@corp.example')
            ->setActive(true)
            ->setComment(false);
        $this->assertTrue(new News($this->configuration)->create($newsMessage));

        $controller = new NewsController();
        $response = $controller->list($this->createRequest());
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNotEmpty($payload['data']);
        foreach ($payload['data'] as $item) {
            $this->assertArrayHasKey('authorName', $item);
            $this->assertArrayNotHasKey('authorEmail', $item);
        }
        $this->assertStringNotContainsString('corp.example', (string) $response->getContent());
    }

    public function testListReturnsEmptyArrayOn404(): void
    {
        $controller = new NewsController();
        $response = $controller->list($this->createRequest());

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            $this->assertEquals([], json_decode($response->getContent(), true));
        } else {
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }
    }
}
