<?php

/**
 * OAuth2 auth code repository.
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

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use phpMyFAQ\Auth\OAuth2\Entity\AuthCodeEntity;

final class AuthCodeRepository extends AbstractRepository implements AuthCodeRepositoryInterface
{
    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $scopes = array_map(
            static fn(ScopeEntityInterface $scope): string => $scope->getIdentifier(),
            $authCodeEntity->getScopes(),
        );

        $insert = sprintf(
            "INSERT INTO %s (identifier, client_id, user_id, redirect_uri, scopes, revoked, expires_at, created)
             VALUES ('%s', '%s', %s, %s, '%s', 0, '%s', %s)",
            $this->table('faqoauth_auth_codes'),
            $this->db()->escape($authCodeEntity->getIdentifier()),
            $this->db()->escape($authCodeEntity->getClient()->getIdentifier()),
            $authCodeEntity->getUserIdentifier() === null
                ? 'NULL'
                : "'" . $this->db()->escape($authCodeEntity->getUserIdentifier()) . "'",
            $authCodeEntity->getRedirectUri() === null
                ? 'NULL'
                : "'" . $this->db()->escape($authCodeEntity->getRedirectUri()) . "'",
            $this->db()->escape((string) json_encode($scopes)),
            $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
            $this->db()->now(),
        );

        if ($this->db()->query($insert) === false) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }
    }

    public function revokeAuthCode(string $codeId): void
    {
        $this->db()->query(sprintf(
            "UPDATE %s SET revoked = 1 WHERE identifier = '%s'",
            $this->table('faqoauth_auth_codes'),
            $this->db()->escape($codeId),
        ));
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        $result = $this->db()->query(sprintf(
            "SELECT revoked FROM %s WHERE identifier = '%s'",
            $this->table('faqoauth_auth_codes'),
            $this->db()->escape($codeId),
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
