<?php

/**
 * The phpMyFAQ instances basic database class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-02-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Instance\Database\DriverInterface;

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
     * DROP TABLE statements.
     */
    private array $dropTableStmts = [
        'DROP TABLE %sfaqadminlog',
        'DROP TABLE %sfaqattachment',
        'DROP TABLE %sfaqattachment_file',
        'DROP TABLE %sfaqcaptcha',
        'DROP TABLE %sfaqcategories',
        'DROP TABLE %sfaqcategoryrelations',
        'DROP TABLE %sfaqcategory_group',
        'DROP TABLE %sfaqcategory_user',
        'DROP TABLE %sfaqchanges',
        'DROP TABLE %sfaqcomments',
        'DROP TABLE %sfaqconfig',
        'DROP TABLE %sfaqdata',
        'DROP TABLE %sfaqdata_revisions',
        'DROP TABLE %sfaqdata_group',
        'DROP TABLE %sfaqdata_tags',
        'DROP TABLE %sfaqdata_user',
        'DROP TABLE %sfaqglossary',
        'DROP TABLE %sfaqgroup',
        'DROP TABLE %sfaqgroup_right',
        'DROP TABLE %sfaqinstances',
        'DROP TABLE %sfaqinstances_config',
        'DROP TABLE %sfaqnews',
        'DROP TABLE %sfaqquestions',
        'DROP TABLE %sfaqright',
        'DROP TABLE %sfaqsearches',
        'DROP TABLE %sfaqsessions',
        'DROP TABLE %sfaqstopwords',
        'DROP TABLE %sfaqtags',
        'DROP TABLE %sfaquser',
        'DROP TABLE %sfaquserdata',
        'DROP TABLE %sfaquserlogin',
        'DROP TABLE %sfaquser_group',
        'DROP TABLE %sfaquser_right',
        'DROP TABLE %sfaqvisits',
        'DROP TABLE %sfaqvoting',
    ];

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
     * @param Configuration $configuration phpMyFAQ configuration container
     * @param string        $type Database management system type
     * @throws Exception
     */
    public static function factory(Configuration $configuration, string $type): ?DriverInterface
    {
        if (str_starts_with($type, 'pdo_')) {
            $class = 'phpMyFAQ\Instance\Database\Pdo' . ucfirst(substr($type, 4));
        } else {
            $class = 'phpMyFAQ\Instance\Database\\' . ucfirst($type);
        }

        if (class_exists($class)) {
            self::$driver = new $class($configuration);

            return self::$driver;
        }

        throw new Exception('Invalid Database Type: ' . $type);
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
     * Executes all DROP TABLE statements.
     */
    public function dropTables(string $prefix = ''): bool
    {
        foreach ($this->dropTableStmts as $dropTableStmt) {
            $result = $this->configuration->getDb()->query(sprintf($dropTableStmt, $prefix));

            if (!$result) {
                return false;
            }
        }

        return true;
    }
}
