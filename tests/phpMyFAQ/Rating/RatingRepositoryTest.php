<?php

/**
 * RatingRepository Test
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-18
 */

namespace phpMyFAQ\Rating;

use phpMyFAQ\Configuration;use phpMyFAQ\Database\Sqlite3;use phpMyFAQ\Entity\Vote;use phpMyFAQ\Language;use phpMyFAQ\Search\Rating\RatingRepository;use phpMyFAQ\Strings;use phpMyFAQ\Translation;use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;use PHPUnit\Framework\MockObject\Exception as MockException;use PHPUnit\Framework\TestCase;use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class RatingRepositoryTest extends TestCase
{
    private Configuration $configuration;
    private RatingRepository $repository;

    /**
     * @throws MockException
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.referenceURL', 'https://example.com');

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->repository = new RatingRepository($this->configuration);
    }

    public function testFetchByRecordIdReturnsNullWhenNoVotes(): void
    {
        // Clean state
        $this->repository->deleteAll();

        $result = $this->repository->fetchByRecordId(999);
        $this->assertNull($result);
    }

    public function testCreateAndFetchVote(): void
    {
        // Clean state
        $this->repository->deleteAll();

        $vote = new Vote();
        $vote->setFaqId(1);
        $vote->setVote(5);
        $vote->setIp('127.0.0.1');

        $this->assertTrue($this->repository->create($vote));

        $result = $this->repository->fetchByRecordId(1);
        $this->assertNotNull($result);
        $this->assertObjectHasProperty('voting', $result);
        $this->assertObjectHasProperty('usr', $result);
        $this->assertEquals(5, $result->voting);
        $this->assertEquals(1, $result->usr);
    }

    public function testUpdateVote(): void
    {
        // Clean state
        $this->repository->deleteAll();

        // Create initial vote
        $vote = new Vote();
        $vote->setFaqId(1);
        $vote->setVote(3);
        $vote->setIp('127.0.0.1');
        $this->repository->create($vote);

        // Update vote
        $vote->setVote(2);
        $this->assertTrue($this->repository->update($vote));

        // Check updated values
        $result = $this->repository->fetchByRecordId(1);
        $this->assertNotNull($result);
        // SQLite performs integer division, so (3 + 2) / 2 = 2
        $this->assertEquals(2, $result->voting);
        $this->assertEquals(2, $result->usr);
    }

    public function testGetNumberOfVotings(): void
    {
        // Clean state
        $this->repository->deleteAll();

        $this->assertEquals(0, $this->repository->getNumberOfVotings(1));

        $vote = new Vote();
        $vote->setFaqId(1);
        $vote->setVote(4);
        $vote->setIp('127.0.0.1');
        $this->repository->create($vote);

        $this->assertEquals(1, $this->repository->getNumberOfVotings(1));

        // Update to increment user count
        $vote->setVote(5);
        $this->repository->update($vote);

        $this->assertEquals(2, $this->repository->getNumberOfVotings(1));
    }

    public function testGetNumberOfVotingsReturnsZeroForNonExistentRecord(): void
    {
        $this->repository->deleteAll();
        $this->assertEquals(0, $this->repository->getNumberOfVotings(999));
    }

    public function testIsVoteAllowed(): void
    {
        // Clean state
        $this->repository->deleteAll();

        // Should be allowed before any vote
        $this->assertTrue($this->repository->isVoteAllowed(1, '192.168.1.1'));

        // Create a vote
        $vote = new Vote();
        $vote->setFaqId(1);
        $vote->setVote(5);
        $vote->setIp('192.168.1.1');
        $this->repository->create($vote);

        // Should not be allowed from the same IP within 5 minutes
        $this->assertFalse($this->repository->isVoteAllowed(1, '192.168.1.1'));

        // Should be allowed from different IP
        $this->assertTrue($this->repository->isVoteAllowed(1, '192.168.1.2'));

        // Should be allowed for different FAQ
        $this->assertTrue($this->repository->isVoteAllowed(2, '192.168.1.1'));
    }

    public function testDeleteAll(): void
    {
        // Create multiple votes
        $vote1 = new Vote();
        $vote1->setFaqId(1);
        $vote1->setVote(5);
        $vote1->setIp('127.0.0.1');
        $this->repository->create($vote1);

        $vote2 = new Vote();
        $vote2->setFaqId(2);
        $vote2->setVote(4);
        $vote2->setIp('127.0.0.2');
        $this->repository->create($vote2);

        // Delete all
        $this->assertTrue($this->repository->deleteAll());

        // Verify all deleted
        $this->assertNull($this->repository->fetchByRecordId(1));
        $this->assertNull($this->repository->fetchByRecordId(2));
        $this->assertEquals(0, $this->repository->getNumberOfVotings(1));
        $this->assertEquals(0, $this->repository->getNumberOfVotings(2));
    }

    public function testMultipleVotesForSameFaq(): void
    {
        // Clean state
        $this->repository->deleteAll();

        // Create first vote
        $vote1 = new Vote();
        $vote1->setFaqId(1);
        $vote1->setVote(5);
        $vote1->setIp('127.0.0.1');
        $this->repository->create($vote1);

        // Add second vote
        $vote2 = new Vote();
        $vote2->setFaqId(1);
        $vote2->setVote(3);
        $vote2->setIp('127.0.0.2');
        $this->repository->update($vote2);

        $result = $this->repository->fetchByRecordId(1);
        $this->assertNotNull($result);
        $this->assertEquals(4, $result->voting); // (5 + 3) / 2
        $this->assertEquals(2, $result->usr);
    }

    public function testVotesForDifferentFaqs(): void
    {
        // Clean state
        $this->repository->deleteAll();

        $vote1 = new Vote();
        $vote1->setFaqId(1);
        $vote1->setVote(5);
        $vote1->setIp('127.0.0.1');
        $this->repository->create($vote1);

        $vote2 = new Vote();
        $vote2->setFaqId(2);
        $vote2->setVote(3);
        $vote2->setIp('127.0.0.2');
        $this->repository->create($vote2);

        $result1 = $this->repository->fetchByRecordId(1);
        $result2 = $this->repository->fetchByRecordId(2);

        $this->assertNotNull($result1);
        $this->assertNotNull($result2);
        $this->assertEquals(5, $result1->voting);
        $this->assertEquals(3, $result2->voting);
        $this->assertEquals(1, $this->repository->getNumberOfVotings(1));
        $this->assertEquals(1, $this->repository->getNumberOfVotings(2));
    }
}
