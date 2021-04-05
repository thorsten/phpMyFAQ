<?php

/**
 * phpMyFAQ SQL Server Driver for PHP based search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-07-06
 */

namespace phpMyFAQ\Search\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Search\SearchDatabase;
use stdClass;

/**
 * Class Sqlsrv
 *
 * @package phpMyFAQ\Search\Database
 */
class Sqlsrv extends SearchDatabase
{
    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);
    }

    /**
     * Prepares the search and executes it.
     *
     * @param  string $searchTerm Search term
     * @throws \Exception
     * @return mixed
     */
    public function search(string $searchTerm)
    {
        if (is_numeric($searchTerm) && $this->config->get('search.searchForSolutionId')) {
            parent::search($searchTerm);
        } else {
            $query = sprintf(
                '
                SELECT
                    %s
                FROM 
                    %s %s %s
                WHERE
                    %s
                    %s',
                $this->getResultColumns(),
                $this->getTable(),
                $this->getJoinedTable(),
                $this->getJoinedColumns(),
                $this->getMatchClause($searchTerm),
                $this->getConditions()
            );

            $this->resultSet = $this->config->getDb()->query($query);
        }

        return $this->resultSet;
    }
}
