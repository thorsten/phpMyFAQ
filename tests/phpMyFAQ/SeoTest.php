<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\SeoType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SeoTest extends TestCase
{
    private Seo $seo;
    
    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $configuration->set('main.currentVersion', System::getVersion());
        $configuration->set('seo.metaTagsHome', 'index,follow');
        $configuration->set('seo.metaTagsFaqs', 'noindex,nofollow');
        $configuration->set('seo.metaTagsCategories', 'noarchive');
        $configuration->set('seo.metaTagsPages', 'none');

        $this->seo = new Seo($configuration);
    }

    public function testCreate(): void
    {
        $seo = new SeoEntity();
        $seo->setSeoType(SeoType::FAQ)
            ->setReferenceId(1)
            ->setReferenceLanguage('en')
            ->setTitle('Test Title')
            ->setDescription('Test Description');

        $result = $this->seo->create($seo);

        $this->assertTrue($result);
    }

    public function testGet(): void
    {
        $seo = new SeoEntity();
        $seo->setSeoType(SeoType::FAQ)
            ->setReferenceId(1)
            ->setReferenceLanguage('en')
            ->setTitle('Test Title')
            ->setDescription('Test Description');
        $this->seo->create($seo);

        $result = $this->seo->get($seo);

        $this->assertInstanceOf(SeoEntity::class, $result);
        $this->assertEquals('Test Title', $result->getTitle());
    }

    public function testUpdate(): void
    {
        $seo = new SeoEntity();
        $seo->setSeoType(SeoType::FAQ)
            ->setReferenceId(1)
            ->setReferenceLanguage('en')
            ->setTitle('Test Title')
            ->setDescription('Test Description');
        $this->seo->create($seo);

        $seo->setSeoType(SeoType::FAQ)
            ->setReferenceId(1)
            ->setReferenceLanguage('en')
            ->setTitle('Updated Title')
            ->setDescription('Updated Description');

        $result = $this->seo->update($seo);

        $this->assertTrue($result);
        $this->assertEquals('Updated Title', $seo->getTitle());
    }

    public function testDelete(): void
    {
        $seo = new SeoEntity();
        $seo->setSeoType(SeoType::FAQ)
            ->setReferenceId(1)
            ->setReferenceLanguage('en')
            ->setTitle('Test Title')
            ->setDescription('Test Description');
        $this->seo->create($seo);

        $result = $this->seo->delete($seo);

        $this->assertTrue($result);
    }

    public function testGetMetaRobots(): void
    {
        $this->assertSame('index,follow', $this->seo->getMetaRobots('main'));
        $this->assertSame('noindex,nofollow', $this->seo->getMetaRobots('faq'));
        $this->assertSame('noarchive', $this->seo->getMetaRobots('show'));
        $this->assertSame('none', $this->seo->getMetaRobots('unknown'));
    }
}
