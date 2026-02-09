<?php

/**
 * OAuth2 user repository.
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
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Auth\OAuth2\Entity\UserEntity;

final class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    public function getUserEntityByUserCredentials(
        string $username,
        string $password,
        string $grantType,
        ClientEntityInterface $clientEntity,
    ): ?UserEntityInterface {
        try {
            $authDatabase = new AuthDatabase($this->configuration);
            $authDatabase->getEncryptionContainer('sha1');
            if (!$authDatabase->checkCredentials($username, $password)) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        $query = sprintf(
            "SELECT user_id FROM %s WHERE login = '%s' LIMIT 1",
            $this->table('faquser'),
            $this->db()->escape($username),
        );

        $result = $this->db()->query($query);
        if ($result === false) {
            return null;
        }

        $row = $this->db()->fetchObject($result);
        if (!is_object($row) || !isset($row->user_id)) {
            return null;
        }

        $user = new UserEntity();
        $user->setIdentifier((string) $row->user_id);

        return $user;
    }
}
