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
class ConfigurationTabControllerTest extends TestCase
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
    public function testListRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->list($request);
    }

    /**
     * @throws \Exception
     */
    public function testSaveRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->save($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslationsRequiresAuthentication(): void
    {
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->translations();
    }

    /**
     * @throws \Exception
     */
    public function testTemplatesRequiresAuthentication(): void
    {
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->templates();
    }

    /**
     * @throws \Exception
     */
    public function testFaqsSortingKeyRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->faqsSortingKey($request);
    }

    /**
     * @throws \Exception
     */
    public function testFaqsSortingOrderRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->faqsSortingOrder($request);
    }

    /**
     * @throws \Exception
     */
    public function testFaqsSortingPopularRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->faqsSortingPopular($request);
    }

    /**
     * @throws \Exception
     */
    public function testPermLevelRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->permLevel($request);
    }

    /**
     * @throws \Exception
     */
    public function testReleaseEnvironmentRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->releaseEnvironment($request);
    }

    /**
     * @throws \Exception
     */
    public function testSearchRelevanceRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->searchRelevance($request);
    }

    /**
     * @throws \Exception
     */
    public function testSeoMetaTagsRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->seoMetaTags($request);
    }

    /**
     * @throws \Exception
     */
    public function testSaveWithMissingCsrfTokenThrowsException(): void
    {
        $request = new Request();
        $controller = new ConfigurationTabController();

        $this->expectException(\Exception::class);
        $controller->save($request);
    }
}
