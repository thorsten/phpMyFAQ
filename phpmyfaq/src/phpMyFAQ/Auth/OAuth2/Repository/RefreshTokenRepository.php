<?php

/**
 * OAuth2 refresh token repository.
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

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use phpMyFAQ\Auth\OAuth2\Entity\RefreshTokenEntity;

final class RefreshTokenRepository extends AbstractRepository implements RefreshTokenRepositoryInterface
{
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshTokenEntity();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $insert = sprintf(
            "INSERT INTO %s (identifier, access_token_identifier, revoked, expires_at, created)
             VALUES ('%s', '%s', 0, '%s', %s)",
            $this->table('faqoauth_refresh_tokens'),
            $this->db()->escape($refreshTokenEntity->getIdentifier()),
            $this->db()->escape($refreshTokenEntity->getAccessToken()->getIdentifier()),
            $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
            $this->db()->now(),
        );

        if ($this->db()->query($insert) === false) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        $this->db()->query(sprintf(
            "UPDATE %s SET revoked = 1 WHERE identifier = '%s'",
            $this->table('faqoauth_refresh_tokens'),
            $this->db()->escape($tokenId),
        ));
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        $result = $this->db()->query(sprintf(
            "SELECT revoked FROM %s WHERE identifier = '%s'",
            $this->table('faqoauth_refresh_tokens'),
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
