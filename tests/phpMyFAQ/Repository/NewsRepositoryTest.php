<?php

declare(strict_types=1);

namespace phpMyFAQ\News\Test;

use DateTime;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\NewsMessage;
use phpMyFAQ\News\NewsRepository;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

final class NewsRepositoryTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        Strings::init();
        $db = new Sqlite3();
        $db->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($db);
    }

    public function testInsertAndFetchById(): void
    {
        $repo = new NewsRepository($this->configuration);

        $news = (new NewsMessage())
            ->setId(0)
            ->setLanguage('en')
            ->setHeader('Unit Test Header')
            ->setMessage('Unit Test Message')
            ->setCreated(new DateTime('2025-01-01T12:00:00Z'))
            ->setAuthor('Tester')
            ->setEmail('tester@example.com')
            ->setActive(true)
            ->setComment(true)
            ->setLink('')
            ->setLinkTitle('')
            ->setLinkTarget('');

        $this->assertTrue($repo->insert($news));

        // fetch the latest and take first id
        $rows = iterator_to_array($repo->getLatest('en', active: true, limit: 1));
        $this->assertNotEmpty($rows);
        $row = $rows[0];
        $this->assertSame('Unit Test Header', $row->header);

        $fetched = $repo->getById((int) $row->id, 'en');
        $this->assertNotNull($fetched);
        $this->assertSame('Unit Test Message', $fetched->artikel);
    }

    public function testActivateAndDelete(): void
    {
        $repo = new NewsRepository($this->configuration);
        $rows = iterator_to_array($repo->getLatest('en', active: true, limit: 1));
        if (empty($rows)) {
            $this->markTestSkipped('No news to activate/delete.');
        }
        $id = (int) $rows[0]->id;

        $this->assertTrue($repo->activate($id, false));
        $row = $repo->getById($id, 'en');
        $this->assertSame('n', $row->active);

        $this->assertTrue($repo->delete($id, 'en'));
        $this->assertNull($repo->getById($id, 'en'));
    }
}

