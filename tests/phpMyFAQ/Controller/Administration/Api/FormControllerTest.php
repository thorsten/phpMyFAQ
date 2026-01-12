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
class FormControllerTest extends TestCase
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
    public function testActivateInputRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'formid' => 1, 'inputid' => 1, 'checked' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->activateInput($request);
    }

    /**
     * @throws \Exception
     */
    public function testSetInputAsRequiredRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'formid' => 1, 'inputid' => 1, 'checked' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->setInputAsRequired($request);
    }

    /**
     * @throws \Exception
     */
    public function testEditTranslationRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
            'formId' => 1,
            'inputId' => 1,
            'lang' => 'en',
            'label' => 'Test',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->editTranslation($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteTranslationRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'formId' => 1, 'inputId' => 1, 'lang' => 'en']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->deleteTranslation($request);
    }

    /**
     * @throws \Exception
     */
    public function testAddTranslationRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
            'formId' => 1,
            'inputId' => 1,
            'lang' => 'en',
            'translation' => 'Test',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->addTranslation($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateInputWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->activateInput($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateInputWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['formid' => 1, 'inputid' => 1, 'checked' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->activateInput($request);
    }
}
