<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Search\SearchFactory;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use stdClass;

class RelationTest extends TestCase
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testGetAllRelatedByQuestion(): void
    {
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $language = new Language($configuration);
        $language->setLanguage(false, 'en');
        $configuration->setLanguage($language);

        $dbHandle->query(
            'INSERT INTO faqdata ' .
            '(id, lang, solution_id, sticky, thema, content, keywords, active, author, email, updated) VALUES ' .
            '(1, \'en\', 1000, \'yes\', \'sample question\', \'sample answer\', \'sample keywords\', \'yes\', \'Author\', \'test@example.org\', \'date\')'
        );
        $dbHandle->query(
            'INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (1, \'en\', 1, \'en\')'
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
