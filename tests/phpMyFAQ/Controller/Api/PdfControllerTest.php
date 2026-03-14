<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Services;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class PdfControllerTest extends TestCase
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

        $this->configuration = $this->createConfiguration();
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    private function createConfiguration(): Configuration
    {
        try {
            return Configuration::getConfigurationInstance();
        } catch (\TypeError) {
            $db = new Sqlite3();
            $db->connect(PMF_TEST_DIR . '/test.db', '', '');

            return new Configuration($db);
        }
    }

    /**
     * @throws Exception
     */
    public function testGetByIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');
        $request->attributes->set('faqId', '1');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdReturnsValidStatusCode(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');
        $request->attributes->set('faqId', '1');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdWithNonExistentFaq(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '999999');
        $request->attributes->set('faqId', '999999');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testGetByIdReturnsJsonData(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');
        $request->attributes->set('faqId', '1');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertJson($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetByIdWithZeroIds(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '0');
        $request->attributes->set('faqId', '0');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdWithInvalidCategoryId(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', 'invalid');
        $request->attributes->set('faqId', '1');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdWithInvalidFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');
        $request->attributes->set('faqId', 'invalid');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetByIdReturnsPdfLinkWhenFaqExists(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '3');
        $request->attributes->set('faqId', '7');

        $faq = $this->createMock(\phpMyFAQ\Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq
            ->expects($this->once())
            ->method('getFaq')
            ->with(7)
            ->willReturnCallback(function () use ($faq): void {
                $faq->faqRecord = ['solution_id' => 1007];
            });

        $services = $this->createMock(Services::class);
        $services->expects($this->once())->method('setFaqId')->with(7)->willReturnSelf();
        $services->expects($this->once())->method('setLanguage')->with('en')->willReturnSelf();
        $services->expects($this->once())->method('setCategoryId')->with(3)->willReturnSelf();
        $services->expects($this->once())->method('getPdfApiLink')->willReturn('http://example.com/pdf/3/7/en');

        $controller = new PdfController();
        $controller->setFaqFactory(static fn() => $faq);
        $controller->setServicesFactory(static fn() => $services);

        $response = $controller->getById($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('"http:\/\/example.com\/pdf\/3\/7\/en"', (string) $response->getContent());
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetByIdReturnsNotFoundWhenFaqUsesSentinelSolutionId(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '3');
        $request->attributes->set('faqId', '42');

        $faq = $this->createMock(\phpMyFAQ\Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq
            ->expects($this->once())
            ->method('getFaq')
            ->with(42)
            ->willReturnCallback(function () use ($faq): void {
                $faq->faqRecord = ['solution_id' => 42];
            });

        $controller = new PdfController();
        $controller->setFaqFactory(static fn() => $faq);

        $response = $controller->getById($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('{}', (string) $response->getContent());
    }
}
