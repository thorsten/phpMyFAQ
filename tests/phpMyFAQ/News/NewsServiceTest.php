<?php

declare(strict_types=1);

namespace phpMyFAQ\News\Test;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Glossary;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\News;
use phpMyFAQ\News\NewsService;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
class NewsServiceTest extends TestCase
{
    private Configuration $configuration;
    private CurrentUser $currentUser;
    private NewsService $newsService;

    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');

        $this->configuration = new Configuration($dbHandle);
        $this->primeConfiguration([
            'records.allowCommentsForGuests' => 'true',
            'security.useSslOnly' => 'false',
        ]);

        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(false);

        $this->currentUser = $this->createMock(CurrentUser::class);
        $this->currentUser->perm = $permission;
        $this->currentUser->method('getUserId')->willReturn(-1);

        $this->newsService = new NewsService($this->configuration, $this->currentUser);
    }

    public function testGetProcessedNewsReturnsEmptyArrayForMissingRecord(): void
    {
        $news = $this->createMock(News::class);
        $news->expects($this->once())->method('get')->with(123)->willReturn([]);

        $this->setProperty($this->newsService, 'news', $news);

        $this->assertSame([], $this->newsService->getProcessedNews(123));
    }

    public function testGetProcessedNewsProcessesContentHeaderAndInformationLink(): void
    {
        $news = $this->createMock(News::class);
        $glossary = $this->createMock(Glossary::class);
        $faqHelper = $this->createMock(FaqHelper::class);

        $news
            ->expects($this->once())
            ->method('get')
            ->with(55)
            ->willReturn([
                'content' => 'See FAQ',
                'header' => 'FAQ Header',
                'link' => 'https://example.org/info?x=1&y=2',
                'target' => '_blank',
                'linkTitle' => 'More & More',
            ]);
        $glossary
            ->method('insertItemsIntoContent')
            ->willReturnCallback(static fn(string $content): string => 'glossary:' . $content);
        $faqHelper
            ->method('cleanUpContent')
            ->willReturnCallback(static fn(string $content): string => 'clean:' . $content);

        $this->setProperty($this->newsService, 'news', $news);
        $this->setProperty($this->newsService, 'glossary', $glossary);
        $this->setProperty($this->newsService, 'faqHelper', $faqHelper);

        $result = $this->newsService->getProcessedNews(55);

        $this->assertStringStartsWith('clean:glossary:See FAQ', $result['processedContent']);
        $this->assertSame('clean:glossary:FAQ Header', $result['processedHeader']);
        $this->assertStringContainsString(
            'https&colon;&sol;&sol;example&period;org&sol;info&quest;x&equals;1&amp;y&equals;2',
            $result['processedContent'],
        );
        $this->assertStringContainsString('More &amp; More', $result['processedContent']);
        $this->assertStringContainsString('_blank', $result['processedContent']);
    }

    public function testGetProcessedNewsSkipsInformationLinkWhenLinkIsEmpty(): void
    {
        $news = $this->createMock(News::class);
        $glossary = $this->createMock(Glossary::class);
        $faqHelper = $this->createMock(FaqHelper::class);

        $news
            ->expects($this->once())
            ->method('get')
            ->with(77)
            ->willReturn([
                'content' => 'Content',
                'header' => 'Header',
                'link' => '',
                'target' => '_self',
                'linkTitle' => '',
            ]);
        $glossary->method('insertItemsIntoContent')->willReturnArgument(0);
        $faqHelper->method('cleanUpContent')->willReturnArgument(0);

        $this->setProperty($this->newsService, 'news', $news);
        $this->setProperty($this->newsService, 'glossary', $glossary);
        $this->setProperty($this->newsService, 'faqHelper', $faqHelper);

        $result = $this->newsService->getProcessedNews(77);

        $this->assertSame('Content', $result['processedContent']);
        $this->assertSame('Header', $result['processedHeader']);
    }

    public function testGetEditLinkReturnsAnchorWhenUserHasPermission(): void
    {
        $this->currentUser = $this->createCurrentUser(userId: -1);
        $permission = $this->createMock(PermissionInterface::class);
        $permission->expects($this->once())->method('hasPermission')->with(-1, 'editnews')->willReturn(true);

        $this->currentUser->perm = $permission;
        $this->newsService = new NewsService($this->configuration, $this->currentUser);

        $result = $this->newsService->getEditLink(99);

        $this->assertStringContainsString('./admin/news/edit/99', $result);
    }

    public function testGetEditLinkReturnsEmptyStringWithoutPermission(): void
    {
        $this->currentUser = $this->createCurrentUser(userId: -1);
        $permission = $this->createMock(PermissionInterface::class);
        $permission->expects($this->once())->method('hasPermission')->with(-1, 'editnews')->willReturn(false);

        $this->currentUser->perm = $permission;
        $this->newsService = new NewsService($this->configuration, $this->currentUser);

        $this->assertSame('', $this->newsService->getEditLink(11));
    }

    public function testGetCommentMessageReturnsNoCommentMessageForGuestsWhenDisallowed(): void
    {
        $this->primeConfiguration([
            'records.allowCommentsForGuests' => 'false',
        ]);

        $result = $this->newsService->getCommentMessage([
            'active' => true,
            'allowComments' => true,
        ]);

        $this->assertSame('You cannot comment on this entry', $result);
    }

    public function testGetCommentMessageReturnsNoCommentMessageForInactiveOrDisallowedNews(): void
    {
        $inactive = $this->newsService->getCommentMessage([
            'active' => false,
            'allowComments' => true,
        ]);
        $disallowed = $this->newsService->getCommentMessage([
            'active' => true,
            'allowComments' => false,
        ]);

        $this->assertSame('You cannot comment on this entry', $inactive);
        $this->assertSame('You cannot comment on this entry', $disallowed);
    }

    public function testGetCommentMessageReturnsCommentLinkWhenAllowed(): void
    {
        $this->configuration->set('records.allowCommentsForGuests', true);
        $this->currentUser->method('getUserId')->willReturn(5);

        $result = $this->newsService->getCommentMessage([
            'active' => true,
            'allowComments' => true,
        ]);

        $this->assertStringContainsString('pmf-modal-add-comment', $result);
    }

    public function testFormatNewsDateReturnsEmptyStringForInactiveNews(): void
    {
        $this->assertSame('', $this->newsService->formatNewsDate([
            'active' => false,
            'date' => '20250101123000',
        ]));
    }

    public function testFormatNewsDateReturnsFormattedMarkupForActiveNews(): void
    {
        $result = $this->newsService->formatNewsDate([
            'active' => true,
            'date' => '20250101123000',
        ]);

        $this->assertStringContainsString('newsLastUpd', $result);
        $this->assertStringContainsString('2025', $result);
    }

    public function testGetAuthorInfoReturnsEmptyStringForInactiveNews(): void
    {
        $this->assertSame('', $this->newsService->getAuthorInfo([
            'active' => false,
            'authorName' => 'Tester',
        ]));
    }

    public function testGetAuthorInfoReturnsTranslatedAuthorStringForActiveNews(): void
    {
        $result = $this->newsService->getAuthorInfo([
            'active' => true,
            'authorName' => 'Tester',
        ]);

        $this->assertStringContainsString('Tester', $result);
        $this->assertStringContainsString('Author', $result);
    }

    private function setProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setValue($object, $value);
    }

    private function primeConfiguration(array $values): void
    {
        $reflection = new ReflectionProperty($this->configuration, 'config');
        $config = $reflection->getValue($this->configuration);
        $reflection->setValue($this->configuration, array_merge($config, $values));
    }

    private function createCurrentUser(int $userId): CurrentUser
    {
        $currentUser = $this
            ->getMockBuilder(CurrentUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserId'])
            ->getMock();
        $currentUser->method('getUserId')->willReturn($userId);

        return $currentUser;
    }
}
