<?php

/**
 * The main Rating class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-31
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Entity\Vote;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Search\Rating\RatingRepository;

/**
 * Class Rating
 *
 * @package phpMyFAQ
 */
readonly class Rating
{
    /**
     * Plural form support.
     */
    private Plurals $plurals;

    /**
     * Rating repository.
     */
    private RatingRepository $ratingRepository;

    /**
     * Constructor.
     */
    public function __construct(Configuration $configuration)
    {
        $this->plurals = new Plurals();
        $this->ratingRepository = new RatingRepository($configuration);
    }

    /**
     * Calculates the rating of the user voting.
     */
    public function get(int $id): string
    {
        $row = $this->ratingRepository->fetchByRecordId($id);

        if ($row !== null) {
            return sprintf(
                ' <span data-rating="%s">%s</span> (' . $this->plurals->GetMsg('plmsgVotes', (int) $row->usr) . ')',
                round((int) $row->voting, 2),
                round((int) $row->voting, 2),
            );
        }

        return ' <span data-rating="0">0</span> (' . $this->plurals->GetMsg('plmsgVotes', 0) . ')';
    }

    /**
     * Reload locking for user voting.
     *
     * @param int    $id FAQ record id
     * @param string $ip IP
     */
    public function check(int $id, string $ip): bool
    {
        return $this->ratingRepository->isVoteAllowed($id, $ip);
    }

    /**
     * Returns the number of users from the table "faqvotings".
     */
    public function getNumberOfVotings(int $recordId): int
    {
        return $this->ratingRepository->getNumberOfVotings($recordId);
    }

    /**
     * Adds a new voting record.
     */
    public function create(Vote $vote): bool
    {
        return $this->ratingRepository->create($vote);
    }

    /**
     * Updates an existing voting record.
     */
    public function update(Vote $vote): bool
    {
        return $this->ratingRepository->update($vote);
    }

    /**
     * Deletes all votes.
     */
    public function deleteAll(): bool
    {
        return $this->ratingRepository->deleteAll();
    }
}
