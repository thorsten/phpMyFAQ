<?php

namespace phpMyFAQ;

use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class RelationTest extends TestCase
{
    private PdoSqlite $db;
    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-relation-test-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        @unlink($this->databaseFile);

        parent::tearDown();
    }

    /**
     * @throws \Exception
     * @throws Exception
     */
    public function testGetAllRelatedByQuestion(): void
    {
        $faqId = 999901;
        $categoryId = 999902;

        $dbConfig = new DatabaseConfiguration(PMF_TEST_DIR . '/content/core/config/database.php');
        Database::setTablePrefix($dbConfig->getPrefix());
        $this->db = new PdoSqlite();
        $this->db->connect($this->databaseFile, '', '');
        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->db);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'pdo_sqlite');
        $configuration = new Configuration($this->db);
        $configuration->set('search.enableRelevance', false);

        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->db->query(
            'INSERT INTO faqdata '
                . '(id, lang, solution_id, sticky, thema, content, keywords, active, author, email, updated) VALUES '
                . sprintf(
                    '(%d, \'en\', 1000, \'yes\', \'sample question\', \'sample answer\', \'sample keywords\', \'yes\', \'Author\', \'test@example.org\', \'date\')',
                    $faqId,
                ),
        );
        $this->db->query(sprintf(
            'INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (%d, \'en\', %d, \'en\')',
            $categoryId,
            $faqId,
        ));

        $relation = new Relation($configuration);

        $relatedArticles = $relation->getAllRelatedByQuestion('sample question', 'sample keywords');

        $expected = new stdClass();
        $expected->id = $faqId;
        $expected->lang = 'en';
        $expected->category_id = $categoryId;
        $expected->question = 'sample question';
        $expected->answer = 'sample answer';
        $expected->keywords = 'sample keywords';

        $this->assertNotEmpty($relatedArticles);
        $this->assertContainsEquals($expected, $relatedArticles);
    }
}
