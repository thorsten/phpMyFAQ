<?php

/**
 * Tenant quota enforcer.
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
 * @since     2026-02-10
 */

declare(strict_types=1);

namespace phpMyFAQ\Tenant;

use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use RuntimeException;

final readonly class TenantQuotaEnforcer
{
    public function __construct(
        private DatabaseDriver $databaseDriver,
        private TenantContext $tenantContext,
    ) {
    }

    public static function createFromDatabaseDriver(DatabaseDriver $databaseDriver): self
    {
        return new self($databaseDriver, new TenantContextResolver()->resolve());
    }

    public function assertCanCreateFaq(): void
    {
        $this->assertCountWithinLimit($this->tenantContext->getQuotas()->getMaxFaqs(), 'faqdata', 'maxFaqs', 'FAQs');
    }

    public function assertCanCreateCategory(): void
    {
        $this->assertCountWithinLimit(
            $this->tenantContext->getQuotas()->getMaxCategories(),
            'faqcategories',
            'maxCategories',
            'categories',
        );
    }

    public function assertCanCreateUser(): void
    {
        $this->assertCountWithinLimit($this->tenantContext->getQuotas()->getMaxUsers(), 'faquser', 'maxUsers', 'users');
    }

    public function assertCanStoreAttachment(int $newAttachmentSizeBytes): void
    {
        $maxAttachmentSizeMb = $this->tenantContext->getQuotas()->getMaxAttachmentSize();
        if ($maxAttachmentSizeMb === null) {
            return;
        }

        $currentSizeBytes = $this->readAttachmentSizeBytes();
        $maxSizeBytes = $maxAttachmentSizeMb * 1024 * 1024;

        if (($currentSizeBytes + $newAttachmentSizeBytes) > $maxSizeBytes) {
            throw new QuotaExceededException(sprintf(
                'Tenant quota exceeded for maxAttachmentSize: %d MB (used: %d bytes, requested: %d bytes)',
                $maxAttachmentSizeMb,
                $currentSizeBytes,
                $newAttachmentSizeBytes,
            ));
        }
    }

    private function assertCountWithinLimit(?int $limit, string $table, string $quotaKey, string $resourceName): void
    {
        if ($limit === null) {
            return;
        }

        $query = sprintf('SELECT COUNT(1) AS amount FROM %s%s', Database::getTablePrefix(), $table);
        $result = $this->databaseDriver->query($query);

        if (!$result) {
            throw new RuntimeException(sprintf(
                'Failed to evaluate tenant quota for %s: %s',
                $resourceName,
                $this->databaseDriver->error(),
            ));
        }

        $row = $this->databaseDriver->fetchArray($result);
        $currentCount = $this->extractFirstInt($row);

        if ($currentCount >= $limit) {
            throw new QuotaExceededException(sprintf(
                'Tenant quota exceeded for %s: limit=%d current=%d',
                $quotaKey,
                $limit,
                $currentCount,
            ));
        }
    }

    private function readAttachmentSizeBytes(): int
    {
        $query = sprintf(
            'SELECT COALESCE(SUM(filesize), 0) AS amount FROM %sfaqattachment',
            Database::getTablePrefix(),
        );
        $result = $this->databaseDriver->query($query);

        if (!$result) {
            throw new RuntimeException(
                'Failed to evaluate tenant quota for attachments: ' . $this->databaseDriver->error(),
            );
        }

        return $this->extractFirstInt($this->databaseDriver->fetchArray($result));
    }

    private function extractFirstInt(array|false|null $row): int
    {
        if ($row === false || $row === null || $row === []) {
            return 0;
        }

        $firstValue = reset($row);
        if ($firstValue === false) {
            return 0;
        }

        return (int) $firstValue;
    }
}
