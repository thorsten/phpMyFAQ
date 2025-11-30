<?php

/**
 * The main Logging class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-08-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\AdminLog as AdminLogEntity;
use phpMyFAQ\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Logging
 *
 * @package phpMyFAQ
 */
readonly class AdminLog
{
    /** @var AdminLogRepository */
    private AdminLogRepository $repository;

    /**
     * Constructor.
     */
    public function __construct(
        private Configuration $configuration,
    ) {
        $this->repository = new AdminLogRepository($this->configuration);
    }

    /**
     * Returns the number of entries.
     */
    public function getNumberOfEntries(): int
    {
        return $this->repository->getNumberOfEntries();
    }

    /**
     * Returns all data from the admin log.
     * @return AdminLogEntity[]
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Adds a new admin log entry.
     *
     * @param User   $user    User object
     * @param string $logText Logged string
     */
    public function log(User $user, string $logText = ''): bool
    {
        if (!$this->configuration->get(item: 'main.enableAdminLog')) {
            return false;
        }

        $request = Request::createFromGlobals();
        return $this->repository->add($user, $logText, $request);
    }

    /**
     * Deletes logging data older than 30 days.
     */
    public function delete(): bool
    {
        $timestamp = (int) Request::createFromGlobals()->server->get(key: 'REQUEST_TIME') - (30 * 86400);
        return $this->repository->deleteOlderThan($timestamp);
    }
}
