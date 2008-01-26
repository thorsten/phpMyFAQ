<?php
/**
 * The phpMyFAQ Search class
 *
 * @package   phpMyFAQ
 * @license   MPL
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2008 phpMyFAQ Team
 * @version   CVS: $Id: Search.php,v 1.1 2008-01-26 11:33:06 thorstenr Exp $
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
 */

class PMF_Search
{
    /**
     * The database handle
     *
     * @var mixed
     */
    private $db = null;

    /**
     * Constructor
     *
     * @param  object $db Database handle
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function __construct($db)
    {
        $this->db =& $db;
    }

    /**
     * The main search function for the full text search
     *
     * TODO: add filter for (X)HTML tag names and attributes!
     *
     * @param   string  $searchterm     Text/Number (solution id)
     * @param   string  $searchcategory '%' to avoid any category filtering
     * @param   boolean $allLanguages   true to search over all languages
     * @param   boolean $hasMore        true to disable the results paging
     * @param   boolean $instantRespnse true to use it for Instant Response
     * @return  array
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @author  Adrianna Musiol <musiol@imageaccess.de>
     * @since   2002-09-16
     */
    public function search($searchterm, $searchcategory = '%', $allLanguages = true, $hasMore = false, $instantRespnse = false)
    {


        $condition = array(SQLPREFIX . 'faqdata.active' => "'yes'");

        // Search in all or one category?
        if ($searchcategory != '%') {
            $selectedCategory = array(SQLPREFIX . 'faqcategoryrelations.category_id' => $searchcategory);
            $condition        = array_merge($selectedCategory, $condition);
        }

        if ((!$allLanguages) && (!is_numeric($searchterm))) {
            $selectedLanguage = array(SQLPREFIX . 'faqdata.lang' => "'".$LANGCODE."'");
            $condition        = array_merge($selectedLanguage, $condition);
        }


        if (is_numeric($searchterm)) {
            // search for the solution_id
            $result = $this->db->search(SQLPREFIX.'faqdata',
                array(
                SQLPREFIX.'faqdata.id AS id',
                SQLPREFIX.'faqdata.lang AS lang',
                SQLPREFIX.'faqdata.solution_id AS solution_id',
                SQLPREFIX.'faqcategoryrelations.category_id AS category_id',
                SQLPREFIX.'faqdata.thema AS thema',
                SQLPREFIX.'faqdata.content AS content'),
                SQLPREFIX.'faqcategoryrelations',
                array(SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id',
                      SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang'),
                array(SQLPREFIX.'faqdata.solution_id'),
                $searchterm,
                $condition);
        } else {
            $result = $this->db->search(SQLPREFIX."faqdata",
                array(
                SQLPREFIX."faqdata.id AS id",
                SQLPREFIX."faqdata.lang AS lang",
                SQLPREFIX."faqcategoryrelations.category_id AS category_id",
                SQLPREFIX."faqdata.thema AS thema",
                SQLPREFIX."faqdata.content AS content"),
                SQLPREFIX."faqcategoryrelations",
                array(SQLPREFIX."faqdata.id = ".SQLPREFIX."faqcategoryrelations.record_id",
                      SQLPREFIX."faqdata.lang = ".SQLPREFIX."faqcategoryrelations.record_lang"),
                array(SQLPREFIX."faqdata.thema",
                SQLPREFIX."faqdata.content",
                SQLPREFIX."faqdata.keywords"),
                $searchterm,
                $condition);
        }


        if ($result) {
            $num = $db->num_rows($result);
        }

    }

    /**
     * Logging of search terms for improvements
     *
     * @param  string $searchterm Search term
     * @return void
     * @static
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    private static function logSearchTerm($searchterm)
    {

    }
}