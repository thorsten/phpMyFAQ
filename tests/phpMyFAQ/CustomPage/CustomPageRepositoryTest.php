<?php

declare(strict_types=1);

namespace phpMyFAQ\CustomPage\Test;

use DateTime;
use phpMyFAQ\Configuration;
use phpMyFAQ\CustomPage\CustomPageRepository;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\CustomPageEntity;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CustomPageRepositoryTest extends TestCase
{
    private Configuration $configuration;
    private CustomPageRepository $repository;

    protected function setUp(): void
    {
        Strings::init();
        $db = new Sqlite3();
        $db->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($db);
        $this->repository = new CustomPageRepository($this->configuration);
    }

    public function testInsertAndFetchById(): void
    {
        $page = new CustomPageEntity();
        $page
            ->setLanguage('en')
            ->setPageTitle('Unit Test Page')
            ->setSlug('unit-test-page')
            ->setContent('<p>Unit test content</p>')
            ->setAuthorName('Test Author')
            ->setAuthorEmail('test@example.com')
            ->setActive(true)
            ->setCreated(new DateTime());

        $pageId = $this->repository->insert($page);
        $this->assertGreaterThan(0, $pageId);
        $this->assertEquals($pageId, $page->getId());

        $fetched = $this->repository->getById($pageId, 'en');
        $this->assertNotNull($fetched);
        $this->assertEquals('Unit Test Page', $fetched->page_title);
        $this->assertEquals('unit-test-page', $fetched->slug);
        $this->assertEquals('<p>Unit test content</p>', $fetched->content);
    }

    public function testFetchBySlug(): void
    {
        $page = new CustomPageEntity();
        $page
            ->setLanguage('en')
            ->setPageTitle('Test Slug Page')
            ->setSlug('test-slug-page')
            ->setContent('<p>Content for slug test</p>')
            ->setAuthorName('Slug Tester')
            ->setAuthorEmail('slug@example.com')
            ->setActive(true)
            ->setCreated(new DateTime());

        $this->repository->insert($page);

        $fetched = $this->repository->getBySlug('test-slug-page', 'en');
        $this->assertNotNull($fetched);
        $this->assertEquals('Test Slug Page', $fetched->page_title);
    }

    public function testUpdate(): void
    {
        $page = new CustomPageEntity();
        $page
            ->setLanguage('en')
            ->setPageTitle('Original Title')
            ->setSlug('original-slug')
            ->setContent('<p>Original content</p>')
            ->setAuthorName('Original Author')
            ->setAuthorEmail('original@example.com')
            ->setActive(false)
            ->setCreated(new DateTime());

        $pageId = $this->repository->insert($page);

        $page
            ->setId($pageId)
            ->setPageTitle('Updated Title')
            ->setContent('<p>Updated content</p>')
            ->setActive(true)
            ->setUpdated(new DateTime());

        $this->assertTrue($this->repository->update($page));

        $fetched = $this->repository->getById($pageId, 'en');
        $this->assertEquals('Updated Title', $fetched->page_title);
        $this->assertEquals('<p>Updated content</p>', $fetched->content);
        $this->assertEquals('y', $fetched->active);
        $this->assertNotNull($fetched->updated);
    }

    public function testActivate(): void
    {
        $page = new CustomPageEntity();
        $page
            ->setLanguage('en')
            ->setPageTitle('Activation Test')
            ->setSlug('activation-test')
            ->setContent('<p>Test content</p>')
            ->setAuthorName('Test')
            ->setAuthorEmail('test@example.com')
            ->setActive(false)
            ->setCreated(new DateTime());

        $pageId = $this->repository->insert($page);

        $this->assertTrue($this->repository->activate($pageId, true));
        $fetched = $this->repository->getById($pageId, 'en');
        $this->assertEquals('y', $fetched->active);

        $this->assertTrue($this->repository->activate($pageId, false));
        $fetched = $this->repository->getById($pageId, 'en');
        $this->assertEquals('n', $fetched->active);
    }

    public function testDelete(): void
    {
        $page = new CustomPageEntity();
        $page
            ->setLanguage('en')
            ->setPageTitle('Delete Test')
            ->setSlug('delete-test')
            ->setContent('<p>Will be deleted</p>')
            ->setAuthorName('Test')
            ->setAuthorEmail('test@example.com')
            ->setActive(true)
            ->setCreated(new DateTime());

        $pageId = $this->repository->insert($page);
        $this->assertNotNull($this->repository->getById($pageId, 'en'));

        $this->assertTrue($this->repository->delete($pageId, 'en'));
        $this->assertNull($this->repository->getById($pageId, 'en'));
    }

    public function testGetAll(): void
    {
        // Insert test pages
        for ($i = 1; $i <= 3; $i++) {
            $page = new CustomPageEntity();
            $page
                ->setLanguage('en')
                ->setPageTitle("Page $i")
                ->setSlug("page-$i")
                ->setContent("<p>Content $i</p>")
                ->setAuthorName('Test')
                ->setAuthorEmail('test@example.com')
                ->setActive($i % 2 === 1)
                ->setCreated(new DateTime());

            $this->repository->insert($page);
        }

        $allPages = iterator_to_array($this->repository->getAll('en', false));
        $this->assertGreaterThanOrEqual(3, count($allPages));

        $activePages = iterator_to_array($this->repository->getAll('en', true));
        foreach ($activePages as $page) {
            $this->assertEquals('y', $page->active);
        }
    }

    public function testGetAllPaginated(): void
    {
        $pages = iterator_to_array($this->repository->getAllPaginated(
            'en',
            false,
            2,
            0,
            'page_title',
            'ASC'
        ));

        $this->assertLessThanOrEqual(2, count($pages));
    }

    public function testCountAll(): void
    {
        $count = $this->repository->countAll('en', false);
        $this->assertGreaterThanOrEqual(0, $count);

        $activeCount = $this->repository->countAll('en', true);
        $this->assertGreaterThanOrEqual(0, $activeCount);
        $this->assertLessThanOrEqual($count, $activeCount);
    }

    public function testSlugExists(): void
    {
        $page = new CustomPageEntity();
        $page
            ->setLanguage('en')
            ->setPageTitle('Slug Exists Test')
            ->setSlug('unique-slug-test')
            ->setContent('<p>Test content</p>')
            ->setAuthorName('Test')
            ->setAuthorEmail('test@example.com')
            ->setActive(true)
            ->setCreated(new DateTime());

        $pageId = $this->repository->insert($page);

        $this->assertTrue($this->repository->slugExists('unique-slug-test', 'en'));
        $this->assertFalse($this->repository->slugExists('non-existent-slug', 'en'));

        // Test with excludeId
        $this->assertFalse($this->repository->slugExists('unique-slug-test', 'en', $pageId));
    }

    public function testMultiLanguageSupport(): void
    {
        // Test that same slug can exist in different languages
        $pageEn = new CustomPageEntity();
        $pageEn
            ->setLanguage('en')
            ->setPageTitle('English Page')
            ->setSlug('multilang-page')
            ->setContent('<p>English content</p>')
            ->setAuthorName('Test')
            ->setAuthorEmail('test@example.com')
            ->setActive(true)
            ->setCreated(new DateTime());

        $pageIdEn = $this->repository->insert($pageEn);
        $this->assertGreaterThan(0, $pageIdEn);

        $pageDe = new CustomPageEntity();
        $pageDe
            ->setLanguage('de')
            ->setPageTitle('German Page')
            ->setSlug('multilang-page') // Same slug, different language
            ->setContent('<p>German content</p>')
            ->setAuthorName('Test')
            ->setAuthorEmail('test@example.com')
            ->setActive(true)
            ->setCreated(new DateTime());

        $pageIdDe = $this->repository->insert($pageDe);
        $this->assertGreaterThan(0, $pageIdDe);

        // Fetch both language versions
        $fetchedEn = $this->repository->getBySlug('multilang-page', 'en');
        $fetchedDe = $this->repository->getBySlug('multilang-page', 'de');

        $this->assertNotNull($fetchedEn);
        $this->assertNotNull($fetchedDe);
        $this->assertEquals('English Page', $fetchedEn->page_title);
        $this->assertEquals('German Page', $fetchedDe->page_title);

        // Test slug uniqueness per language
        $this->assertTrue($this->repository->slugExists('multilang-page', 'en'));
        $this->assertTrue($this->repository->slugExists('multilang-page', 'de'));

        // Same slug should not exist in French
        $this->assertFalse($this->repository->slugExists('multilang-page', 'fr'));
    }

    public function testSortFieldWhitelist(): void
    {
        // Valid sort fields should work
        $pages = iterator_to_array($this->repository->getAllPaginated(
            'en',
            false,
            10,
            0,
            'page_title',
            'ASC'
        ));
        $this->assertIsArray($pages);

        // Invalid sort field should default to 'created'
        $pages = iterator_to_array($this->repository->getAllPaginated(
            'en',
            false,
            10,
            0,
            'invalid_field',
            'ASC'
        ));
        $this->assertIsArray($pages);
    }

    public function testSortOrderValidation(): void
    {
        // Valid sort orders
        $pagesAsc = iterator_to_array($this->repository->getAllPaginated(
            'en',
            false,
            10,
            0,
            'page_title',
            'ASC'
        ));
        $this->assertIsArray($pagesAsc);

        $pagesDesc = iterator_to_array($this->repository->getAllPaginated(
            'en',
            false,
            10,
            0,
            'page_title',
            'DESC'
        ));
        $this->assertIsArray($pagesDesc);

        // Invalid sort order should default to 'DESC'
        $pages = iterator_to_array($this->repository->getAllPaginated(
            'en',
            false,
            10,
            0,
            'page_title',
            'INVALID'
        ));
        $this->assertIsArray($pages);
    }

    public function testInsertAndFetchWithSeoFields(): void
    {
        $page = new CustomPageEntity();
        $page
            ->setLanguage('en')
            ->setPageTitle('SEO Test Page')
            ->setSlug('seo-test-page')
            ->setContent('<p>Content with SEO</p>')
            ->setAuthorName('SEO Author')
            ->setAuthorEmail('seo@example.com')
            ->setActive(true)
            ->setSeoTitle('Custom SEO Title')
            ->setSeoDescription('Custom SEO Description for testing')
            ->setSeoRobots('noindex,follow')
            ->setCreated(new DateTime());

        $pageId = $this->repository->insert($page);
        $this->assertGreaterThan(0, $pageId);

        $fetched = $this->repository->getById($pageId, 'en');
        $this->assertNotNull($fetched);
        $this->assertEquals('Custom SEO Title', $fetched->seo_title);
        $this->assertEquals('Custom SEO Description for testing', $fetched->seo_description);
        $this->assertEquals('noindex,follow', $fetched->seo_robots);
    }

    public function testUpdateWithSeoFields(): void
    {
        $page = new CustomPageEntity();
        $page
            ->setLanguage('en')
            ->setPageTitle('Update SEO Test')
            ->setSlug('update-seo-test')
            ->setContent('<p>Content</p>')
            ->setAuthorName('Author')
            ->setAuthorEmail('author@example.com')
            ->setActive(true)
            ->setCreated(new DateTime());

        $pageId = $this->repository->insert($page);

        $page
            ->setId($pageId)
            ->setSeoTitle('Updated SEO Title')
            ->setSeoDescription('Updated SEO Description')
            ->setSeoRobots('index,nofollow')
            ->setUpdated(new DateTime());

        $this->assertTrue($this->repository->update($page));

        $fetched = $this->repository->getById($pageId, 'en');
        $this->assertEquals('Updated SEO Title', $fetched->seo_title);
        $this->assertEquals('Updated SEO Description', $fetched->seo_description);
        $this->assertEquals('index,nofollow', $fetched->seo_robots);
    }
}
