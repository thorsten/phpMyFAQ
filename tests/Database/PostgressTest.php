<?php
/**
 * Postgres connector.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @package   PMF_Tests
 * @author Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2010 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-01-03
 */

/**
 * Class Database_PostgresTest
 */
abstract class Database_PostgresTest extends Database_AbstractTest
{
    /**
     * Return the Postgres server settings.
     *
     * @return array
     */    
    protected function getDbData()
    {
        return [
            "server"   => 'localhost',
            "user"     => 'postgres',
            "password" => '-',
            "db"       => 'phpmyfaqtest',
            "prefix"   => '',
            "type"     => 'pgsql',
            'port'     => 5432
        ];
    }
}
