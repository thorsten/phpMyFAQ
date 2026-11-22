<?php

namespace phpMyFAQ;

use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class SitemapTest extends TestCase
{
    private Sitemap $sitemap;

    private PdoSqlite $db;

    private Configuration $configuration;

    /**
     * @throws Exception
     * @throws Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTP_HOST'] = 'example.com';

        $dbConfig = new DatabaseConfiguration(PMF_TEST_DIR . '/content/core/config/database.php');
        Database::setTablePrefix($dbConfig->getPrefix());
        $this->db = Database::factory($dbConfig->getType());
        $this->db->connect(
            $dbConfig->getServer(),
            $dbConfig->getUser(),
            $dbConfig->getPassword(),
            $dbConfig->getDatabase(),
            $dbConfig->getPort(),
        );
        $this->configuration = new Configuration($this->db);
        $this->configuration->set('main.referenceURL', 'https://example.com/');
        $this->configuration->set('security.permLevel', 'medium');

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->sitemap = new Sitemap($this->configuration);

        $this->db->query(
            'INSERT INTO faqdata '
            . '(id, lang, solution_id, sticky, thema, content, keywords, active, author, email, updated) VALUES '
            . '(1, \'en\', 1000, \'yes\', \'sample question\', \'sample answer\', \'sample keywords\', \'yes\', \'Author\', \'test@example.org\', \'date\')',
        );
        $this->db->query('INSERT INTO faqdata_group (record_id, group_id) VALUES (1,-1)');
        $this->db->query('INSERT INTO faqdata_user (record_id, user_id) VALUES (1,-1)');
    }

    protected function tearDown(): void
    {
        $this->db->query('DELETE FROM faqdata WHERE id = 1');
        $this->db->query('DELETE FROM faqdata_group WHERE record_id = 1');
        $this->db->query('DELETE FROM faqdata_user WHERE record_id = 1');
        $this->configuration->set('security.permLevel', 'basic');
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function testGetAllFirstLetters(): void
    {
        $letters = $this->sitemap->getAllFirstLetters();
        $expected = new stdClass();
        $expected->letter = 'S';
        $expected->url = 'https://example.com/sitemap/S/en.html';
        $this->assertIsArray($letters);
        $this->assertEquals([$expected], $letters);
    }

    public function testGetAllFirstLettersNormalizesGroupIds(): void
    {
        $this->sitemap->setUser(-1);
        $this->sitemap->setGroups(['-1) OR 1=1 -- ', '2']);

        $letters = $this->sitemap->getAllFirstLetters();

        $this->assertIsArray($letters);
        $this->assertStringContainsString('fdg.group_id IN (-1, 2)', $this->db->log());
        $this->assertStringNotContainsString('OR 1=1', $this->db->log());
    }

    public function testSitemapHidesScheduledAndExpiredFaqs(): void
    {
        $this->db->query(
            'INSERT INTO faqdata '
            . '(id, lang, solution_id, sticky, thema, content, keywords, active, author, email, updated, date_start, date_end) VALUES '
            . "(9101, 'en', 9101, 0, 'Future question', 'embargoed answer', '', 'yes', 'Author', 'test@example.org', 'date', '99990101000000', '99991231235959'), "
            . "(9102, 'en', 9102, 0, 'Expired question', 'withdrawn answer', '', 'yes', 'Author', 'test@example.org', 'date', '00000000000000', '20200101000000')",
        );
        $this->db->query('INSERT INTO faqdata_group (record_id, group_id) VALUES (9101,-1), (9102,-1)');
        $this->db->query('INSERT INTO faqdata_user (record_id, user_id) VALUES (9101,-1), (9102,-1)');
        $this->db->query(
            "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (1, 'en', 9101, 'en'), (1, 'en', 9102, 'en')",
        );

        try {
            $letters = array_map(static fn(stdClass $letter): string => $letter->letter, $this->sitemap->getAllFirstLetters());
            $future = $this->sitemap->getFaqsFromLetter('F');
            $expired = $this->sitemap->getFaqsFromLetter('E');
        } finally {
            $this->db->query('DELETE FROM faqdata WHERE id IN (9101, 9102)');
            $this->db->query('DELETE FROM faqdata_group WHERE record_id IN (9101, 9102)');
            $this->db->query('DELETE FROM faqdata_user WHERE record_id IN (9101, 9102)');
            $this->db->query('DELETE FROM faqcategoryrelations WHERE record_id IN (9101, 9102)');
        }

        $this->assertSame(['S'], $letters);
        $this->assertSame([], $future);
        $this->assertSame([], $expired);
    }
}
