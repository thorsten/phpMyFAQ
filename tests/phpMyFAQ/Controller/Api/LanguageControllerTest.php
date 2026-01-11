<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class LanguageControllerTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testIndex(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');

        $configuration->setLanguage($language);

        $languageController = new LanguageController();

        $response = $languageController->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($language->getLanguage(), json_decode($response->getContent(), true));
    }

    /**
     * @throws Exception
     */
    public function testIndexReturnsJsonResponse(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $configuration->setLanguage($language);

        $languageController = new LanguageController();
        $response = $languageController->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJson($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testIndexReturnsValidLanguageCode(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $configuration->setLanguage($language);

        $languageController = new LanguageController();
        $response = $languageController->index();

        $languageCode = json_decode($response->getContent(), true);
        $this->assertNotEmpty($languageCode);
        $this->assertIsString($languageCode);
        // Language code should be 2 characters (e.g., 'en', 'de')
        $this->assertMatchesRegularExpression('/^[a-z]{2}(-[A-Z]{2})?$/', $languageCode);
    }

    /**
     * @throws Exception
     */
    public function testIndexResponseContentIsNotNull(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $configuration->setLanguage($language);

        $languageController = new LanguageController();
        $response = $languageController->index();

        $this->assertNotNull($response->getContent());
    }
}
