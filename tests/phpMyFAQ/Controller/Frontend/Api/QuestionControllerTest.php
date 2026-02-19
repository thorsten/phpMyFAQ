<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Notification;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class QuestionControllerTest extends TestCase
{
    private Configuration $configuration;
    private StopWords $stopWords;
    private QuestionHelper $questionHelper;
    private Search $search;
    private Question $question;
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

        $this->stopWords = $this->createStub(StopWords::class);
        $this->questionHelper = $this->createStub(QuestionHelper::class);
        $this->search = $this->createStub(Search::class);
        $this->question = $this->createStub(Question::class);
        $this->notification = $this->createStub(Notification::class);
    }

    private function createController(): QuestionController
    {
        return new QuestionController(
            $this->stopWords,
            $this->questionHelper,
            $this->search,
            $this->question,
            $this->notification,
        );
    }

    /**
     * @throws Exception
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
            'lang' => 'en',
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
            'lang' => 'en',
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
            'lang' => 'en',
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
    public function testCreateWithMissingLanguageThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
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
    public function testCreateWithCategoryThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'lang' => 'en',
            'category' => 1,
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
    public function testCreateWithSaveParameterThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'lang' => 'en',
            'save' => 1,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }
}
