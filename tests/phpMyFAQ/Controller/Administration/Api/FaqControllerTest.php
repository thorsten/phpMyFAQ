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
class FaqControllerTest extends TestCase
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
    public function testCreateRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'data' => [
                'pmf-csrf-token' => 'test-token',
                'question' => 'Test Question',
                'answer' => 'Test Answer',
                'categories[]' => [1],
                'lang' => 'en',
                'author' => 'Test Author',
                'email' => 'test@example.com',
            ],
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'data' => [
                'pmf-csrf-token' => 'test-token',
                'faqId' => 1,
                'question' => 'Test Question',
                'answer' => 'Test Answer',
                'categories[]' => [1],
                'lang' => 'en',
            ],
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testListPermissionsRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->listPermissions($request);
    }

    /**
     * @throws \Exception
     */
    public function testListByCategoryRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->listByCategory($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'faqIds' => [1], 'faqLanguage' => 'en', 'checked' => true]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->activate($request);
    }

    /**
     * @throws \Exception
     */
    public function testStickyRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'faqIds' => [1], 'faqLanguage' => 'en', 'checked' => true]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->sticky($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'faqId' => 1, 'faqLanguage' => 'en']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testSearchRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'search' => 'test']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->search($request);
    }

    /**
     * @throws \Exception
     */
    public function testSaveOrderOfStickyFaqsRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'faqIds' => [1, 2, 3]]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->saveOrderOfStickyFaqs($request);
    }

    /**
     * @throws \Exception
     */
    public function testImportRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->import($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->activate($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'data' => [
                'question' => 'Test Question',
                'answer' => 'Test Answer',
            ],
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['faqIds' => [1], 'faqLanguage' => 'en', 'checked' => true]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController();

        $this->expectException(\Exception::class);
        $controller->activate($request);
    }
}
