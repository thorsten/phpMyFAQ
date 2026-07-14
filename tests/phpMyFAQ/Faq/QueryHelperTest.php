<?php

namespace phpMyFAQ\Faq;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class QueryHelperTest extends TestCase
{
    private ?Configuration $previousConfiguration = null;

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        Database::setTablePrefix('');

        $configuration = new Configuration($dbHandle);
        $configuration->getAll();

        $reflection = new ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('configuration');
        $this->previousConfiguration = $property->getValue();
        $property->setValue(null, $configuration);

        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');

        $configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        $reflection = new ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('configuration');
        $property->setValue(null, $this->previousConfiguration);

        parent::tearDown();
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
        $expectedQuery =
            '
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
                '
            . Database::getTablePrefix()
            . 'faqdata fd,
                '
            . Database::getTablePrefix()
            . 'faqvisits fv,
                '
            . Database::getTablePrefix()
            . "faqcategoryrelations fcr
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

    public function testGetCategoryIdWhereSequenceRecursesIntoChildren(): void
    {
        $queryHelper = new QueryHelper(-1, [1, 2, 3]);

        $category = $this->createMock(Category::class);
        $category
            ->method('getChildren')
            ->willReturnCallback(static function (int $categoryId): array {
                return match ($categoryId) {
                    1 => [2, 3],
                    2 => [4],
                    3, 4 => [],
                    default => [],
                };
            });

        $reflectionMethod = new \ReflectionMethod(QueryHelper::class, 'getCategoryIdWhereSequence');

        $result = $reflectionMethod->invoke($queryHelper, 1, $category);

        $this->assertSame(' OR fcr.category_id = 2 OR fcr.category_id = 4 OR fcr.category_id = 3', $result);
    }

    public function testQueryPermissionNormalizesGroupIds(): void
    {
        $queryHelper = new QueryHelper(-1, ['1) OR 1=1 -- ', '2']);

        $this->assertSame('AND fdg.group_id IN (1, 2)', $queryHelper->queryPermission(true));
    }

    public function testQueryPermissionExistsAllWithoutGroupSupport(): void
    {
        $queryHelper = new QueryHelper(42, [1, 2, 3]);

        $this->assertSame(
            'AND EXISTS (SELECT 1 FROM faqdata_user pfdu '
            . 'WHERE pfdu.record_id = fd.id AND pfdu.user_id IN (-1, 42))',
            $queryHelper->queryPermissionExistsAll(),
        );
    }

    public function testQueryPermissionExistsAllForGuestWithoutGroupSupport(): void
    {
        $queryHelper = new QueryHelper(-1, [1, 2, 3]);

        $this->assertSame(
            'AND EXISTS (SELECT 1 FROM faqdata_user pfdu '
            . 'WHERE pfdu.record_id = fd.id AND pfdu.user_id IN (-1))',
            $queryHelper->queryPermissionExistsAll(),
        );
    }

    public function testQueryPermissionExistsAllWithGroupSupport(): void
    {
        $queryHelper = new QueryHelper(42, [1, 2, 3]);

        $this->assertSame(
            'AND EXISTS (SELECT 1 FROM faqdata_user pfdu '
            . 'WHERE pfdu.record_id = fd.id AND pfdu.user_id IN (-1, 42)) '
            . 'AND EXISTS (SELECT 1 FROM faqdata_group pfdg '
            . 'WHERE pfdg.record_id = fd.id AND pfdg.group_id IN (1, 2, 3))',
            $queryHelper->queryPermissionExistsAll(true),
        );
    }

    public function testQueryPermissionExistsAllForGuestWithGroupSupport(): void
    {
        $queryHelper = new QueryHelper(-1, [7]);

        $this->assertSame(
            'AND EXISTS (SELECT 1 FROM faqdata_user pfdu '
            . 'WHERE pfdu.record_id = fd.id AND pfdu.user_id IN (-1)) '
            . 'AND EXISTS (SELECT 1 FROM faqdata_group pfdg '
            . 'WHERE pfdg.record_id = fd.id AND pfdg.group_id IN (7))',
            $queryHelper->queryPermissionExistsAll(true),
        );
    }

    public function testQueryPermissionExistsAnyWithoutGroupSupport(): void
    {
        $queryHelper = new QueryHelper(42, [1, 2, 3]);

        $this->assertSame(
            'AND EXISTS (SELECT 1 FROM faqdata_user pfdu '
            . 'WHERE pfdu.record_id = fd.id AND pfdu.user_id IN (-1, 42))',
            $queryHelper->queryPermissionExistsAny(),
        );
    }

    public function testQueryPermissionExistsAnyForGuestWithoutGroupSupport(): void
    {
        $queryHelper = new QueryHelper(-1, [1, 2, 3]);

        $this->assertSame(
            'AND EXISTS (SELECT 1 FROM faqdata_user pfdu '
            . 'WHERE pfdu.record_id = fd.id AND pfdu.user_id IN (-1))',
            $queryHelper->queryPermissionExistsAny(),
        );
    }

    public function testQueryPermissionExistsAnyWithGroupSupportAllowsUserOrGroupMatch(): void
    {
        $queryHelper = new QueryHelper(42, [1, 2, 3]);

        $this->assertSame(
            'AND (EXISTS (SELECT 1 FROM faqdata_user pfdu '
            . 'WHERE pfdu.record_id = fd.id AND pfdu.user_id = 42) '
            . 'OR EXISTS (SELECT 1 FROM faqdata_group pfdg '
            . 'WHERE pfdg.record_id = fd.id AND pfdg.group_id IN (1, 2, 3)))',
            $queryHelper->queryPermissionExistsAny(true),
        );
    }

    public function testQueryPermissionExistsAnyForGuestWithGroupSupportRequiresGroupMatch(): void
    {
        $queryHelper = new QueryHelper(-1, [7]);

        $this->assertSame(
            'AND EXISTS (SELECT 1 FROM faqdata_group pfdg '
            . 'WHERE pfdg.record_id = fd.id AND pfdg.group_id IN (7))',
            $queryHelper->queryPermissionExistsAny(true),
        );
    }

    public function testQueryPermissionExistsAllNormalizesGroupIds(): void
    {
        $queryHelper = new QueryHelper(-1, ['1) OR 1=1 -- ', '2']);

        $this->assertStringContainsString('pfdg.group_id IN (1, 2)', $queryHelper->queryPermissionExistsAll(true));
    }
}
