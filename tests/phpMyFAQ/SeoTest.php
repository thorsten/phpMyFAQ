<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

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
    public function testGetMetaRobots()
    {
        $this->assertSame('index,follow', $this->seo->getMetaRobots('main'));
        $this->assertSame('noindex,nofollow', $this->seo->getMetaRobots('faq'));
        $this->assertSame('noarchive', $this->seo->getMetaRobots('show'));
        $this->assertSame('none', $this->seo->getMetaRobots('unknown'));
    }
}
