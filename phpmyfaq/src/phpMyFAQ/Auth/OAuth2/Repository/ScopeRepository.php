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

        $scope = new ScopeEntity();
        $scope->setIdentifier((string) $row->scope_id);

        return $scope;
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
