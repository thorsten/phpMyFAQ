<?php

/**
 * Test case for Vote Entity
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
 */

namespace phpMyFAQ\Entity;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * Class VoteTest
 */
class VoteTest extends TestCase
{
    private Vote $vote;

    protected function setUp(): void
    {
        $this->vote = new Vote();
    }

    /**
     * Test Vote entity instantiation
     */
    public function testVoteInstantiation(): void
    {
        $this->assertInstanceOf(Vote::class, $this->vote);
    }

    /**
     * Test id getter and setter
     */
    public function testIdGetterAndSetter(): void
    {
        $id = 123;
        $result = $this->vote->setId($id);

        $this->assertInstanceOf(Vote::class, $result); // Test fluent interface
        $this->assertEquals($id, $this->vote->getId());
    }

    /**
     * Test faqId getter and setter
     */
    public function testFaqIdGetterAndSetter(): void
    {
        $faqId = 456;
        $result = $this->vote->setFaqId($faqId);

        $this->assertInstanceOf(Vote::class, $result); // Test fluent interface
        $this->assertEquals($faqId, $this->vote->getFaqId());
    }

    /**
     * Test vote getter and setter
     */
    public function testVoteGetterAndSetter(): void
    {
        $vote = 5;
        $result = $this->vote->setVote($vote);

        $this->assertInstanceOf(Vote::class, $result); // Test fluent interface
        $this->assertEquals($vote, $this->vote->getVote());
    }

    /**
     * Test users getter and setter
     */
    public function testUsersGetterAndSetter(): void
    {
        $users = 10;
        $result = $this->vote->setUsers($users);

        $this->assertInstanceOf(Vote::class, $result); // Test fluent interface
        $this->assertEquals($users, $this->vote->getUsers());
    }

    /**
     * Test createdAt getter and setter
     */
    public function testCreatedAtGetterAndSetter(): void
    {
        $dateTime = new DateTime('2025-08-09 12:00:00');
        $result = $this->vote->setCreatedAt($dateTime);

        $this->assertInstanceOf(Vote::class, $result); // Test fluent interface
        $this->assertSame($dateTime, $this->vote->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $this->vote->getCreatedAt());
    }

    /**
     * Test ip getter and setter
     */
    public function testIpGetterAndSetter(): void
    {
        $ip = '192.168.1.1';
        $result = $this->vote->setIp($ip);

        $this->assertInstanceOf(Vote::class, $result); // Test fluent interface
        $this->assertEquals($ip, $this->vote->getIp());
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $dateTime = new DateTime();

        $result = $this->vote
            ->setId(1)
            ->setFaqId(100)
            ->setVote(4)
            ->setUsers(5)
            ->setCreatedAt($dateTime)
            ->setIp('10.0.0.1');

        $this->assertInstanceOf(Vote::class, $result);
        $this->assertEquals(1, $this->vote->getId());
        $this->assertEquals(100, $this->vote->getFaqId());
        $this->assertEquals(4, $this->vote->getVote());
        $this->assertEquals(5, $this->vote->getUsers());
        $this->assertSame($dateTime, $this->vote->getCreatedAt());
        $this->assertEquals('10.0.0.1', $this->vote->getIp());
    }

    /**
     * Test vote with negative values
     */
    public function testVoteWithNegativeValues(): void
    {
        $this->vote->setVote(-1);
        $this->assertEquals(-1, $this->vote->getVote());

        $this->vote->setUsers(0);
        $this->assertEquals(0, $this->vote->getUsers());
    }

    /**
     * Test vote with various IP address formats
     */
    public function testVoteWithVariousIpFormats(): void
    {
        $ipAddresses = [
            '127.0.0.1',
            '192.168.1.1',
            '10.0.0.1',
            '2001:db8::1',
            '::1',
            'unknown'
        ];

        foreach ($ipAddresses as $ip) {
            $this->vote->setIp($ip);
            $this->assertEquals($ip, $this->vote->getIp());
        }
    }

    /**
     * Test vote with different DateTime objects
     */
    public function testVoteWithDifferentDateTimes(): void
    {
        $dates = [
            new DateTime('2025-01-01 00:00:00'),
            new DateTime('2025-12-31 23:59:59'),
            new DateTime(), // Current time
            new DateTime('1970-01-01 00:00:00')
        ];

        foreach ($dates as $date) {
            $this->vote->setCreatedAt($date);
            $this->assertSame($date, $this->vote->getCreatedAt());
        }
    }

    /**
     * Test vote value boundaries
     */
    public function testVoteValueBoundaries(): void
    {
        // Test minimum and maximum integer values
        $this->vote->setVote(PHP_INT_MIN);
        $this->assertEquals(PHP_INT_MIN, $this->vote->getVote());

        $this->vote->setVote(PHP_INT_MAX);
        $this->assertEquals(PHP_INT_MAX, $this->vote->getVote());

        // Test typical rating values
        for ($i = 1; $i <= 5; $i++) {
            $this->vote->setVote($i);
            $this->assertEquals($i, $this->vote->getVote());
        }
    }

    /**
     * Test complete vote scenario
     */
    public function testCompleteVoteScenario(): void
    {
        $voteId = 42;
        $faqId = 123;
        $voteValue = 4;
        $userCount = 15;
        $createdAt = new DateTime('2025-08-09 15:30:00');
        $ip = '203.0.113.1';

        $this->vote
            ->setId($voteId)
            ->setFaqId($faqId)
            ->setVote($voteValue)
            ->setUsers($userCount)
            ->setCreatedAt($createdAt)
            ->setIp($ip);

        // Verify all values
        $this->assertEquals($voteId, $this->vote->getId());
        $this->assertEquals($faqId, $this->vote->getFaqId());
        $this->assertEquals($voteValue, $this->vote->getVote());
        $this->assertEquals($userCount, $this->vote->getUsers());
        $this->assertEquals($createdAt, $this->vote->getCreatedAt());
        $this->assertEquals($ip, $this->vote->getIp());
    }

    /**
     * Test empty string IP handling
     */
    public function testEmptyStringIp(): void
    {
        $this->vote->setIp('');
        $this->assertEquals('', $this->vote->getIp());
    }

    /**
     * Test zero values
     */
    public function testZeroValues(): void
    {
        $this->vote
            ->setId(0)
            ->setFaqId(0)
            ->setVote(0)
            ->setUsers(0);

        $this->assertEquals(0, $this->vote->getId());
        $this->assertEquals(0, $this->vote->getFaqId());
        $this->assertEquals(0, $this->vote->getVote());
        $this->assertEquals(0, $this->vote->getUsers());
    }

    /**
     * Test object state consistency
     */
    public function testObjectStateConsistency(): void
    {
        $originalVote = new Vote();
        $originalVote->setId(1)->setFaqId(10)->setVote(5);

        // Verify state doesn't change unexpectedly
        $this->assertEquals(1, $originalVote->getId());
        $this->assertEquals(10, $originalVote->getFaqId());
        $this->assertEquals(5, $originalVote->getVote());

        // Create another vote object and verify independence
        $anotherVote = new Vote();
        $anotherVote->setId(2)->setFaqId(20)->setVote(3);

        // Original should be unchanged
        $this->assertEquals(1, $originalVote->getId());
        $this->assertEquals(10, $originalVote->getFaqId());
        $this->assertEquals(5, $originalVote->getVote());
    }
}
