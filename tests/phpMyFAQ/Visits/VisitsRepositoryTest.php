<?php

declare(strict_types=1);

namespace phpMyFAQ\Visits\Test;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Visits\VisitsRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class VisitsRepositoryTest extends TestCase
{
    private Configuration $configuration;
    private VisitsRepository $repository;

    protected function setUp(): void
    {
        $db = new Sqlite3();
        $db->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($db);
        $this->repository = new VisitsRepository($this->configuration);
    }

    protected function tearDown(): void
    {
        $query = sprintf('DELETE FROM %sfaqvisits', Database::getTablePrefix());
        $this->configuration->getDb()->query($query);
    }

    public function testGetVisitCountReturnsZeroWhenNoRecord(): void
    {
        $count = $this->repository->getVisitCount(999, 'en');
        $this->assertSame(0, $count);
    }

    public function testExistsReturnsFalseWhenNoRecord(): void
    {
        $exists = $this->repository->exists(999, 'en');
        $this->assertFalse($exists);
    }

    public function testInsertCreatesNewRecord(): void
    {
        $faqId = 1;
        $language = 'en';
        $timestamp = time();

        $result = $this->repository->insert($faqId, $language, $timestamp);
        $this->assertTrue($result);

        $count = $this->repository->getVisitCount($faqId, $language);
        $this->assertSame(1, $count);
    }

    public function testExistsReturnsTrueAfterInsert(): void
    {
        $faqId = 1;
        $language = 'en';
        $timestamp = time();

        $this->repository->insert($faqId, $language, $timestamp);
        $exists = $this->repository->exists($faqId, $language);
        $this->assertTrue($exists);
    }

    public function testUpdateIncrementsVisitCount(): void
    {
        $faqId = 1;
        $language = 'en';
        $timestamp = time();

        $this->repository->insert($faqId, $language, $timestamp);
        $this->repository->update($faqId, $language, $timestamp + 100);

        $count = $this->repository->getVisitCount($faqId, $language);
        $this->assertSame(2, $count);
    }

    public function testGetAllReturnsAllRecords(): void
    {
        $timestamp = time();

        $this->repository->insert(1, 'en', $timestamp);
        $this->repository->insert(2, 'de', $timestamp);
        $this->repository->update(1, 'en', $timestamp + 100);

        $allData = $this->repository->getAll();

        $this->assertCount(2, $allData);
        $this->assertSame(1, $allData[0]['id']);
        $this->assertSame('en', $allData[0]['lang']);
        $this->assertSame(2, $allData[0]['visits']);
    }

    public function testGetAllOrdersByVisitsDescending(): void
    {
        $timestamp = time();

        $this->repository->insert(1, 'en', $timestamp);
        $this->repository->insert(2, 'de', $timestamp);
        $this->repository->update(2, 'de', $timestamp + 100);
        $this->repository->update(2, 'de', $timestamp + 200);

        $allData = $this->repository->getAll();

        $this->assertSame(2, $allData[0]['id']);
        $this->assertSame(3, $allData[0]['visits']);
        $this->assertSame(1, $allData[1]['id']);
        $this->assertSame(1, $allData[1]['visits']);
    }

    public function testResetAllSetsVisitsToOne(): void
    {
        $timestamp = time();

        $this->repository->insert(1, 'en', $timestamp);
        $this->repository->insert(2, 'de', $timestamp);
        $this->repository->update(1, 'en', $timestamp + 100);
        $this->repository->update(2, 'de', $timestamp + 100);
        $this->repository->update(2, 'de', $timestamp + 200);

        $result = $this->repository->resetAll($timestamp + 300);
        $this->assertTrue($result);

        $allData = $this->repository->getAll();
        $this->assertSame(1, $allData[0]['visits']);
        $this->assertSame(1, $allData[1]['visits']);
    }

    public function testResetAllUpdatesLastVisit(): void
    {
        $timestamp = time();
        $resetTimestamp = $timestamp + 1000;

        $this->repository->insert(1, 'en', $timestamp);
        $this->repository->resetAll($resetTimestamp);

        $allData = $this->repository->getAll();
        $this->assertSame($resetTimestamp, $allData[0]['last_visit']);
    }

    public function testGetVisitCountWithDifferentLanguages(): void
    {
        $timestamp = time();

        $this->repository->insert(1, 'en', $timestamp);
        $this->repository->insert(1, 'de', $timestamp);

        $countEn = $this->repository->getVisitCount(1, 'en');
        $countDe = $this->repository->getVisitCount(1, 'de');

        $this->assertSame(1, $countEn);
        $this->assertSame(1, $countDe);
    }
}
