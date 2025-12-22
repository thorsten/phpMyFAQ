<?php

/**
 * Rating Repository Interface.
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
 * @since     2025-11-19
 */

declare(strict_types=1);

namespace phpMyFAQ\Search\Rating;

use phpMyFAQ\Entity\Vote;

interface RatingRepositoryInterface
{
    /**
     * Fetches the voting data for a given FAQ record.
     *
     * @return object|null Returns an object with 'voting' and 'usr' properties, or null if not found
     */
    public function fetchByRecordId(int $id): ?object;

    /**
     * Checks if a vote from this IP is allowed (not within 5 minutes).
     */
    public function isVoteAllowed(int $id, string $ip): bool;

    /**
     * Returns the number of users who voted for a record.
     */
    public function getNumberOfVotings(int $recordId): int;

    /**
     * Creates a new voting record.
     */
    public function create(Vote $vote): bool;

    /**
     * Updates an existing voting record.
     */
    public function update(Vote $vote): bool;

    /**
     * Deletes all votes.
     */
    public function deleteAll(): bool;
}
