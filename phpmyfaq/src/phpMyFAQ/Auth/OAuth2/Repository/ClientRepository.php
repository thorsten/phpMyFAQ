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
        if (!is_object($row)) {
            return null;
        }

        $entity = new ClientEntity();
        $entity->setIdentifier((string) $row->client_id);
        $entity->secret = $row->client_secret !== null ? (string) $row->client_secret : null;
        $entity->setName((string) ($row->name ?? $row->client_id));
        $entity->setRedirectUri((string) ($row->redirect_uri ?? ''));
        $entity->setConfidential((int) ($row->is_confidential ?? 1) === 1);

        $grants = array_filter(array_map('trim', explode(',', (string) ($row->grants ?? ''))));
        $entity->allowedGrants = array_values($grants);

        return $entity;
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
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
        if (($passwordInfo['algoName'] ?? 'unknown') !== 'unknown') {
            return password_verify($clientSecret, $storedSecret);
        }

        return hash_equals($storedSecret, $clientSecret);
    }
}
