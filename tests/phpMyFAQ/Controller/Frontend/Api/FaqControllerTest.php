<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Faq;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Notification;
use phpMyFAQ\Question;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(FaqController::class)]
#[UsesNamespace('phpMyFAQ')]
class FaqControllerTest extends TestCase
{
    private Configuration $configuration;
    private Faq $faq;
    private FaqHelper $faqHelper;
    private Question $question;
    private StopWords $stopWords;
    private UserSession $userSession;
    private Language $language;
    private CategoryHelper $categoryHelper;
    private Notification $notification;

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

        $this->faq = $this->createStub(Faq::class);
        $this->faqHelper = $this->createStub(FaqHelper::class);
        $this->question = $this->createStub(Question::class);
        $this->stopWords = $this->createStub(StopWords::class);
        $this->userSession = $this->createStub(UserSession::class);
        $this->language = $this->createStub(Language::class);
        $this->categoryHelper = $this->createStub(CategoryHelper::class);
        $this->notification = $this->createStub(Notification::class);
    }

    private function createController(): FaqController
    {
        return new FaqController(
            $this->faq,
            $this->faqHelper,
            $this->question,
            $this->stopWords,
            $this->userSession,
            $this->language,
            $this->categoryHelper,
            $this->notification,
        );
    }

    /**
     * @throws \JsonException
     */
    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithMissingNameThrowsException(): void
    {
        $requestData = json_encode([
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'keywords' => 'test',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithInvalidEmailThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'invalid-email',
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'keywords' => 'test',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithEmptyQuestionThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => '',
            'answer' => 'Test answer',
            'keywords' => 'test',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithMissingAnswerThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'keywords' => 'test',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }
}
