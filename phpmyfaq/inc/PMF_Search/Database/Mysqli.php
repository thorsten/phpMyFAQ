<?php
/**
 * phpMyFAQ MySQL (ext/mysqli) search classes
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
 * @copyright 2010-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Search_Database_Mysqli
 *
 * @category  phpMyFAQ
 * @package   PMF_Search_Database
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */
class PMF_Search_Database_Mysqli extends PMF_Search_Database
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

            $columns    =  $this->getResultColumns();
            $columns   .= ($enableRelevance) ? $this->getMatchingColumnsAsResult($searchTerm) : '';
            $orderBy    = ($enableRelevance) ? 'ORDER BY ' . $this->getMatchingOrder() . ' DESC' : '';
            $chars      = array (chr(150), chr(147), chr(148), chr(146), chr(34), '&quot;', '&#34;');
            $replace    = array ("-", "\"", "\"", "'", "\"" , "\"", "\"");
            $searchTerm = str_replace ($chars, $replace, $searchTerm);

            $query = sprintf("
                SELECT
                    %s
                FROM 
                    %s %s %s
                WHERE
                    MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE)
                    %s
                    %s",
                $columns,
                $this->getTable(),
                $this->getJoinedTable(),
                $this->getJoinedColumns(),
                $this->getMatchingColumns(),
                $this->dbHandle->escape($searchTerm),
                $this->getConditions(),
                $orderBy
            );

            $this->resultSet = $this->dbHandle->query($query);
            
            // Fallback for searches with less than three characters
            if (0 == $this->dbHandle->numRows($this->resultSet)) {
                
                $query = sprintf("
                    SELECT
                        %s
                    FROM 
                        %s %s %s
                    WHERE
                        %s
                        %s",
                    $this->getResultColumns(),
                    $this->getTable(),
                    $this->getJoinedTable(),
                    $this->getJoinedColumns(),
                    $this->getMatchClause($searchTerm),
                    $this->getConditions());
            }
            
            $this->resultSet = $this->dbHandle->query($query);
        }
        
        return $this->resultSet;
    }
    
    /**
     * Add the matching columns into the columns for the resultset
     *
     * @return PMF_Search_Database
     */
    public function getMatchingColumnsAsResult($searchterm)
    {
        $resultColumns = '';

        foreach ($this->matchingColumns as $matchColumn) {
            $column = sprintf("MATCH (%s) AGAINST ('*%s*' IN BOOLEAN MODE) AS rel_%s",
                $matchColumn,
                $this->dbHandle->escape($searchterm),
                substr(strstr($matchColumn, '.'), 1));

                $resultColumns .= ', ' . $column;
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
        $count  = count($list);
        $order  = '';

        foreach ($list as $field) {
            $string = '(rel_' . $field . '*' . $count .')';
            if (empty($order)) {
                $order .= $string;
            } else {
                $order .= '+' . $string;
            }
            $count--;
        }

        return $order;
    }

}