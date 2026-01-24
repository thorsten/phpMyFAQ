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
class RelationTest extends TestCase
{
    private PdoSqlite $db;

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->db->query('DELETE FROM faqdata WHERE id = 1');
        $this->db->query('DELETE FROM faqcategoryrelations WHERE category_id = 1');
    }

    /**
     * @throws \Exception
     * @throws Exception
     */
    public function testGetAllRelatedByQuestion(): void
    {
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
        $configuration = new Configuration($this->db);
        $configuration->set('search.enableRelevance', false);

        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->db->query(
            'INSERT INTO faqdata '
            . '(id, lang, solution_id, sticky, thema, content, keywords, active, author, email, updated) VALUES '
            . '(1, \'en\', 1000, \'yes\', \'sample question\', \'sample answer\', \'sample keywords\', \'yes\', \'Author\', \'test@example.org\', \'date\')',
        );
        $this->db->query(
            'INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (1, \'en\', 1, \'en\')',
        );

        $relation = new Relation($configuration);

        $relatedArticles = $relation->getAllRelatedByQuestion('sample question', 'sample keywords');

        $expected = new stdClass();
        $expected->id = 1;
        $expected->lang = 'en';
        $expected->category_id = 1;
        $expected->question = 'sample question';
        $expected->answer = 'sample answer';
        $expected->keywords = 'sample keywords';

        $this->assertEquals([$expected], $relatedArticles);
    }
}
