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
    /**
     * Configuration key of the chain anchor: the hash of the newest entry delete() has pruned.
     * The oldest entry left has to point to it, so removing entries from the start of the log
     * breaks the chain like removing them from the middle does.
     */
    private const string ANCHOR_HASH_KEY = 'security.adminLogAnchorHash';

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

        // Get the hash of the last log entry for chaining; a log pruned empty continues at its anchor
        $previousHash = $this->adminLogRepository->getLastHash() ?? $this->anchorHash();

        return $this->adminLogRepository->add($user, $logText, $request, $previousHash);
    }

    /**
     * Deletes logging data older than 30 days.
     */
    public function delete(): bool
    {
        $timestamp = (int) Request::createFromGlobals()->server->get(key: 'REQUEST_TIME') - (30 * 86_400);
        $newestPrunedHash = $this->adminLogRepository->getLastHashOlderThan($timestamp);

        if (!$this->adminLogRepository->deleteOlderThan($timestamp)) {
            return false;
        }

        // Nothing pruned: the anchor stays, so entries removed by other means are still noticed
        if ($newestPrunedHash === null) {
            return true;
        }

        return $this->storeAnchorHash($newestPrunedHash);
    }

    /**
     * Returns null for a log that was never pruned, or last pruned by a release without the anchor.
     */
    private function anchorHash(): ?string
    {
        $anchorHash = (string) $this->configuration->get(self::ANCHOR_HASH_KEY);

        return $anchorHash === '' ? null : $anchorHash;
    }

    private function storeAnchorHash(string $anchorHash): bool
    {
        // add() only inserts a missing key, set() only updates an existing one
        return (
            $this->configuration->add(self::ANCHOR_HASH_KEY, $anchorHash)
            && $this->configuration->set(self::ANCHOR_HASH_KEY, $anchorHash)
        );
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

        // delete() prunes entries older than 30 days, so the oldest entry left may point to an
        // entry that no longer exists: it has to point to the anchor delete() stored. Without an
        // anchor the chain is verified from the first surviving entry on.
        $anchorHash = $this->anchorHash();
        $previousHash = null;
        $isFirstEntry = true;

        foreach ($logs as $log) {
            $expectedPreviousHash = $previousHash;
            $chainStartsHere = $isFirstEntry;

            // Keep chaining after an error, so one bad entry is not reported for every later one
            $previousHash = $log->getHash();
            $isFirstEntry = false;

            // Verify the hash matches the stored hash
            if (!$log->verifyIntegrity()) {
                $errors[] = sprintf('Log ID %d: Hash verification failed - data has been tampered', $log->getId());
                continue;
            }

            if ($chainStartsHere && $anchorHash !== null && $log->getPreviousHash() !== $anchorHash) {
                $errors[] = sprintf(
                    'Log ID %d: Chain broken - entries before it were removed (expected: %s, got: %s)',
                    $log->getId(),
                    substr(string: $anchorHash, offset: 0, length: 8) . '...',
                    substr(string: $log->getPreviousHash() ?? 'NULL', offset: 0, length: 8) . '...',
                );
                continue;
            }

            // Verify the chain (previous hash matches)
            if (!$chainStartsHere && $log->getPreviousHash() !== $expectedPreviousHash) {
                $errors[] = sprintf(
                    'Log ID %d: Chain broken - previous hash mismatch (expected: %s, got: %s)',
                    $log->getId(),
                    substr(string: $expectedPreviousHash ?? 'NULL', offset: 0, length: 8) . '...',
                    substr(string: $log->getPreviousHash() ?? 'NULL', offset: 0, length: 8) . '...',
                );
                continue;
            }

            $verified++;
        }

        return [
            'valid' => $errors === [],
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
