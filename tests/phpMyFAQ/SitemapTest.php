<?php

namespace phpMyFAQ;

use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Database\PdoSqlite;use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Session\Session;

class SitemapTest extends TestCase
{
    private Sitemap $sitemap;

    private PdoSqlite $db;

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
            $dbConfig->getPort()
        );
        $configuration = new Configuration($this->db);
        $configuration->set('main.referenceURL', 'https://example.com/');

        $language = new Language($configuration, $this->createMock(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->sitemap = new Sitemap($configuration);

        $this->db->query(
            'INSERT INTO faqdata ' .
            '(id, lang, solution_id, sticky, thema, content, keywords, active, author, email, updated) VALUES ' .
            '(1, \'en\', 1000, \'yes\', \'sample question\', \'sample answer\', \'sample keywords\', \'yes\', \'Author\', \'test@example.org\', \'date\')'
        );
        $this->db->query('INSERT INTO faqdata_group (record_id, group_id) VALUES (1,-1)');
        $this->db->query('INSERT INTO faqdata_user (record_id, user_id) VALUES (1,-1)');
    }

    protected function tearDown(): void
    {
        $this->db->query('DELETE FROM faqdata WHERE id = 1');
        $this->db->query('DELETE FROM faqdata_group WHERE record_id = 1');
        $this->db->query('DELETE FROM faqdata_user WHERE record_id = 1');
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
}
