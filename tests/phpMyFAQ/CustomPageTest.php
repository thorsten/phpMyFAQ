<?php

namespace phpMyFAQ;

use DateTime;
use phpMyFAQ\CustomPage\CustomPageRepositoryInterface;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\CustomPageEntity;
use phpMyFAQ\Seo\SeoRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class CustomPageTest extends TestCase
{
    private CustomPage $customPage;
    private Configuration $configuration;
    private CustomPageRepositoryInterface $mockRepository;
    private SeoRepositoryInterface $mockSeoRepository;

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

        // Create mock repositories
        $this->mockRepository = $this->createMock(CustomPageRepositoryInterface::class);
        $this->mockSeoRepository = $this->createMock(SeoRepositoryInterface::class);

        $this->customPage = new CustomPage($this->configuration, $this->mockRepository, $this->mockSeoRepository);
    }

    public function testCreate(): void
    {
        $page = new CustomPageEntity();
        $page
            ->setLanguage('en')
            ->setPageTitle('Test Page')
            ->setSlug('test-page')
            ->setContent('<p>Test content</p>')
            ->setAuthorName('Test Author')
            ->setAuthorEmail('test@example.org')
            ->setActive(true)
            ->setCreated(new DateTime());

        $this->mockRepository->expects($this->once())->method('insert')->with($page)->willReturn(1);

        $this->assertEquals(1, $this->customPage->create($page));
    }

    public function testUpdate(): void
    {
        $page = new CustomPageEntity();
        $page
            ->setId(1)
            ->setLanguage('en')
            ->setPageTitle('Updated Page')
            ->setSlug('updated-page')
            ->setContent('<p>Updated content</p>')
            ->setAuthorName('Test Author')
            ->setAuthorEmail('test@example.org')
            ->setActive(true)
            ->setCreated(new DateTime());

        $this->mockRepository->expects($this->once())->method('update')->with($page)->willReturn(true);

        $this->assertTrue($this->customPage->update($page));
        $this->assertNotNull($page->getUpdated());
    }

    public function testGetById(): void
    {
        $mockData = new stdClass();
        $mockData->id = 1;
        $mockData->lang = 'en';
        $mockData->page_title = 'Test Page';
        $mockData->slug = 'test-page';
        $mockData->content = '<p>Test content</p>';
        $mockData->author_name = 'Test Author';
        $mockData->author_email = 'test@example.org';
        $mockData->active = 'y';
        $mockData->created = '2026-01-12 12:00:00';
        $mockData->updated = null;
        $mockData->seo_title = null;
        $mockData->seo_description = null;
        $mockData->seo_robots = 'index,follow';

        $this->mockRepository->expects($this->once())->method('getById')->with(1, 'en')->willReturn($mockData);

        $result = $this->customPage->getById(1);
        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals('Test Page', $result->getPageTitle());
    }

    public function testGetByIdReturnsNull(): void
    {
        $this->mockRepository->expects($this->once())->method('getById')->with(999, 'en')->willReturn(null);

        $result = $this->customPage->getById(999);
        $this->assertNull($result);
    }

    public function testGetBySlug(): void
    {
        $mockData = new stdClass();
        $mockData->id = 1;
        $mockData->lang = 'en';
        $mockData->page_title = 'Test Page';
        $mockData->slug = 'test-slug';
        $mockData->content = '<p>Test content</p>';
        $mockData->author_name = 'Test Author';
        $mockData->author_email = 'test@example.org';
        $mockData->active = 'y';
        $mockData->created = '2026-01-12 12:00:00';
        $mockData->updated = null;
        $mockData->seo_title = null;
        $mockData->seo_description = null;
        $mockData->seo_robots = 'index,follow';

        $this->mockRepository
            ->expects($this->once())
            ->method('getBySlug')
            ->with('test-slug', 'en')
            ->willReturn($mockData);

        $result = $this->customPage->getBySlug('test-slug');
        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals('test-slug', $result->getSlug());
    }

    public function testDelete(): void
    {
        $this->mockRepository->expects($this->once())->method('delete')->with(1, 'en')->willReturn(true);

        $this->assertTrue($this->customPage->delete(1));
    }

    public function testActivate(): void
    {
        $this->mockRepository->expects($this->once())->method('activate')->with(1, true)->willReturn(true);

        $this->assertTrue($this->customPage->activate(1, true));
    }

    public function testGetAllPages(): void
    {
        $mockData1 = new stdClass();
        $mockData1->id = 1;
        $mockData1->lang = 'en';
        $mockData1->page_title = 'Page 1';
        $mockData1->slug = 'page-1';
        $mockData1->content = '<p>Content 1</p>';
        $mockData1->author_name = 'Author 1';
        $mockData1->author_email = 'author1@example.org';
        $mockData1->active = 'y';
        $mockData1->created = '2026-01-12 12:00:00';
        $mockData1->updated = null;
        $mockData1->seo_title = null;
        $mockData1->seo_description = null;
        $mockData1->seo_robots = 'index,follow';

        $mockData2 = new stdClass();
        $mockData2->id = 2;
        $mockData2->lang = 'en';
        $mockData2->page_title = 'Page 2';
        $mockData2->slug = 'page-2';
        $mockData2->content = '<p>Content 2</p>';
        $mockData2->author_name = 'Author 2';
        $mockData2->author_email = 'author2@example.org';
        $mockData2->active = 'n';
        $mockData2->created = '2026-01-12 13:00:00';
        $mockData2->updated = null;
        $mockData2->seo_title = null;
        $mockData2->seo_description = null;
        $mockData2->seo_robots = 'index,follow';

        $this->mockRepository
            ->expects($this->once())
            ->method('getAll')
            ->with('en', false)
            ->willReturn([$mockData1, $mockData2]);

        $result = $this->customPage->getAllPages(false);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Page 1', $result[0]['page_title']);
        $this->assertEquals('Page 2', $result[1]['page_title']);
    }

    public function testGetPagesPaginated(): void
    {
        $mockData = new stdClass();
        $mockData->id = 1;
        $mockData->lang = 'en';
        $mockData->page_title = 'Paginated Page';
        $mockData->slug = 'paginated-page';
        $mockData->content = '<p>Paginated content</p>';
        $mockData->author_name = 'Author';
        $mockData->author_email = 'author@example.org';
        $mockData->active = 'y';
        $mockData->created = '2026-01-12 12:00:00';
        $mockData->updated = null;
        $mockData->seo_title = null;
        $mockData->seo_description = null;
        $mockData->seo_robots = 'index,follow';

        $this->mockRepository
            ->expects($this->once())
            ->method('getAllPaginated')
            ->with('en', false, 10, 0, 'created', 'DESC')
            ->willReturn([$mockData]);

        $result = $this->customPage->getPagesPaginated(false, 10, 0, 'created', 'DESC');
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testCountPages(): void
    {
        $this->mockRepository->expects($this->once())->method('countAll')->with('en', false)->willReturn(5);

        $count = $this->customPage->countPages(false);
        $this->assertEquals(5, $count);
    }

    public function testGetAllLanguagesPaginated(): void
    {
        $mockData = new stdClass();
        $mockData->id = 10;
        $mockData->lang = 'de';
        $mockData->page_title = 'All Languages Page';
        $mockData->slug = 'all-languages-page';
        $mockData->content = '<p>Paginated content</p>';
        $mockData->author_name = 'Author';
        $mockData->author_email = 'author@example.org';
        $mockData->active = 'y';
        $mockData->created = '2026-01-12 12:00:00';
        $mockData->updated = null;
        $mockData->seo_title = null;
        $mockData->seo_description = null;
        $mockData->seo_robots = 'index,follow';

        $this->mockRepository
            ->expects($this->once())
            ->method('getAllLanguagesPaginated')
            ->with(false, 25, 0, 'created', 'DESC')
            ->willReturn([$mockData]);

        $result = $this->customPage->getAllLanguagesPaginated();

        $this->assertCount(1, $result);
        $this->assertSame('All Languages Page', $result[0]['page_title']);
        $this->assertSame('de', $result[0]['lang']);
    }

    public function testCountAllLanguages(): void
    {
        $this->mockRepository->expects($this->once())->method('countAllLanguages')->with(true)->willReturn(3);

        $this->assertSame(3, $this->customPage->countAllLanguages(true));
    }

    public function testGetExistingLanguages(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('getExistingLanguages')
            ->with(23)
            ->willReturn(['de', 'en', 'fr']);

        $this->assertSame(['de', 'en', 'fr'], $this->customPage->getExistingLanguages(23));
    }

    public function testSlugExists(): void
    {
        $this->mockRepository
            ->expects($this->exactly(2))
            ->method('slugExists')
            ->willReturnMap([
                ['existing-slug', 'en', null, true],
                ['new-slug',      'en', null, false],
            ]);

        $this->assertTrue($this->customPage->slugExists('existing-slug'));
        $this->assertFalse($this->customPage->slugExists('new-slug'));
    }

    public function testGenerateUniqueSlug(): void
    {
        $this->mockRepository
            ->expects($this->exactly(3))
            ->method('slugExists')
            ->willReturnMap([
                ['test-slug',   'en', null, true],
                ['test-slug-1', 'en', null, true],
                ['test-slug-2', 'en', null, false],
            ]);

        $uniqueSlug = $this->customPage->generateUniqueSlug('test-slug');
        $this->assertEquals('test-slug-2', $uniqueSlug);
    }

    public function testGenerateUniqueSlugWhenNotExists(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('slugExists')
            ->with('unique-slug', 'en', null)
            ->willReturn(false);

        $uniqueSlug = $this->customPage->generateUniqueSlug('unique-slug');
        $this->assertEquals('unique-slug', $uniqueSlug);
    }

    public function testGenerateUniqueSlugWithExcludeId(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('slugExists')
            ->with('test-slug', 'en', 123)
            ->willReturn(false);

        $uniqueSlug = $this->customPage->generateUniqueSlug('test-slug', null, 123);
        $this->assertEquals('test-slug', $uniqueSlug);
    }

    public function testCreateTranslation(): void
    {
        $page = new CustomPageEntity();
        $page
            ->setLanguage('de')
            ->setPageTitle('Translated Page')
            ->setSlug('translated-page')
            ->setContent('<p>Translated content</p>')
            ->setAuthorName('Test Author')
            ->setAuthorEmail('test@example.org')
            ->setActive(true)
            ->setCreated(new DateTime());

        $this->mockRepository->expects($this->once())->method('insertTranslation')->with($page, 11)->willReturn(true);

        $this->assertTrue($this->customPage->createTranslation($page, 11));
    }

    public function testGetByIdWithSeoFields(): void
    {
        $mockData = new stdClass();
        $mockData->id = 1;
        $mockData->lang = 'en';
        $mockData->page_title = 'Test Page';
        $mockData->slug = 'test-page';
        $mockData->content = '<p>Test content</p>';
        $mockData->author_name = 'Test Author';
        $mockData->author_email = 'test@example.org';
        $mockData->active = 'y';
        $mockData->created = '2026-01-12 12:00:00';
        $mockData->updated = null;
        $mockData->seo_title = 'SEO Test Title';
        $mockData->seo_description = 'SEO Test Description';
        $mockData->seo_robots = 'noindex,nofollow';

        $this->mockRepository->expects($this->once())->method('getById')->with(1, 'en')->willReturn($mockData);

        $result = $this->customPage->getById(1);
        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals('SEO Test Title', $result->getSeoTitle());
        $this->assertEquals('SEO Test Description', $result->getSeoDescription());
        $this->assertEquals('noindex,nofollow', $result->getSeoRobots());
    }

    public function testGetByIdMapsUpdatedTimestamp(): void
    {
        $mockData = new stdClass();
        $mockData->id = 2;
        $mockData->lang = 'en';
        $mockData->page_title = 'Updated Page';
        $mockData->slug = 'updated-page';
        $mockData->content = '<p>Updated content</p>';
        $mockData->author_name = 'Test Author';
        $mockData->author_email = 'test@example.org';
        $mockData->active = 'y';
        $mockData->created = '2026-01-12 12:00:00';
        $mockData->updated = '2026-01-13 09:30:00';
        $mockData->seo_title = null;
        $mockData->seo_description = null;
        $mockData->seo_robots = 'index,follow';

        $this->mockRepository->expects($this->once())->method('getById')->with(2, 'en')->willReturn($mockData);

        $result = $this->customPage->getById(2);

        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertNotNull($result->getUpdated());
        $this->assertSame('2026-01-13 09:30:00', $result->getUpdated()?->format('Y-m-d H:i:s'));
    }
}
