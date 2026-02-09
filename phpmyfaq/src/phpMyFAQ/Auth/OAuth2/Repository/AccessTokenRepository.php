<?php

/**
 * OAuth2 access token repository.
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

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use phpMyFAQ\Auth\OAuth2\Entity\AccessTokenEntity;

final class AccessTokenRepository extends AbstractRepository implements AccessTokenRepositoryInterface
{
    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        ?string $userIdentifier = null,
    ): AccessTokenEntityInterface {
        $token = new AccessTokenEntity();
        $token->setClient($clientEntity);

        foreach ($scopes as $scope) {
            if ($scope instanceof ScopeEntityInterface) {
                $token->addScope($scope);
            }
        }

        if ($userIdentifier !== null) {
            $token->setUserIdentifier($userIdentifier);
        }

        return $token;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $scopes = array_map(
            static fn(ScopeEntityInterface $scope): string => $scope->getIdentifier(),
            $accessTokenEntity->getScopes(),
        );

        $insert = sprintf(
            "INSERT INTO %s (identifier, client_id, user_id, scopes, revoked, expires_at, created)
             VALUES ('%s', '%s', %s, '%s', 0, '%s', %s)",
            $this->table('faqoauth_access_tokens'),
            $this->db()->escape($accessTokenEntity->getIdentifier()),
            $this->db()->escape($accessTokenEntity->getClient()->getIdentifier()),
            $accessTokenEntity->getUserIdentifier() === null
                ? 'NULL'
                : "'" . $this->db()->escape($accessTokenEntity->getUserIdentifier()) . "'",
            $this->db()->escape((string) json_encode($scopes)),
            $this->db()->escape($accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s')),
            $this->db()->now(),
        );

        if ($this->db()->query($insert) === false) {
            $dbError = strtolower($this->db()->error());
            if (str_contains($dbError, 'duplicate') || str_contains($dbError, 'unique')) {
                throw UniqueTokenIdentifierConstraintViolationException::create();
            }

            throw new \RuntimeException('Failed to persist access token: ' . $this->db()->error());
        }
    }

    public function revokeAccessToken(string $tokenId): void
    {
        $this->db()->query(sprintf(
            "UPDATE %s SET revoked = 1 WHERE identifier = '%s'",
            $this->table('faqoauth_access_tokens'),
            $this->db()->escape($tokenId),
        ));
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        $result = $this->db()->query(sprintf(
            "SELECT revoked FROM %s WHERE identifier = '%s'",
            $this->table('faqoauth_access_tokens'),
            $this->db()->escape($tokenId),
        ));

        if ($result === false) {
            return true;
        }

        $row = $this->db()->fetchObject($result);
        if (!is_object($row)) {
            return true;
        }

        return (int) ($row->revoked ?? 1) === 1;
    }
}
