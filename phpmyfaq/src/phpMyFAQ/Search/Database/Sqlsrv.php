<?php

namespace phpMyFAQ\Search\Database;

/**
 * phpMyFAQ SQL Server Driver for PHP based search classes.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 *
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link https://www.phpmyfaq.de
 * @since 2010-07-06
 */

use phpMyFAQ\Configuration;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Search_Database_Sqlsrv.
 *
 * @package phpMyFAQ
 *
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link https://www.phpmyfaq.de
 * @since 2010-07-06
 */
class Sqlsrv extends SearchDatabase
{
    /**
     * Constructor.
     *
     * @param Configuration
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);
    }

    /**
     * Prepares the search and executes it.
     *
     * @param string $searchTerm Search term
     *
     * @return resource
     *
     * @throws PMF_Search_Exception
     */
    public function search($searchTerm)
    {
        if (is_numeric($searchTerm) && $this->_config->get('search.searchForSolutionId')) {
            parent::search($searchTerm);
        } else {
            $query = sprintf('
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
                $this->getConditions());

            $this->resultSet = $this->_config->getDb()->query($query);
        }

        return $this->resultSet;
    }
}
