<?php

/**
 * API key authenticator.
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
 * @since     2026-02-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use Symfony\Component\HttpFoundation\Request;

final class ApiKeyAuthenticator
{
    /** @var array<string, mixed>|null */
    private ?array $authenticatedApiKeyStorage = null;

    /**
     * Authenticated API key context of the current request.
     *
     * @var array<string, mixed>|null
     */
    public ?array $authenticatedApiKey {
        get => $this->authenticatedApiKeyStorage;
    }

    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * @param string[] $requiredScopes
     */
    public function authenticate(Request $request, array $requiredScopes = []): bool
    {
        $this->authenticatedApiKeyStorage = null;

        $header = trim((string) $request->headers->get('Authorization', ''));
        if (!preg_match('/^Bearer\s+(pmf_[A-Za-z0-9]+)$/', $header, $matches)) {
            return false;
        }

        $apiKey = $matches[1];
        $db = $this->configuration->getDb();
        $sql = sprintf("SELECT id, user_id, api_key, name, scopes, last_used_at, expires_at, created
             FROM %sfaqapi_keys
             WHERE api_key = '%s'", Database::getTablePrefix(), $db->escape($apiKey));

        $result = $db->query($sql);
        if ($result === false) {
            return false;
        }

        $row = $db->fetchObject($result);
        if (!is_object($row)) {
            return false;
        }

        if (!$this->isNotExpired($row->expires_at ?? null)) {
            return false;
        }

        $grantedScopes = $this->parseScopes($row->scopes ?? '[]');
        if (!$this->hasRequiredScopes($grantedScopes, $requiredScopes)) {
            return false;
        }

        $db->query(sprintf(
            'UPDATE %sfaqapi_keys SET last_used_at = %s WHERE id = %d',
            Database::getTablePrefix(),
            $db->now(),
            (int) $row->id,
        ));

        $this->authenticatedApiKeyStorage = [
            'id' => (int) $row->id,
            'user_id' => (int) $row->user_id,
            'api_key' => (string) $row->api_key,
            'name' => (string) ($row->name ?? ''),
            'scopes' => $grantedScopes,
            'last_used_at' => $row->last_used_at ?? null,
            'expires_at' => $row->expires_at ?? null,
            'created' => $row->created ?? null,
        ];

        return true;
    }

    public function getAuthenticatedUserId(): ?int
    {
        return $this->authenticatedApiKeyStorage['user_id'] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAuthenticatedApiKey(): ?array
    {
        return $this->authenticatedApiKey;
    }

    private function isNotExpired(mixed $expiresAt): bool
    {
        if ($expiresAt === null || $expiresAt === '') {
            return true;
        }

        $timestamp = strtotime((string) $expiresAt);
        if ($timestamp === false) {
            return false;
        }

        return $timestamp >= time();
    }

    /**
     * @return string[]
     */
    private function parseScopes(mixed $scopes): array
    {
        if (!is_string($scopes) || trim($scopes) === '') {
            return [];
        }

        $decoded = json_decode($scopes, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn(mixed $scope): bool => is_string($scope)));
    }

    /**
     * @param string[] $grantedScopes
     * @param string[] $requiredScopes
     */
    private function hasRequiredScopes(array $grantedScopes, array $requiredScopes): bool
    {
        if ($requiredScopes === []) {
            return true;
        }

        return array_all($requiredScopes, fn($requiredScope) => in_array($requiredScope, $grantedScopes, true));
    }
}
