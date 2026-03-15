<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
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
    private Configuration $configuration;

    /**
     * @throws MockException
     */
    protected function setUp(): void
    {
        $this->configuration = Configuration::getConfigurationInstance();
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);
    }

    private function createRequest(): Request
    {
        return Request::create('/api/v3.2/news', 'GET');
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
