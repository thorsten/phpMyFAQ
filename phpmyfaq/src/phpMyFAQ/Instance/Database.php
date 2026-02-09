<?php

/**
 * The phpMyFAQ instance basic database class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-02-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Instance\Database\DriverInterface;
use phpMyFAQ\Setup\Installation\SchemaInstaller;
use phpMyFAQ\Setup\Migration\QueryBuilder\DialectFactory;

/**
 * Class Database
 *
 * @package phpMyFAQ\Instance
 */
class Database
{
    /**
     * Instance.
     */
    private static ?DriverInterface $driver = null;

    /**
     * Constructor.
     */
    private function __construct(
        protected Configuration $configuration,
    ) {
    }

    /**
     * Database factory.
     *
     * Returns a SchemaInstaller that uses the dialect-agnostic DatabaseSchema.
     *
     * @param Configuration $configuration phpMyFAQ configuration container
     * @param string        $type Database management system type
     * @throws Exception
     */
    public static function factory(Configuration $configuration, string $type): ?DriverInterface
    {
        try {
            $dialect = DialectFactory::createForType(strtolower($type));
        } catch (\InvalidArgumentException) {
            throw new Exception('Invalid Database Type: ' . $type);
        }

        self::$driver = new SchemaInstaller($configuration, $dialect);

        return self::$driver;
    }

    /**
     * Returns the single instance.
     */
    public static function getInstance(): ?DriverInterface
    {
        if (!self::$driver instanceof DriverInterface) {
            $className = self::class;
            self::$driver = new $className();
        }

        return self::$driver;
    }

    /**
     * Creates a dedicated tenant database for supported drivers (PostgreSQL, SQL Server).
     *
     * @throws \RuntimeException if the driver does not support database-per-tenant isolation
     */
    public static function createTenantDatabase(Configuration $configuration, string $type, string $databaseName): bool
    {
        $normalizedType = strtolower($type);

        if (!preg_match('/^[A-Za-z0-9_]+$/', $databaseName)) {
            throw new \InvalidArgumentException(sprintf('Invalid tenant database identifier: "%s".', $databaseName));
        }

        if (str_contains($normalizedType, 'pgsql')) {
            $existsQuery = sprintf(
                "SELECT 1 FROM pg_database WHERE datname = '%s'",
                $configuration->getDb()->escape($databaseName),
            );
            $existsResult = $configuration->getDb()->query($existsQuery);
            if ($existsResult !== false && $configuration->getDb()->numRows($existsResult) > 0) {
                return true;
            }

            return (bool) $configuration->getDb()->query(sprintf('CREATE DATABASE "%s"', $databaseName));
        }

        if (str_contains($normalizedType, 'sqlsrv')) {
            return (bool) $configuration
                ->getDb()
                ->query(sprintf(
                    "IF DB_ID('%s') IS NULL CREATE DATABASE [%s]",
                    $configuration->getDb()->escape($databaseName),
                    $databaseName,
                ));
        }

        throw new \RuntimeException(sprintf(
            'Database-per-tenant isolation is not supported for driver "%s". Use PostgreSQL or SQL Server.',
            $type,
        ));
    }
}
