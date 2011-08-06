<?php
/**
 * phpMyFAQ MySQL (ext/mysqli) search classes
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Search_Database
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
                $this->dbHandle->escape_string($searchTerm),
                $this->getConditions(),
                $orderBy
            );
            
            $this->resultSet = $this->dbHandle->query($query);
            
            // Fallback for searches with less than three characters
            if (false == $this->resultSet) {
                
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
}