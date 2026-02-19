<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\CustomPage;
use phpMyFAQ\Faq;
use phpMyFAQ\Instance\Search\OpenSearch;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class OpenSearchControllerTest extends TestCase
{
    private Configuration $configuration;
    private OpenSearch $openSearch;
    private Faq $faq;
    private CustomPage $customPage;

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
        $this->openSearch = $this->createStub(OpenSearch::class);
        $this->faq = $this->createStub(Faq::class);
        $this->customPage = $this->createStub(CustomPage::class);
    }

    /**
     * @throws \Exception
     */
    public function testCreateRequiresAuthentication(): void
    {
        $controller = new OpenSearchController($this->openSearch, $this->faq, $this->customPage);

        $this->expectException(\Exception::class);
        $controller->create();
    }

    /**
     * @throws \Exception
     */
    public function testDropRequiresAuthentication(): void
    {
        $controller = new OpenSearchController($this->openSearch, $this->faq, $this->customPage);

        $this->expectException(\Exception::class);
        $controller->drop();
    }

    /**
     * @throws \Exception
     */
    public function testImportRequiresAuthentication(): void
    {
        $controller = new OpenSearchController($this->openSearch, $this->faq, $this->customPage);

        $this->expectException(\Exception::class);
        $controller->import();
    }

    /**
     * @throws \Exception
     */
    public function testStatisticsRequiresAuthentication(): void
    {
        $controller = new OpenSearchController($this->openSearch, $this->faq, $this->customPage);

        $this->expectException(\Exception::class);
        $controller->statistics();
    }

    /**
     * @throws \Exception
     */
    public function testHealthcheckRequiresAuthentication(): void
    {
        $controller = new OpenSearchController($this->openSearch, $this->faq, $this->customPage);

        $this->expectException(\Exception::class);
        $controller->healthcheck();
    }
}
