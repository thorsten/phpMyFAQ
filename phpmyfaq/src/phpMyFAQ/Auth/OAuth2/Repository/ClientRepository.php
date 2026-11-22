<?php

/**
 * OAuth2 client repository.
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

namespace phpMyFAQ\Auth\OAuth2\Repository;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use phpMyFAQ\Auth\OAuth2\ClientRegistration;
use phpMyFAQ\Auth\OAuth2\Entity\ClientEntity;

final class ClientRepository extends AbstractRepository implements ClientRepositoryInterface
{
    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $query = sprintf(
            "SELECT client_id, client_secret, name, redirect_uri, grants, is_confidential
             FROM %s
             WHERE client_id = '%s'",
            $this->table('faqoauth_clients'),
            $this->db()->escape($clientIdentifier),
        );

        $result = $this->db()->query($query);
        if ($result === false) {
            return null;
        }

        $row = $this->db()->fetchObject($result);

        return is_object($row) ? $this->toEntity($row) : null;
    }

    /**
     * Every registered client, ordered by name.
     *
     * @return list<ClientEntity>
     */
    public function all(): array
    {
        $result = $this->db()->query(sprintf(
            'SELECT client_id, client_secret, name, redirect_uri, grants, is_confidential FROM %s ORDER BY name',
            $this->table('faqoauth_clients'),
        ));

        if ($result === false) {
            return [];
        }

        $clients = [];
        while (true) {
            $row = $this->db()->fetchObject($result);
            if (!is_object($row)) {
                break;
            }

            $client = $this->toEntity($row);
            if ($client instanceof ClientEntity) {
                $clients[] = $client;
            }
        }

        return $clients;
    }

    /**
     * @param string|null $secretHash password_hash() of the secret, null for a public client
     * @param int $userId The administrator who registers the client
     */
    public function register(
        string $clientIdentifier,
        ClientRegistration $registration,
        #[\SensitiveParameter]
        ?string $secretHash,
        int $userId,
    ): bool {
        $result = $this->db()->queryPrepared(
            sprintf(
                'INSERT INTO %s (client_id, client_secret, name, redirect_uri, grants, is_confidential, user_id, created)
                 VALUES (?, ?, ?, ?, ?, ?, ?, %s)',
                $this->table('faqoauth_clients'),
                $this->db()->now(),
            ),
            [
                $clientIdentifier,
                $secretHash,
                $registration->name,
                $registration->redirectUri,
                implode(',', $registration->grantValues()),
                $registration->confidential ? 1 : 0,
                $userId,
            ],
        );

        return $result !== false && $result !== null;
    }

    /**
     * Changes the name, the redirect URI and the grants. Whether a client is confidential is
     * decided when it is registered: its secret is created then and shown only once.
     */
    public function update(string $clientIdentifier, ClientRegistration $registration): bool
    {
        $result = $this->db()->queryPrepared(
            sprintf(
                'UPDATE %s SET name = ?, redirect_uri = ?, grants = ? WHERE client_id = ?',
                $this->table('faqoauth_clients'),
            ),
            [
                $registration->name,
                $registration->redirectUri,
                implode(',', $registration->grantValues()),
                $clientIdentifier,
            ],
        );

        return $result !== false && $result !== null;
    }

    /**
     * Removes the client together with everything issued to it. An access token without a
     * stored row counts as revoked, so the tokens of the client stop working at once.
     */
    public function delete(string $clientIdentifier): bool
    {
        $statements = [
            sprintf(
                'DELETE FROM %s WHERE access_token_identifier IN (SELECT identifier FROM %s WHERE client_id = ?)',
                $this->table('faqoauth_refresh_tokens'),
                $this->table('faqoauth_access_tokens'),
            ),
            sprintf('DELETE FROM %s WHERE client_id = ?', $this->table('faqoauth_access_tokens')),
            sprintf('DELETE FROM %s WHERE client_id = ?', $this->table('faqoauth_auth_codes')),
            sprintf('DELETE FROM %s WHERE client_id = ?', $this->table('faqoauth_clients')),
        ];

        foreach ($statements as $statement) {
            $result = $this->db()->queryPrepared($statement, [$clientIdentifier]);
            if ($result === false || $result === null) {
                return false;
            }
        }

        return true;
    }

    private function toEntity(object $row): ?ClientEntity
    {
        $clientIdentifier = (string) ($row->client_id ?? '');
        if ($clientIdentifier === '') {
            return null;
        }

        $entity = new ClientEntity();
        $entity->setIdentifier($clientIdentifier);
        $entity->secret = ($row->client_secret ?? null) !== null ? (string) $row->client_secret : null;
        $entity->setName((string) ($row->name ?? $row->client_id));
        $entity->setRedirectUri((string) ($row->redirect_uri ?? ''));
        $entity->setConfidential((int) ($row->is_confidential ?? 1) === 1);

        $grants = array_filter(array_map('trim', explode(',', (string) ($row->grants ?? ''))));
        $entity->allowedGrants = array_values($grants);

        return $entity;
    }

    public function validateClient(
        string $clientIdentifier,
        #[\SensitiveParameter]
        ?string $clientSecret,
        ?string $grantType,
    ): bool {
        $client = $this->getClientEntity($clientIdentifier);
        if (!$client instanceof ClientEntity) {
            return false;
        }

        if ($grantType !== null && !$client->supportsGrantType($grantType)) {
            return false;
        }

        if (!$client->isConfidential()) {
            return true;
        }

        $storedSecret = $client->secret;
        if ($storedSecret === null || $clientSecret === null) {
            return false;
        }

        $passwordInfo = password_get_info($storedSecret);
        if ($passwordInfo['algoName'] !== 'unknown') {
            return password_verify($clientSecret, $storedSecret);
        }

        if (!hash_equals($storedSecret, $clientSecret)) {
            return false;
        }

        // A secret that is still stored in clear text is accepted this one time and
        // replaced by its hash, so a database read no longer yields a usable credential.
        $this->storeHashedSecret($clientIdentifier, $clientSecret);

        return true;
    }

    private function storeHashedSecret(string $clientIdentifier, #[\SensitiveParameter] string $clientSecret): void
    {
        $this->db()->queryPrepared(
            sprintf('UPDATE %s SET client_secret = ? WHERE client_id = ?', $this->table('faqoauth_clients')),
            [password_hash($clientSecret, PASSWORD_BCRYPT), $clientIdentifier],
        );
    }
}
