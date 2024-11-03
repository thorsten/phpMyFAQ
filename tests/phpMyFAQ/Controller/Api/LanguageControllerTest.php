<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class LanguageControllerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testIndex(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $language = new Language($configuration, $this->createMock(Session::class));
        $language->setLanguage(true, 'language_en.php');

        $configuration->setLanguage($language);

        $languageController = new LanguageController();

        $response = $languageController->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($language->getLanguage(), json_decode($response->getContent(), true));
    }
}
