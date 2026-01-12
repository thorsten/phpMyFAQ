<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class UnauthorizedUserControllerTest extends TestCase
{
    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configuration = Configuration::getConfigurationInstance();
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();

        $this->expectException(\Exception::class);
        $controller->updatePassword($request);
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordWithMissingUsernameReturnsConflict(): void
    {
        $requestData = json_encode([
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordWithMissingEmailReturnsConflict(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordWithInvalidEmailReturnsConflict(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'email' => 'invalid-email',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordWithNonExistentUserReturnsConflict(): void
    {
        $requestData = json_encode([
            'username' => 'nonexistentuser' . time(),
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordReturnsJsonResponse(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordResponseHasCorrectContentType(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function testJsonReturnsJsonResponse(): void
    {
        $controller = new UnauthorizedUserController();
        $response = $controller->json(['test' => 'data']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testJsonWithCustomStatusCode(): void
    {
        $controller = new UnauthorizedUserController();
        $response = $controller->json(['error' => 'test'], Response::HTTP_BAD_REQUEST);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
