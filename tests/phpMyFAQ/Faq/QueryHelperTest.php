<?php

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class QueryHelperTest extends TestCase
{
    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configuration = Configuration::getConfigurationInstance();
        $language = new Language($configuration, $this->createMock(Session::class));
        $language->setLanguage(true, 'language_en.php');

        $configuration->setLanguage($language);
    }

    public function testQueryPermissionWithoutGroupSupport(): void
    {
        $user = 42;
        $groups = [1, 2, 3];
        $queryHelper = new QueryHelper($user, $groups);

        $expectedQuery = 'AND ( fdu.user_id = 42 OR fdu.user_id = -1 )';
        $this->assertEquals($expectedQuery, $queryHelper->queryPermission());
    }

    public function testQueryPermissionWithGroupSupport(): void
    {
        $user = 42;
        $groups = [1, 2, 3];
        $queryHelper = new QueryHelper($user, $groups);

        $expectedQuery = 'AND ( fdu.user_id = 42 OR fdg.group_id IN (1, 2, 3) )';
        $this->assertEquals($expectedQuery, $queryHelper->queryPermission(true));
    }

    public function testQueryPermissionWithNegativeUserAndGroupSupport(): void
    {
        $user = -1;
        $groups = [1, 2, 3];
        $queryHelper = new QueryHelper($user, $groups);

        $expectedQuery = 'AND fdg.group_id IN (1, 2, 3)';
        $this->assertEquals($expectedQuery, $queryHelper->queryPermission(true));
    }

    public function testQueryPermissionWithNegativeUserAndNoGroupSupport(): void
    {
        $user = -1;
        $groups = [1, 2, 3];
        $queryHelper = new QueryHelper($user, $groups);

        $expectedQuery = 'AND fdu.user_id = -1';
        $this->assertEquals($expectedQuery, $queryHelper->queryPermission());
    }

    public function testGetQuery(): void
    {
        $user = -1;
        $groups = [1, 2, 3];
        $queryHelper = new QueryHelper($user, $groups);

        // Define the input values
        $queryType = 'export_pdf';
        $categoryId = 1;
        $bDownwards = true;
        $lang = 'en';
        $date = '2022-01-01';
        $faqId = 0;

        // Call the method under test
        $result = $queryHelper->getQuery($queryType, $categoryId, $bDownwards, $lang, $date, $faqId);

        // Define the expected SQL query string
        $expectedQuery = "
            SELECT
                fd.id AS id,
                fd.solution_id AS solution_id,
                fd.revision_id AS revision_id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                fd.active AS active,
                fd.sticky AS sticky,
                fd.keywords AS keywords,
                fd.thema AS thema,
                fd.content AS content,
                fd.author AS author,
                fd.email AS email,
                fd.comment AS comment,
                fd.updated AS updated,
                fd.notes AS notes,
                fv.visits AS visits,
                fv.last_visit AS last_visit
            FROM
                " . Database::getTablePrefix() . "faqdata fd,
                " . Database::getTablePrefix() . "faqvisits fv,
                " . Database::getTablePrefix() . "faqcategoryrelations fcr
            WHERE
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            AND fd.id = fv.id
            AND
                fd.lang = fv.lang AND (fcr.category_id = 1) AND fd.lang = 'en' AND fd.active = 'yes'
ORDER BY fcr.category_id, fd.id";

        // Perform the assertion
        $this->assertSame($expectedQuery, $result);
    }
}
