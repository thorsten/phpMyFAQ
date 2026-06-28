<?php

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Strings;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class SearchResultSetTest
 */
#[AllowMockObjectsWithoutExpectations]
class SearchResultSetTest extends TestCase
{
    private SearchResultSet $searchResultSet;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        $dbHandle = new Sqlite3();
        $configuration = new Configuration($dbHandle);

        $userMock = $this->createMock(CurrentUser::class);
        $faqPermissionMock = $this->createMock(Permission::class);

        $this->searchResultSet = new SearchResultSet($userMock, $faqPermissionMock, $configuration);
    }

    /**
     * Builds a SearchResultSet whose permission level, permission lookups and current
     * user id are fully controlled, so reviewResultSet() can be exercised deterministically
     * without touching the database.
     *
     * @param int[] $permissionResult value returned by Permission::get() for every lookup
     */
    private function makeReviewableResultSet(
        string $permLevel = 'basic',
        array $permissionResult = [-1],
        int $userId = 1,
    ): SearchResultSet {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')->willReturn($permLevel);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('getUserId')->willReturn($userId);

        $faqPermission = $this->createMock(Permission::class);
        $faqPermission->method('get')->willReturn($permissionResult);

        return new SearchResultSet($currentUser, $faqPermission, $configuration);
    }

    private function makeResult(int $id, array $properties = []): stdClass
    {
        $result = new stdClass();
        $result->id = $id;
        foreach ($properties as $key => $value) {
            $result->{$key} = $value;
        }

        return $result;
    }

    public function testSetAndGetNumberOfResults(): void
    {
        $this->searchResultSet->setNumberOfResults([1, 2]);
        $this->assertSame(2, $this->searchResultSet->getNumberOfResults());

        $this->searchResultSet->setNumberOfResults([]);
        $this->assertSame(0, $this->searchResultSet->getNumberOfResults());
    }

    public function testGetResultSetIsEmptyBeforeReview(): void
    {
        $this->assertSame([], $this->searchResultSet->getResultSet());
        $this->assertSame(0, $this->searchResultSet->getNumberOfResults());
    }

    public function testGetScoreSumsAllThreeRelevanceFields(): void
    {
        $searchResultSet = $this->makeReviewableResultSet();
        $object = $this->makeResult(1, [
            'relevance_thema' => 0.9,
            'relevance_content' => 0.6,
            'relevance_keywords' => 0.3,
        ]);

        // (0.9 + 0.6 + 0.3) / 3 * 100 = 60
        $this->assertSame(60.0, $searchResultSet->getScore($object));
    }

    public function testGetScoreReturnsZeroWhenNoRelevanceFields(): void
    {
        $searchResultSet = $this->makeReviewableResultSet();

        $this->assertSame(0.0, $searchResultSet->getScore($this->makeResult(1)));
    }

    public function testGetScoreIgnoresMissingFields(): void
    {
        $searchResultSet = $this->makeReviewableResultSet();
        $object = $this->makeResult(1, ['relevance_thema' => 1.5]);

        // 1.5 / 3 * 100 = 50
        $this->assertSame(50.0, $searchResultSet->getScore($object));
    }

    public function testGetScoreIgnoresNullFields(): void
    {
        $searchResultSet = $this->makeReviewableResultSet();
        $object = $this->makeResult(1, [
            'relevance_thema' => 1.5,
            'relevance_content' => null,
            'relevance_keywords' => null,
        ]);

        $this->assertSame(50.0, $searchResultSet->getScore($object));
    }

    public function testGetScoreRoundsToNearestInteger(): void
    {
        $searchResultSet = $this->makeReviewableResultSet();
        $object = $this->makeResult(1, ['relevance_thema' => 1]);

        // 1 / 3 * 100 = 33.33… -> 33
        $this->assertSame(33.0, $searchResultSet->getScore($object));
    }

    public function testReviewResultSetIncludesPermittedResultForBasicPermLevel(): void
    {
        $searchResultSet = $this->makeReviewableResultSet('basic', [-1], 1);

        $searchResultSet->reviewResultSet([$this->makeResult(1)]);

        $this->assertCount(1, $searchResultSet->getResultSet());
        $this->assertSame(1, $searchResultSet->getNumberOfResults());
    }

    public function testReviewResultSetExcludesUnpermittedResultForBasicPermLevel(): void
    {
        // Result is restricted to user 5, current user is 1 and -1 (everyone) is absent.
        $searchResultSet = $this->makeReviewableResultSet('basic', [5], 1);

        $searchResultSet->reviewResultSet([$this->makeResult(1)]);

        $this->assertSame([], $searchResultSet->getResultSet());
        $this->assertSame(0, $searchResultSet->getNumberOfResults());
    }

    public function testReviewResultSetIncludesResultRestrictedToCurrentUser(): void
    {
        $searchResultSet = $this->makeReviewableResultSet('basic', [7], 7);

        $searchResultSet->reviewResultSet([$this->makeResult(1)]);

        $this->assertCount(1, $searchResultSet->getResultSet());
    }

    public function testReviewResultSetIncludesPermittedGroupForMediumPermLevel(): void
    {
        $searchResultSet = $this->makeReviewableResultSet('medium', [-1], 1);

        $searchResultSet->reviewResultSet([$this->makeResult(1)]);

        $this->assertCount(1, $searchResultSet->getResultSet());
    }

    public function testReviewResultSetExcludesUnpermittedGroupForMediumPermLevel(): void
    {
        $searchResultSet = $this->makeReviewableResultSet('medium', [99], 1);

        $searchResultSet->reviewResultSet([$this->makeResult(1)]);

        $this->assertSame([], $searchResultSet->getResultSet());
    }

    public function testReviewResultSetRemovesDuplicateIds(): void
    {
        $searchResultSet = $this->makeReviewableResultSet('basic', [-1], 1);

        $searchResultSet->reviewResultSet([
            $this->makeResult(1),
            $this->makeResult(1),
            $this->makeResult(2),
        ]);

        $this->assertCount(2, $searchResultSet->getResultSet());
        $this->assertSame(2, $searchResultSet->getNumberOfResults());
    }

    public function testReviewResultSetAssignsScoreWhenMissing(): void
    {
        $searchResultSet = $this->makeReviewableResultSet('basic', [-1], 1);
        $result = $this->makeResult(1, [
            'relevance_thema' => 1,
            'relevance_content' => 1,
            'relevance_keywords' => 1,
        ]);

        $searchResultSet->reviewResultSet([$result]);

        $reviewed = $searchResultSet->getResultSet();
        $this->assertSame(100.0, $reviewed[0]->score);
    }

    public function testReviewResultSetPreservesExistingScore(): void
    {
        $searchResultSet = $this->makeReviewableResultSet('basic', [-1], 1);
        $result = $this->makeResult(1, [
            'score' => 42.0,
            'relevance_thema' => 1,
            'relevance_content' => 1,
            'relevance_keywords' => 1,
        ]);

        $searchResultSet->reviewResultSet([$result]);

        $reviewed = $searchResultSet->getResultSet();
        $this->assertSame(42.0, $reviewed[0]->score);
    }

    public function testReviewResultSetWithEmptyInputYieldsNoResults(): void
    {
        $searchResultSet = $this->makeReviewableResultSet('basic', [-1], 1);

        $searchResultSet->reviewResultSet([]);

        $this->assertSame([], $searchResultSet->getResultSet());
        $this->assertSame(0, $searchResultSet->getNumberOfResults());
    }
}
