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
 * @copyright 2006-2026 phpMyFAQ Team
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
    private AdminLogRepository $adminLogRepository;

    /**
     * Constructor.
     */
    public function __construct(
        private Configuration $configuration,
    ) {
        $this->adminLogRepository = new AdminLogRepository($this->configuration);
    }

    /**
     * Returns the number of entries.
     */
    public function getNumberOfEntries(): int
    {
        return $this->adminLogRepository->getNumberOfEntries();
    }

    /**
     * Returns all data from the admin log.
     * @return AdminLogEntity[]
     */
    public function getAll(): array
    {
        return $this->adminLogRepository->getAll();
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

        // Get the hash of the last log entry for chaining
        $previousHash = $this->adminLogRepository->getLastHash();

        return $this->adminLogRepository->add($user, $logText, $request, $previousHash);
    }

    /**
     * Deletes logging data older than 30 days.
     */
    public function delete(): bool
    {
        $timestamp = (int) Request::createFromGlobals()->server->get(key: 'REQUEST_TIME') - (30 * 86400);
        return $this->adminLogRepository->deleteOlderThan($timestamp);
    }

    /**
     * Verifies the integrity of the entire admin log chain.
     * @return array{valid: bool, errors: array<int, string>, total: int, verified: int}
     */
    public function verifyChainIntegrity(): array
    {
        $logs = $this->getAll();
        $errors = [];
        $verified = 0;
        $total = count($logs);

        if ($total === 0) {
            return [
                'valid' => true,
                'errors' => [],
                'total' => 0,
                'verified' => 0,
            ];
        }

        $previousHash = null;

        foreach ($logs as $log) {
            // Verify the hash matches the stored hash
            if (!$log->verifyIntegrity()) {
                $errors[] = sprintf('Log ID %d: Hash verification failed - data has been tampered', $log->getId());
                continue;
            }

            // Verify the chain (previous hash matches)
            if ($previousHash !== null && $log->getPreviousHash() !== $previousHash) {
                $errors[] = sprintf(
                    'Log ID %d: Chain broken - previous hash mismatch (expected: %s, got: %s)',
                    $log->getId(),
                    substr($previousHash, 0, 8) . '...',
                    substr($log->getPreviousHash() ?? 'NULL', 0, 8) . '...',
                );
                continue;
            }

            // The first entry should have null previous hash
            if ($previousHash === null && $log->getPreviousHash() !== null) {
                $errors[] = sprintf('Log ID %d: First entry should have null previous hash', $log->getId());
                continue;
            }

            $verified++;
            $previousHash = $log->getHash();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'total' => $total,
            'verified' => $verified,
        ];
    }

    /**
     * Calculates hash for a single log entry (for migration or manual verification).
     */
    public function calculateHash(AdminLogEntity $log): string
    {
        return $log->calculateHash();
    }
}
