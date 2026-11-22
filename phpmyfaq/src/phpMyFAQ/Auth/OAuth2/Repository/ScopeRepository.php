<?php

/**
 * OAuth2 scope repository.
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
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use phpMyFAQ\Auth\OAuth2\Entity\ScopeEntity;

final class ScopeRepository extends AbstractRepository implements ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        $query = sprintf(
            "SELECT scope_id FROM %s WHERE scope_id = '%s'",
            $this->table('faqoauth_scopes'),
            $this->db()->escape($identifier),
        );

        $result = $this->db()->query($query);
        if ($result === false) {
            return null;
        }

        $row = $this->db()->fetchObject($result);
        if (!is_object($row)) {
            return null;
        }

        $scopeIdentifier = (string) $row->scope_id;
        if ($scopeIdentifier === '') {
            return null;
        }

        $scope = new ScopeEntity();
        $scope->setIdentifier($scopeIdentifier);

        return $scope;
    }

    /**
     * The scopes a client may request, with their descriptions.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        $result = $this->db()->query(sprintf(
            'SELECT scope_id, description FROM %s ORDER BY scope_id',
            $this->table('faqoauth_scopes'),
        ));

        if ($result === false) {
            return [];
        }

        $scopes = [];
        while (true) {
            $row = $this->db()->fetchObject($result);
            if (!is_object($row)) {
                break;
            }

            $scopes[(string) $row->scope_id] = (string) ($row->description ?? '');
        }

        return $scopes;
    }

    /**
     * Makes the scope requestable, or changes the description of one that already is.
     */
    public function enable(string $identifier, string $description): bool
    {
        if (!$this->disable($identifier)) {
            return false;
        }

        $result = $this->db()->queryPrepared(
            sprintf('INSERT INTO %s (scope_id, description) VALUES (?, ?)', $this->table('faqoauth_scopes')),
            [$identifier, $description],
        );

        return $result !== false && $result !== null;
    }

    /**
     * Tokens issued before keep the scope until they expire; new ones cannot request it.
     */
    public function disable(string $identifier): bool
    {
        $result = $this->db()->queryPrepared(
            sprintf('DELETE FROM %s WHERE scope_id = ?', $this->table('faqoauth_scopes')),
            [$identifier],
        );

        return $result !== false && $result !== null;
    }

    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {
        return $scopes;
    }
}
