<?php

namespace phpMyFAQ;

use DateTime;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\NewsMessage;
use phpMyFAQ\News\NewsRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class NewsTest extends TestCase
{
    private News $news;
    private Configuration $configuration;
    private NewsRepositoryInterface $mockRepository;

    /**
     * @throws Exception|Core\Exception
     */
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
        $this->configuration->set('main.language', 'en');
        $this->configuration->set('main.referenceURL', 'https://example.org/');

        Language::$language = 'en';
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $this->configuration->setLanguage($language);

        // Create a mock repository
        $this->mockRepository = $this->createMock(NewsRepositoryInterface::class);

        $this->news = new News($this->configuration, $this->mockRepository);
    }

    public function testCreate(): void
    {
        $newsMessage = new NewsMessage();
        $newsMessage
            ->setCreated(new DateTime())
            ->setLanguage('en')
            ->setHeader('Test')
            ->setMessage('Message')
            ->setAuthor('Test Author')
            ->setEmail('test@example.org')
            ->setActive(true)
            ->setComment(true);

        $this->mockRepository
            ->expects($this->once())
            ->method('insert')
            ->with($newsMessage)
            ->willReturn(true);

        $this->assertTrue($this->news->create($newsMessage));
    }

    public function testUpdate(): void
    {
        $newsMessage = new NewsMessage();
        $newsMessage
            ->setId(1)
            ->setCreated(new DateTime())
            ->setLanguage('en')
            ->setHeader('Test Updated')
            ->setMessage('Message Updated')
            ->setAuthor('Test Author')
            ->setEmail('test@example.org')
            ->setActive(true)
            ->setComment(true);

        $this->mockRepository
            ->expects($this->once())
            ->method('update')
            ->with($newsMessage)
            ->willReturn(true);

        $this->assertTrue($this->news->update($newsMessage));
    }

    public function testDelete(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('delete')
            ->with(1, 'en')
            ->willReturn(true);

        $this->assertTrue($this->news->delete(1));
    }

    public function testDeleteFails(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('delete')
            ->with(999, 'en')
            ->willReturn(false);

        $this->assertFalse($this->news->delete(999));
    }

    public function testActivate(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('activate')
            ->with(1, true)
            ->willReturn(true);

        $this->assertTrue($this->news->activate(1));
    }

    public function testDeactivate(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('activate')
            ->with(1, false)
            ->willReturn(true);

        $this->assertTrue($this->news->deactivate(1));
    }

    /**
     * @throws \Exception
     */ public function testGetAll(): void
    {
        $mockRow = new stdClass();
        $mockRow->id = 1;
        $mockRow->lang = 'en';
        $mockRow->header = 'Test News';
        $mockRow->artikel = '<p>Test content</p>';
        $mockRow->datum = '20251222100000';

        $this->mockRepository
            ->expects($this->once())
            ->method('getLatest')
            ->with('en', true, 5)
            ->willReturn([$mockRow]);

        $this->configuration->set('records.numberOfShownNewsEntries', '5');

        $result = $this->news->getAll(false, true);

        $this->assertCount(1, $result);
        $this->assertEquals('Test News', $result[0]->header);
        $this->assertEquals('Test content', $result[0]->content);
        $this->assertStringContainsString('/news/1/', $result[0]->url);
    }

    /**
     * @throws \Exception
     */ public function testGetAllShowArchive(): void
    {
        $mockRow = new stdClass();
        $mockRow->id = 1;
        $mockRow->lang = 'en';
        $mockRow->header = 'Archived News';
        $mockRow->artikel = 'Archived content';
        $mockRow->datum = '20200101120000';

        $this->mockRepository
            ->expects($this->once())
            ->method('getLatest')
            ->with('en', true, null)
            ->willReturn([$mockRow]);

        $result = $this->news->getAll(true, true);

        $this->assertCount(1, $result);
        $this->assertEquals('Archived News', $result[0]->header);
    }

    public function testGetAllInactive(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('getLatest')
            ->with('en', false, null)
            ->willReturn([]);

        $result = $this->news->getAll(false, false);

        $this->assertEmpty($result);
    }

    public function testGetLatestData(): void
    {
        $mockRow = new stdClass();
        $mockRow->id = 1;
        $mockRow->lang = 'en';
        $mockRow->header = 'Latest News';
        $mockRow->artikel = 'Latest content';
        $mockRow->datum = '20251222100000';
        $mockRow->author_name = 'John Doe';
        $mockRow->author_email = 'john@example.org';
        $mockRow->active = 'y';
        $mockRow->comment = 'y';
        $mockRow->link = 'https://example.org';
        $mockRow->linktitel = 'Example';
        $mockRow->target = '_blank';

        $this->mockRepository
            ->expects($this->once())
            ->method('getLatest')
            ->with('en', true, 5)
            ->willReturn([$mockRow]);

        $this->configuration->set('records.numberOfShownNewsEntries', '5');

        $result = $this->news->getLatestData();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('Latest News', $result[0]['header']);
        $this->assertEquals('Latest content', $result[0]['content']);
        $this->assertEquals('John Doe', $result[0]['authorName']);
        $this->assertEquals('john@example.org', $result[0]['authorEmail']);
        $this->assertTrue($result[0]['active']);
        $this->assertTrue($result[0]['allowComments']);
        $this->assertEquals('https://example.org', $result[0]['link']);
        $this->assertEquals('Example', $result[0]['linkTitle']);
        $this->assertEquals('_blank', $result[0]['target']);
    }

    public function testGetLatestDataWithArchive(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('getLatest')
            ->with('en', true, null)
            ->willReturn([]);

        $this->configuration->set('records.numberOfShownNewsEntries', '5');

        $result = $this->news->getLatestData(true);

        $this->assertEmpty($result);
    }

    public function testGetLatestDataForceConfLimit(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('getLatest')
            ->with('en', true, 10)
            ->willReturn([]);

        $this->configuration->set('records.numberOfShownNewsEntries', '10');

        $result = $this->news->getLatestData(false, true, true);

        $this->assertEmpty($result);
    }

    public function testGetHeader(): void
    {
        $mockRow = new stdClass();
        $mockRow->id = 1;
        $mockRow->lang = 'en';
        $mockRow->header = 'News Header';
        $mockRow->datum = '20251222100000';
        $mockRow->active = 'y';

        $this->mockRepository
            ->expects($this->once())
            ->method('getHeaders')
            ->with('en')
            ->willReturn([$mockRow]);

        $result = $this->news->getHeader();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[1]['id']);
        $this->assertEquals('en', $result[1]['lang']);
        $this->assertEquals('News Header', $result[1]['header']);
        $this->assertEquals('y', $result[1]['active']);
    }

    public function testGetHeaderEmpty(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('getHeaders')
            ->with('en')
            ->willReturn([]);

        $result = $this->news->getHeader();

        $this->assertEmpty($result);
    }

    public function testGet(): void
    {
        $mockRow = new stdClass();
        $mockRow->id = 1;
        $mockRow->lang = 'en';
        $mockRow->header = 'Single News';
        $mockRow->artikel = 'Single news content';
        $mockRow->datum = '20251222100000';
        $mockRow->author_name = 'Jane Doe';
        $mockRow->author_email = 'jane@example.org';
        $mockRow->active = 'y';
        $mockRow->comment = 'y';
        $mockRow->link = 'https://example.org';
        $mockRow->linktitel = 'Example';
        $mockRow->target = '_self';

        $this->mockRepository
            ->expects($this->once())
            ->method('getById')
            ->with(1, 'en')
            ->willReturn($mockRow);

        $result = $this->news->get(1);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('en', $result['lang']);
        $this->assertEquals('Single News', $result['header']);
        $this->assertEquals('Single news content', $result['content']);
        $this->assertEquals('Jane Doe', $result['authorName']);
        $this->assertEquals('jane@example.org', $result['authorEmail']);
        $this->assertTrue($result['active']);
        $this->assertTrue($result['allowComments']);
        $this->assertEquals('https://example.org', $result['link']);
        $this->assertEquals('Example', $result['linkTitle']);
        $this->assertEquals('_self', $result['target']);
    }

    public function testGetNonExistent(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('getById')
            ->with(999, 'en')
            ->willReturn(null);

        $result = $this->news->get(999);

        $this->assertEmpty($result);
    }

    public function testGetInactiveAsNonAdmin(): void
    {
        $mockRow = new stdClass();
        $mockRow->id = 1;
        $mockRow->lang = 'en';
        $mockRow->header = 'Inactive News';
        $mockRow->artikel = 'This should not be shown';
        $mockRow->datum = '20251222100000';
        $mockRow->author_name = 'Admin';
        $mockRow->author_email = 'admin@example.org';
        $mockRow->active = 'n';
        $mockRow->comment = 'n';
        $mockRow->link = '';
        $mockRow->linktitel = '';
        $mockRow->target = '';

        $this->mockRepository
            ->expects($this->once())
            ->method('getById')
            ->with(1, 'en')
            ->willReturn($mockRow);

        $result = $this->news->get(1, false);

        $this->assertFalse($result['active']);
        $this->assertStringContainsString('revision', strtolower($result['content']));
    }

    public function testGetInactiveAsAdmin(): void
    {
        $mockRow = new stdClass();
        $mockRow->id = 1;
        $mockRow->lang = 'en';
        $mockRow->header = 'Inactive News';
        $mockRow->artikel = 'This should be shown to admin';
        $mockRow->datum = '20251222100000';
        $mockRow->author_name = 'Admin';
        $mockRow->author_email = 'admin@example.org';
        $mockRow->active = 'n';
        $mockRow->comment = 'n';
        $mockRow->link = '';
        $mockRow->linktitel = '';
        $mockRow->target = '';

        $this->mockRepository
            ->expects($this->once())
            ->method('getById')
            ->with(1, 'en')
            ->willReturn($mockRow);

        $result = $this->news->get(1, true);

        $this->assertFalse($result['active']);
        $this->assertEquals('This should be shown to admin', $result['content']);
    }

    public function testGetWithCommentsDisabled(): void
    {
        $mockRow = new stdClass();
        $mockRow->id = 1;
        $mockRow->lang = 'en';
        $mockRow->header = 'News without comments';
        $mockRow->artikel = 'Content';
        $mockRow->datum = '20251222100000';
        $mockRow->author_name = 'Author';
        $mockRow->author_email = 'author@example.org';
        $mockRow->active = 'y';
        $mockRow->comment = 'n';
        $mockRow->link = '';
        $mockRow->linktitel = '';
        $mockRow->target = '';

        $this->mockRepository
            ->expects($this->once())
            ->method('getById')
            ->with(1, 'en')
            ->willReturn($mockRow);

        $result = $this->news->get(1);

        $this->assertTrue($result['active']);
        $this->assertFalse($result['allowComments']);
    }
}
