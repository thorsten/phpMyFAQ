<?php
/**
 * phpMyFAQ PostgreSQL search classes
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Search_Database
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Search_Database_Pgsql
 *
 * @category  phpMyFAQ
 * @package   PMF_Search_Database
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */
class PMF_Search_Database_Pgsql extends PMF_Search_Database
{
    /**
     * Constructor
     *
     * @param PMF_Language $language Language
     *
     * @return PMF_Search_Abstract
     */
    public function __construct(PMF_Language $language)
    {
        parent::__construct($language);
    }

    /**
     * Prepares the search and executes it
     *
     * @param string $searchTerm Search term
     *
     * @return resource
     *
     * @throws PMF_Search_Exception
     */
    public function search($searchTerm)
    {
        if (is_numeric($searchTerm)) {
            parent::search($searchTerm);
        } else {
            $enableRelevance = PMF_Configuration::getInstance()->get('search.enableRelevance');

            $columns  =  $this->getResultColumns();
            $columns .= ($enableRelevance) ? $this->getMatchingColumnsAsResult($searchTerm) : '';

            $orderBy = ($enableRelevance) ? 'ORDER BY ' . $this->getMatchingOrder() : '';

            $query = sprintf("
                SELECT
                    %s
                FROM
                    %s %s %s %s
                WHERE
                    (%s) ILIKE ('%%%s%%')
                    %s
                    %s",
                $columns,
                $this->getTable(),
                $this->getJoinedTable(),
                $this->getJoinedColumns(),
                ($enableRelevance)
                    ? ", plainto_tsquery('" . $this->dbHandle->escape($searchTerm) . "') query "
                    : '',
                $this->getMatchingColumns(),
                $this->dbHandle->escape($searchTerm),
                $this->getConditions(),
                $orderBy);

            $this->resultSet = $this->dbHandle->query($query);
        }

        return $this->resultSet;
    }

    /**
     * Returns the part of the SQL query with the matching columns
     *
     * @return string
     */
    public function getMatchingColumns()
    {
        $enableRelevance = PMF_Configuration::getInstance()->get('search.enableRelevance');

        if ($enableRelevance) {
            $machColumns = '';

            foreach ($this->matchingColumns as $matchColumn) {
                $match = sprintf("to_tsvector(coalesce(%s,''))", $matchColumn);
                if (empty($machColumns)) {
                    $machColumns .= '(' . $match;
                } else {
                    $machColumns .= ' || ' . $match;
                }
            }

            // Add the ILIKE since the FULLTEXT looks for the exact phrase only
            $machColumns .= ') @@ query) OR (' . implode(" || ' ' || ", $this->matchingColumns);
        } else {
            $machColumns = implode(" || ' ' || ", $this->matchingColumns);
        }

        return $machColumns;
    }

    /**
     * Add the matching columns into the columns for the resultset
     *
     * @return PMF_Search_Database
     */
    public function getMatchingColumnsAsResult()
    {
        $resultColumns = '';
        $config        = PMF_Configuration::getInstance()->get('search.relevance');
        $list          = explode(",", $config);

        // Set weight
        $weights = array('A', 'B', 'C', 'D');
        $weight  = array();
        foreach ($list as $columnName) {
            $weight[$columnName] = array_shift($weights);
        }

        foreach ($this->matchingColumns as $matchColumn) {
            $columnName = substr(strstr($matchColumn, '.'), 1);

            if (isset($weight[$columnName])) {
                $column = sprintf("ts_rank_cd(setweight(to_tsvector(coalesce(%s,'')), '" . $weight[$columnName]
                    . "'), query) AS rel_%s",
                    $matchColumn,
                    $columnName);

                $resultColumns .= ', ' . $column;
            }
        }

        return $resultColumns;
    }

    /**
     * Returns the part of the SQL query with the order by
     *
     * The order is calculate by weight depend on the search.relevance order
     *
     * @return string
     */
    public function getMatchingOrder()
    {
        $config = PMF_Configuration::getInstance()->get('search.relevance');
        $list   = explode(",", $config);
        $order  = '';

        foreach ($list as $field) {
            $string = 'rel_' . $field . ' DESC';
            if (empty($order)) {
                $order .= $string;
            } else {
                $order .= ', ' . $string;
            }
        }

        return $order;
    }
}