<?php

/**
 * Repository for push notification subscriptions.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Push
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Push;

use DateTimeImmutable;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\PushSubscriptionEntity;

readonly class PushSubscriptionRepository
{
    private string $table;

    public function __construct(
        private Configuration $configuration,
    ) {
        $this->table = Database::getTablePrefix() . 'faqpush_subscriptions';
    }

    /**
     * Saves a push subscription (upsert by endpoint_hash).
     *
     * Uses an atomic approach: attempts INSERT first, and if a duplicate key
     * constraint violation occurs, falls back to UPDATE to avoid race conditions.
     */
    public function save(PushSubscriptionEntity $entity): bool
    {
        $db = $this->configuration->getDb();
        $endpointHash = hash('sha256', $entity->getEndpoint());

        // Try to insert first (atomic approach to avoid race conditions)
        $nextId = $db->nextId($this->table, 'id');
        $insertQuery = sprintf(
            'INSERT INTO %s (id, user_id, endpoint, endpoint_hash, public_key, auth_token, content_encoding, created_at)'
            . " VALUES (%d, %d, '%s', '%s', '%s', '%s', '%s', %s)",
            $this->table,
            $nextId,
            $entity->getUserId(),
            $db->escape($entity->getEndpoint()),
            $db->escape($endpointHash),
            $db->escape($entity->getPublicKey()),
            $db->escape($entity->getAuthToken()),
            $db->escape($entity->getContentEncoding() ?? 'aesgcm'),
            $db->now(),
        );

        try {
            $result = $db->query($insertQuery);
            if ($result !== false) {
                return true;
            }
        } catch (\Throwable) {
            // Likely a duplicate key constraint violation, fall through to update
        }

        // INSERT failed (duplicate key constraint), perform UPDATE instead
        $updateQuery = sprintf(
            "UPDATE %s SET user_id = %d, endpoint = '%s', public_key = '%s', auth_token = '%s', "
            . "content_encoding = '%s' WHERE endpoint_hash = '%s'",
            $this->table,
            $entity->getUserId(),
            $db->escape($entity->getEndpoint()),
            $db->escape($entity->getPublicKey()),
            $db->escape($entity->getAuthToken()),
            $db->escape($entity->getContentEncoding() ?? 'aesgcm'),
            $db->escape($endpointHash),
        );

        try {
            return (bool) $db->query($updateQuery);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Deletes a subscription by endpoint hash.
     */
    public function deleteByEndpointHash(string $endpointHash): bool
    {
        $db = $this->configuration->getDb();
        $query = sprintf("DELETE FROM %s WHERE endpoint_hash = '%s'", $this->table, $db->escape($endpointHash));

        return (bool) $db->query($query);
    }

    /**
     * Deletes a subscription by endpoint hash scoped to a specific user.
     * This ensures users can only delete their own subscriptions.
     */
    public function deleteByEndpointHashAndUserId(string $endpointHash, int $userId): bool
    {
        $db = $this->configuration->getDb();
        $query = sprintf(
            "DELETE FROM %s WHERE endpoint_hash = '%s' AND user_id = %d",
            $this->table,
            $db->escape($endpointHash),
            $userId,
        );

        return (bool) $db->query($query);
    }

    /**
     * Deletes all subscriptions for a user.
     */
    public function deleteByUserId(int $userId): bool
    {
        $db = $this->configuration->getDb();
        $query = sprintf('DELETE FROM %s WHERE user_id = %d', $this->table, $userId);

        return (bool) $db->query($query);
    }

    /**
     * Deletes a subscription by its endpoint URL.
     */
    public function deleteByEndpoint(string $endpoint): bool
    {
        $endpointHash = hash('sha256', $endpoint);
        return $this->deleteByEndpointHash($endpointHash);
    }

    /**
     * Gets all subscriptions for a specific user.
     *
     * @return PushSubscriptionEntity[]
     */
    public function getByUserId(int $userId): array
    {
        $db = $this->configuration->getDb();
        $query = sprintf('SELECT * FROM %s WHERE user_id = %d ORDER BY created_at DESC', $this->table, $userId);

        $result = $db->query($query);
        if ($result === false) {
            return [];
        }

        $subscriptions = [];

        $row = $db->fetchObject($result);
        while ($row) {
            $subscriptions[] = $this->mapRowToEntity($row);
            $row = $db->fetchObject($result);
        }

        return $subscriptions;
    }

    /**
     * Gets all subscriptions for multiple users.
     *
     * @param int[] $userIds
     * @return PushSubscriptionEntity[]
     */
    public function getByUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $db = $this->configuration->getDb();
        $ids = implode(',', array_map('intval', $userIds));
        $query = sprintf('SELECT * FROM %s WHERE user_id IN (%s) ORDER BY created_at DESC', $this->table, $ids);

        $result = $db->query($query);
        if ($result === false) {
            return [];
        }

        $subscriptions = [];

        $row = $db->fetchObject($result);
        while ($row) {
            $subscriptions[] = $this->mapRowToEntity($row);
            $row = $db->fetchObject($result);
        }

        return $subscriptions;
    }

    /**
     * Gets all subscriptions.
     *
     * @return PushSubscriptionEntity[]
     */
    public function getAll(): array
    {
        $db = $this->configuration->getDb();
        $query = sprintf('SELECT * FROM %s ORDER BY created_at DESC', $this->table);

        $result = $db->query($query);
        if ($result === false) {
            return [];
        }

        $subscriptions = [];

        $row = $db->fetchObject($result);
        while ($row) {
            $subscriptions[] = $this->mapRowToEntity($row);
            $row = $db->fetchObject($result);
        }

        return $subscriptions;
    }

    /**
     * Checks if a user has any active subscriptions.
     */
    public function hasSubscription(int $userId): bool
    {
        $db = $this->configuration->getDb();
        $query = sprintf('SELECT id FROM %s WHERE user_id = %d', $this->table, $userId);

        $result = $db->query($query);
        if ($result === false) {
            return false;
        }

        return (bool) $db->fetchObject($result);
    }

    private function mapRowToEntity(object $row): PushSubscriptionEntity
    {
        $entity = new PushSubscriptionEntity();

        try {
            $createdAt = new DateTimeImmutable($row->created_at);
        } catch (\Exception) {
            // Fallback to current time if created_at is malformed
            $createdAt = new DateTimeImmutable();
        }

        $entity
            ->setId((int) $row->id)
            ->setUserId((int) $row->user_id)
            ->setEndpoint($row->endpoint)
            ->setEndpointHash($row->endpoint_hash)
            ->setPublicKey($row->public_key)
            ->setAuthToken($row->auth_token)
            ->setContentEncoding($row->content_encoding)
            ->setCreatedAt($createdAt);

        return $entity;
    }
}
