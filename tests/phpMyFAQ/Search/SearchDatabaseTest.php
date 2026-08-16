<?php

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class SearchDatabaseTest
 */
#[AllowMockObjectsWithoutExpectations]
class SearchDatabaseTest extends TestCase
{
    /** @var SearchDatabase */
    private $searchDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        $dbHandle = new Sqlite3();
        $configuration = new Configuration($dbHandle);
        $this->searchDatabase = new SearchDatabase($configuration);
    }

    protected function tearDown(): void
    {
        $this->searchDatabase = null;
        parent::tearDown();
    }

    public function testSetAndGetTable()
    {
        $this->searchDatabase->setTable('faqdata');
        $this->assertEquals('faqdata', $this->searchDatabase->getTable());
        $this->assertIsString($this->searchDatabase->getTable());
    }

    public function testSetAndGetTableWithoutTable()
    {
        $this->assertEquals('', $this->searchDatabase->getTable());
    }

    public function testSetAndGetJoinedTable()
    {
        $this->searchDatabase->setJoinedTable('faqcategoryrelations');
        $this->assertEquals(' LEFT JOIN faqcategoryrelations ON ', $this->searchDatabase->getJoinedTable());
        $this->assertIsString($this->searchDatabase->getJoinedTable());
    }

    public function testSetAndGetJoinedTableWithoutJoinedTable()
    {
        $this->assertEquals('', $this->searchDatabase->getJoinedTable());
    }

    public function testSetAndGetResultColumns()
    {
        $resultColumns = [
            'faqdata.id AS id',
            'faqdata.lang AS lang',
            'faqdata.thema AS question',
            'faqdata.content AS answer',
        ];

        $this->searchDatabase->setResultColumns($resultColumns);
        $this->assertEquals(
            'faqdata.id AS id, faqdata.lang AS lang, faqdata.thema AS question, faqdata.content AS answer',
            $this->searchDatabase->getResultColumns(),
        );
        $this->assertIsString($this->searchDatabase->getResultColumns());
    }

    public function testSetAndGetResultColumnsWithoutResultColumns()
    {
        $this->searchDatabase->setResultColumns([]);
        $this->assertEquals('', $this->searchDatabase->getResultColumns());
    }

    public function testSetAndGetJoinedColumns()
    {
        $joinedColumns = [
            'faqdata.id = faqcategoryrelations.record_id',
            'faqdata.lang = faqcategoryrelations.record_lang',
        ];

        $this->searchDatabase->setJoinedColumns($joinedColumns);
        $this->assertEquals(
            'faqdata.id = faqcategoryrelations.record_id AND faqdata.lang = faqcategoryrelations.record_lang ',
            $this->searchDatabase->getJoinedColumns(),
        );
        $this->assertIsString($this->searchDatabase->getJoinedColumns());
    }

    public function testSetAndGetJoinedColumnsWithoutJoinedColumns()
    {
        $this->searchDatabase->setJoinedColumns([]);
        $this->assertEquals('', $this->searchDatabase->getJoinedColumns());
    }

    public function testSetAndGetMatchingColumns()
    {
        $matchingColumns = [
            'faqdata.thema',
            'faqdata.content',
            'faqdata.keywords',
        ];

        $this->searchDatabase->setMatchingColumns($matchingColumns);
        $this->assertEquals(
            'faqdata.thema, faqdata.content, faqdata.keywords',
            $this->searchDatabase->getMatchingColumns(),
        );
        $this->assertIsString($this->searchDatabase->getMatchingColumns());
    }

    public function testSetAndGetMatchingColumnsWithoutColums()
    {
        $this->searchDatabase->setMatchingColumns([]);
        $this->assertEquals('', $this->searchDatabase->getMatchingColumns());
    }

    public function testSetAndGetConditions()
    {
        $conditions = [
            'faqdata.active' => 'yes',
            'faqcategoryrelations.category_id' => 1,
        ];

        $this->searchDatabase->setConditions($conditions);
        $this->assertEquals(
            " AND faqdata.active = 'yes' AND faqcategoryrelations.category_id = 1",
            $this->searchDatabase->getConditions(),
        );
        $this->assertIsString($this->searchDatabase->getConditions());
    }

    public function testSetAndGetConditionsWithoutConditions()
    {
        $this->searchDatabase->setConditions([]);
        $this->assertEquals('', $this->searchDatabase->getConditions());
    }

    public function testGetMatchClause()
    {
        $this->searchDatabase->setMatchingColumns(['faqdata.author']);
        $this->assertEquals(
            " (faqdata.author LIKE '%Thorsten%' ESCAPE '|')",
            $this->searchDatabase->getMatchClause('Thorsten'),
        );
        $this->assertIsString($this->searchDatabase->getMatchClause('Thorsten'));
    }

    public function testGetMatchClauseWithTwoSearchTerms()
    {
        $this->searchDatabase->setMatchingColumns(['faqdata.author']);
        $this->assertEquals(
            " ((faqdata.author LIKE '%Thorsten%' ESCAPE '|') OR (faqdata.author LIKE '%Rinne%' ESCAPE '|'))",
            $this->searchDatabase->getMatchClause('Thorsten Rinne'),
        );
        $this->assertIsString($this->searchDatabase->getMatchClause('Thorsten'));
    }

    /**
     * Regression test: a multi-term match clause must be wrapped in one outer set of
     * parentheses so that a caller's appended "AND ..." conditions (see getConditions())
     * bind across the whole disjunction of per-term groups, not just the last one. Without
     * the outer wrap, "WHERE (A) OR (B) AND status = 'published'" only constrains B by
     * status — SQL's AND binds tighter than a bare OR — so a two-term search would leak
     * non-published (or, previously, inactive) records matched only by the first term.
     */
    public function testGetMatchClauseWrapsMultiTermDisjunctionSoConditionsApplyToEveryTerm(): void
    {
        $this->searchDatabase->setMatchingColumns(['faqdata.thema']);
        $this->searchDatabase->setConditions(['faqdata.status' => 'published']);

        $matchClause = $this->searchDatabase->getMatchClause('foo bar');
        $conditions = $this->searchDatabase->getConditions();
        $where = $matchClause . $conditions;

        // The whole multi-term disjunction is enclosed in one outer parenthesis pair,
        // placed immediately before the appended AND condition.
        $this->assertStringStartsWith(' ((', $matchClause);
        $this->assertStringEndsWith('))', $matchClause);
        $this->assertMatchesRegularExpression(
            "/^ \(\(.*\)\) AND faqdata\.status = 'published'$/",
            $where,
        );
    }

    public function testGetMatchClauseWithTwoColumns()
    {
        $this->searchDatabase->setMatchingColumns(['faqdata.author', 'faqdata.thema']);
        $this->assertEquals(
            " (faqdata.author LIKE '%Thorsten%' ESCAPE '|' OR faqdata.thema LIKE '%Thorsten%' ESCAPE '|')",
            $this->searchDatabase->getMatchClause('Thorsten'),
        );
        $this->assertIsString($this->searchDatabase->getMatchClause('Thorsten'));
    }

    /**
     * A user-supplied LIKE wildcard must be neutralised with the '|' escape
     * character declared in the ESCAPE clause, otherwise it stays an active
     * wildcard and broadens the match (LIKE wildcard injection).
     */
    public function testGetMatchClauseNeutralisesUserWildcards()
    {
        $this->searchDatabase->setMatchingColumns(['faqdata.author']);
        $this->assertEquals(
            " (faqdata.author LIKE '%100|%%' ESCAPE '|')",
            $this->searchDatabase->getMatchClause('100%'),
        );
        $this->assertEquals(
            " (faqdata.author LIKE '%a|_b%' ESCAPE '|')",
            $this->searchDatabase->getMatchClause('a_b'),
        );
    }

    /**
     * The escape character itself must be doubled so it cannot smuggle in an
     * escape sequence of the attacker's choosing.
     */
    public function testGetMatchClauseEscapesTheEscapeCharacter()
    {
        $this->searchDatabase->setMatchingColumns(['faqdata.author']);
        $this->assertEquals(
            " (faqdata.author LIKE '%a||b%' ESCAPE '|')",
            $this->searchDatabase->getMatchClause('a|b'),
        );
    }

    /**
     * Quote escaping and wildcard escaping must compose: the quote is doubled by
     * the driver escape and the '%' is still neutralised.
     */
    public function testGetMatchClauseComposesQuoteAndWildcardEscaping()
    {
        $this->searchDatabase->setMatchingColumns(['faqdata.author']);
        $this->assertEquals(
            " (faqdata.author LIKE '%O''Brien|%%' ESCAPE '|')",
            $this->searchDatabase->getMatchClause("O'Brien%"),
        );
    }
}
