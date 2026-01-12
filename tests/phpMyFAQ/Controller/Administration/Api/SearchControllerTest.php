<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class SearchControllerTest extends TestCase
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
     * @throws \Exception
     */
    public function testDeleteTermRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'searchTermId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new SearchController();

        $this->expectException(\Exception::class);
        $controller->deleteTerm($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteTermWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new SearchController();

        $this->expectException(\Exception::class);
        $controller->deleteTerm($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteTermWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['searchTermId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new SearchController();

        $this->expectException(\Exception::class);
        $controller->deleteTerm($request);
    }
}
