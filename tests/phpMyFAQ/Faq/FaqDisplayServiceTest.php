<?php

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class FaqDisplayServiceTest extends TestCase
{
    private Configuration $configuration;
    private CurrentUser|MockObject $currentUser;
    private array $currentGroups;
    private Faq|MockObject $faq;
    private Category|MockObject $category;
    private FaqDisplayService $service;

    /**
     * @throws Exception
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        // Create configuration with real database
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $this->configuration->setLanguage($language);

        // Set configuration values
        $this->configuration->set('main.enableMarkdownEditor', false);
        $this->configuration->set('records.disableAttachments', false);

        // Create mock current user
        $this->currentUser = $this->createMock(CurrentUser::class);
        $this->currentUser
            ->method('getUserId')
            ->willReturn(1);

        $this->currentGroups = [1];

        // Create mock FAQ
        $this->faq = $this->createMock(Faq::class);
        $this->faq->faqRecord = [
            'id' => 1,
            'lang' => 'en',
            'solution_id' => 1000,
            'active' => 'yes',
            'content' => 'Test answer content',
            'title' => 'Test Question',
            'keywords' => 'test, keywords',
            'dateEnd' => '99991231235959',
            'author' => 'John Doe',
            'email' => 'john@example.com',
            'created' => '2024-01-01 12:00:00',
            'date' => '2024-01-02 12:00:00',
            'notes' => 'Admin notes',
            'comment' => 'y',
        ];

        // Create mock category
        $this->category = $this->createMock(Category::class);

        $this->service = new FaqDisplayService(
            $this->configuration,
            $this->currentUser,
            $this->currentGroups,
            $this->faq,
            $this->category,
        );
    }

    /**
     * @throws Exception
     */
    public function testConstructor(): void
    {
        $service = new FaqDisplayService(
            $this->configuration,
            $this->currentUser,
            $this->currentGroups,
            $this->faq,
            $this->category,
        );

        static::assertInstanceOf(FaqDisplayService::class, $service);
    }

    /**
     * @throws Exception
     */
    public function testLoadFaqById(): void
    {
        $this->faq
            ->expects(static::once())
            ->method('getFaq')
            ->with(42);

        $result = $this->service->loadFaq(42, null);

        static::assertSame(1, $result);
    }

    /**
     * @throws Exception
     */
    public function testLoadFaqByIdWithZeroSolutionId(): void
    {
        $this->faq
            ->expects(static::once())
            ->method('getFaq')
            ->with(42);

        $result = $this->service->loadFaq(42, 0);

        static::assertSame(1, $result);
    }

    /**
     * @throws Exception
     */
    public function testLoadFaqBySolutionId(): void
    {
        $this->faq
            ->expects(static::once())
            ->method('getFaqBySolutionId')
            ->with(1000);

        $result = $this->service->loadFaq(0, 1000);

        static::assertSame(1, $result);
    }

    /**
     * @throws Exception
     */
    public function testProcessAnswerWithoutMarkdown(): void
    {
        $this->configuration->set('main.enableMarkdownEditor', false);

        $this->faq
            ->method('getQuestion')
            ->willReturn('Test Question');

        $result = $this->service->processAnswer('http://example.com/faq.html', null);

        static::assertIsString($result);
        static::assertStringContainsString('Test answer content', $result);
    }

    /**
     * @throws Exception
     */
    public function testProcessAnswerWithMarkdown(): void
    {
        $this->configuration->set('main.enableMarkdownEditor', true);
        $this->faq->faqRecord['content'] = '**Bold text**';

        $this->faq
            ->method('getQuestion')
            ->willReturn('Test Question');

        $result = $this->service->processAnswer('http://example.com/faq.html', null);

        static::assertIsString($result);
        static::assertStringContainsString('<strong>Bold text</strong>', $result);
    }

    /**
     * @throws Exception
     */
    public function testProcessAnswerWithHighlighting(): void
    {
        $this->faq
            ->method('getQuestion')
            ->willReturn('Test Question');

        $result = $this->service->processAnswer('http://example.com/faq.html', 'content');

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testProcessAnswerIgnoresShortHighlight(): void
    {
        $this->faq
            ->method('getQuestion')
            ->willReturn('Test Question');

        $result = $this->service->processAnswer('http://example.com/faq.html', 'ab');

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testProcessAnswerIgnoresSpecialCharacterHighlight(): void
    {
        $this->faq
            ->method('getQuestion')
            ->willReturn('Test Question');

        $result = $this->service->processAnswer('http://example.com/faq.html', '/');

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testProcessQuestion(): void
    {
        $this->faq
            ->method('getQuestion')
            ->willReturn('Test Question');

        $result = $this->service->processQuestion(null);

        static::assertSame('Test Question', $result);
    }

    /**
     * @throws Exception
     */
    public function testProcessQuestionWithHighlighting(): void
    {
        $this->faq
            ->method('getQuestion')
            ->willReturn('Test Question');

        $result = $this->service->processQuestion('test');

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testGetAttachmentListWhenDisabled(): void
    {
        $this->configuration->set('records.disableAttachments', false);

        $result = $this->service->getAttachmentList(1);

        static::assertIsArray($result);
        static::assertEmpty($result);
    }

    /**
     * @throws Exception
     */
    public function testGetAttachmentListWhenFaqInactive(): void
    {
        $this->configuration->set('records.disableAttachments', true);
        $this->faq->faqRecord['active'] = 'no';

        $result = $this->service->getAttachmentList(1);

        static::assertIsArray($result);
        static::assertEmpty($result);
    }

    /**
     * @throws Exception
     */
    public function testGetRenderedCategoryPathSingleCategory(): void
    {
        $this->category
            ->method('getCategoriesFromFaq')
            ->willReturn([['id' => 1]]);

        $result = $this->service->getRenderedCategoryPath(1);

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testGetRenderedCategoryPathMultipleCategories(): void
    {
        $this->category
            ->method('getCategoriesFromFaq')
            ->willReturn([['id' => 1], ['id' => 2]]);

        $this->category
            ->method('getPath')
            ->willReturn('<ul><li>Category</li></ul>');

        $result = $this->service->getRenderedCategoryPath(1);

        static::assertIsString($result);
        static::assertNotEmpty($result);
    }

    /**
     * @throws Exception
     */
    public function testGetRenderedCategoryPathNoCategories(): void
    {
        $this->category
            ->method('getCategoriesFromFaq')
            ->willReturn([]);

        $result = $this->service->getRenderedCategoryPath(1);

        static::assertSame('', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetRelatedFaqs(): void
    {
        $result = $this->service->getRelatedFaqs(1);

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testIsExpiredFalse(): void
    {
        $this->faq->faqRecord['dateEnd'] = '99991231235959';

        $result = $this->service->isExpired();

        static::assertFalse($result);
    }

    /**
     * @throws Exception
     */
    public function testIsExpiredTrue(): void
    {
        $this->faq->faqRecord['dateEnd'] = '20200101000000';

        $result = $this->service->isExpired();

        static::assertTrue($result);
    }

    /**
     * @throws Exception
     */
    public function testGetNumberOfComments(): void
    {
        $result = $this->service->getNumberOfComments();

        static::assertIsArray($result);
    }

    /**
     * @throws Exception
     */
    public function testGetCommentsData(): void
    {
        $result = $this->service->getCommentsData(1);

        static::assertIsArray($result);
    }

    /**
     * @throws Exception
     */
    public function testGetAvailableLanguages(): void
    {
        $result = $this->service->getAvailableLanguages(1);

        static::assertIsArray($result);
    }

    /**
     * @throws Exception
     */
    public function testGetTagsHtml(): void
    {
        $result = $this->service->getTagsHtml(1);

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testGetRating(): void
    {
        $result = $this->service->getRating(1);

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testGetFaqHelper(): void
    {
        $result = $this->service->getFaqHelper();

        static::assertInstanceOf(\phpMyFAQ\Helper\FaqHelper::class, $result);
    }

    /**
     * @throws Exception
     */
    public function testProcessAnswerReturnsString(): void
    {
        $this->faq
            ->method('getQuestion')
            ->willReturn('Test Question');

        $result = $this->service->processAnswer('http://example.com/faq.html', null);

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testProcessQuestionReturnsString(): void
    {
        $this->faq
            ->method('getQuestion')
            ->willReturn('Test Question');

        $result = $this->service->processQuestion(null);

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testLoadFaqReturnsInteger(): void
    {
        $this->faq
            ->expects(static::once())
            ->method('getFaq')
            ->with(42);

        $result = $this->service->loadFaq(42, null);

        static::assertIsInt($result);
    }

    /**
     * @throws Exception
     */
    public function testGetAttachmentListReturnsArray(): void
    {
        $result = $this->service->getAttachmentList(1);

        static::assertIsArray($result);
    }

    /**
     * @throws Exception
     */
    public function testGetRenderedCategoryPathReturnsString(): void
    {
        $this->category
            ->method('getCategoriesFromFaq')
            ->willReturn([]);

        $result = $this->service->getRenderedCategoryPath(1);

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testIsExpiredReturnsBool(): void
    {
        $result = $this->service->isExpired();

        static::assertIsBool($result);
    }
}
