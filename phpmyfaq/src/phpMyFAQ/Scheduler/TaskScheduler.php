<?php

/**
 * Scheduled background tasks for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Scheduler;

use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Administration\Session as AdminSession;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\Faq\Statistics;
use Throwable;

class TaskScheduler
{
    private const int DEFAULT_SESSION_RETENTION_SECONDS = 86400;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly AdminSession $adminSession,
        private readonly Backup $backup,
        private readonly Statistics $statistics,
    ) {
    }

    /**
     * Runs all configured scheduler tasks.
     *
     * @return array<string, mixed>
     */
    public function run(): array
    {
        return [
            'sessionCleanup' => $this->cleanupSessions(),
            'searchOptimization' => $this->optimizeSearchIndex(),
            'statisticsAggregation' => $this->aggregateStatistics(),
            'backupCreation' => $this->createBackup(),
        ];
    }

    /**
     * @return array{success: bool, cutoffTimestamp: int, retentionSeconds: int}
     */
    public function cleanupSessions(): array
    {
        $configuredRetention = (int) ($this->configuration->get('session.scheduler.retentionSeconds') ?? 0);
        $retentionSeconds = $configuredRetention > 0 ? $configuredRetention : self::DEFAULT_SESSION_RETENTION_SECONDS;
        $cutoffTimestamp = time() - $retentionSeconds;
        $success = $this->adminSession->deleteSessions(0, $cutoffTimestamp);

        if (!$success) {
            $this->configuration->getLogger()->warning('Scheduled session cleanup failed.', [
                'cutoffTimestamp' => $cutoffTimestamp,
                'retentionSeconds' => $retentionSeconds,
            ]);
        }

        return [
            'success' => $success,
            'cutoffTimestamp' => $cutoffTimestamp,
            'retentionSeconds' => $retentionSeconds,
        ];
    }

    /**
     * @return array{success: bool, skipped: bool, elasticsearch: bool|null, opensearch: bool|null}
     */
    public function optimizeSearchIndex(): array
    {
        $elasticsearchResult = null;
        $openSearchResult = null;

        if ((bool) $this->configuration->get('search.enableElasticsearch')) {
            try {
                $this->configuration
                    ->getElasticsearch()
                    ->indices()
                    ->forcemerge([
                        'index' => $this->configuration->getElasticsearchConfig()->getIndex(),
                        'max_num_segments' => 1,
                    ]);
                $elasticsearchResult = true;
            } catch (Throwable $throwable) {
                $elasticsearchResult = false;
                $this->configuration->getLogger()->error('Scheduled Elasticsearch optimization failed.', [
                    'message' => $throwable->getMessage(),
                    'trace' => $throwable->getTraceAsString(),
                ]);
            }
        }

        if ((bool) $this->configuration->get('search.enableOpenSearch')) {
            try {
                $this->configuration
                    ->getOpenSearch()
                    ->indices()
                    ->forcemerge([
                        'index' => $this->configuration->getOpenSearchConfig()->getIndex(),
                        'max_num_segments' => 1,
                    ]);
                $openSearchResult = true;
            } catch (Throwable $throwable) {
                $openSearchResult = false;
                $this->configuration->getLogger()->error('Scheduled OpenSearch optimization failed.', [
                    'message' => $throwable->getMessage(),
                    'trace' => $throwable->getTraceAsString(),
                ]);
            }
        }

        $skipped = $elasticsearchResult === null && $openSearchResult === null;
        $success = !$skipped && $elasticsearchResult !== false && $openSearchResult !== false;

        return [
            'success' => $success,
            'skipped' => $skipped,
            'elasticsearch' => $elasticsearchResult,
            'opensearch' => $openSearchResult,
        ];
    }

    /**
     * @return array{success: bool, generatedAt: int, totalFaqs: int, totalSessions: int}
     */
    public function aggregateStatistics(): array
    {
        return [
            'success' => true,
            'generatedAt' => time(),
            'totalFaqs' => $this->statistics->totalFaqs(),
            'totalSessions' => $this->adminSession->getNumberOfSessions(),
        ];
    }

    /**
     * @return array{success: bool, fileName: string|null}
     */
    public function createBackup(): array
    {
        try {
            $backupResult = $this->backup->export(BackupType::BACKUP_TYPE_DATA);

            return [
                'success' => true,
                'fileName' => $backupResult->fileName,
            ];
        } catch (Throwable $throwable) {
            $this->configuration->getLogger()->error('Scheduled backup creation failed.', [
                'message' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'fileName' => null,
            ];
        }
    }
}
