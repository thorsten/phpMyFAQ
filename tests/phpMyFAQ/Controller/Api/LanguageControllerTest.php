<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class LanguageControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $language = new Language($configuration);
        $language->setLanguage(true, 'language_en.php');

        $configuration->setLanguage($language);

        $languageController = new LanguageController();

        $response = $languageController->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($language->getLanguage(), json_decode($response->getContent(), true));
    }
}
