<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

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
class LoginControllerTest extends TestCase
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
     * @throws \JsonException
     */public function testLoginReturnsJsonResponse(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */public function testLoginReturnsCorrectStatusCodeOnFailure(): void
    {
        $requestData = json_encode([
            'username' => 'invaliduser',
            'password' => 'invalidpassword',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */public function testLoginResponseContainsLoggedInField(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('loggedin', $content);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */public function testLoginFailureContainsErrorField(): void
    {
        $requestData = json_encode([
            'username' => 'invaliduser',
            'password' => 'invalidpassword',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertFalse($content['loggedin']);
    }
}
