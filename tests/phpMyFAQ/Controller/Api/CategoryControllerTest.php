<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class CategoryControllerTest extends TestCase
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
     * @throws MockException
     */
    public function testListReturnsJsonResponse(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController();
        $response = $controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws MockException
     */
    public function testListReturnsValidStatusCode(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController();
        $response = $controller->list();

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testCreateRequiresValidToken(): void
    {
        $requestData = json_encode([
            'language' => 'en',
            'parent-id' => 0,
            'category-name' => 'Test Category',
            'description' => 'Test Description',
            'user-id' => 1,
            'group-id' => -1,
            'is-active' => true,
            'show-on-homepage' => true,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CategoryController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CategoryController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    public function testCreateWithMissingRequiredFieldsThrowsException(): void
    {
        $requestData = json_encode([
            'language' => 'en',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CategoryController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws MockException
     */
    public function testListResponseContainsJsonData(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController();
        $response = $controller->list();

        $this->assertJson($response->getContent());
    }

    /**
     * @throws MockException
     */
    public function testListReturnsArrayData(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController();
        $response = $controller->list();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * @throws MockException
     */
    public function testListResponseContentIsNotNull(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController();
        $response = $controller->list();

        $this->assertNotNull($response->getContent());
    }

    /**
     * @throws MockException
     */
    public function testListReturnsEmptyArrayOn404(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController();
        $response = $controller->list();

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            $this->assertEquals([], json_decode($response->getContent(), true));
        } else {
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }
    }
}
