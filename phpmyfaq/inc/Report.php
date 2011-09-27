<?php
/**
 * The reporting class for simple report generation
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
 * @package   PMF_Report
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2011-02-04
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Report
 *
 * @category  phpMyFAQ
 * @package   PMF_Report
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2011-02-04
 */
class PMF_Report
{
    /**
     * DB handle
     *
     * @var PMF_DB_Driver
     */
    private $db;

    /**
     * Language
     *
     * @var PMF_Language
     */
    private $language;

    /**
     * Constructor
     *
     * @param PMF_DB_Driver $database Database connection
     * @param PMF_Language  $language Language object
     *
     * @return PMF_Report
     */
    public function __construct(PMF_DB_Driver $database, PMF_Language $language)
    {
        $this->db       = $database;
        $this->language = $language;
    }

    /**
     * Generates a huge array for the report 
     * @return array
     */
    public function getReportingData()
    {
        $report = array();
        
        $query = sprintf("
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                c.name as category_name,
                c.parent_id as parent_id,
                fd.sticky AS sticky,
                fd.thema AS question,
                fd.author AS original_author,
                fd.datum AS creation_date,
                fv.visits AS visits,
                u.display_name AS last_author
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                (fd.id = fcr.record_id AND fd.lang = fcr.record_lang)
            LEFT JOIN
                %sfaqvisits fv
            ON
                (fd.id = fv.id AND fd.lang = fv.lang)
            LEFT JOIN
                faqchanges as fc
            ON
                (fd.id = fc.id AND fd.lang = fc.lang)
            LEFT JOIN
                %sfaquserdata as u
            ON
                (u.user_id = fc.usr)
            LEFT JOIN
                faqcategories as c
            ON
                (c.id = fcr.category_id AND c.lang = fcr.record_lang)
            ORDER BY
                fd.id
            ASC",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX
        );

        $result = $this->db->query($query);

        $lastId = 0;
        while ($row = $this->db->fetchObject($result)) {

            if ($row->id == $lastId) {
                $report[$row->id]['faq_translations'] += 1;
            } else {
                $report[$row->id] = array(
                    'faq_id'           => $row->id,
                    'faq_language'     => $row->lang,
                    'category_id'      => $row->category_id,
                    'category_parent'  => $row->parent_id,
                    'category_name'    => $row->category_name,
                    'faq_translations' => 0,
                    'faq_sticky'       => $row->sticky,
                    'faq_question'     => $row->question,
                    'faq_org_author'   => $row->original_author,
                    'faq_creation'     => PMF_Date::createIsoDate($row->creation_date),
                    'faq_visits'       => $row->visits,
                    'faq_last_author'  => $row->last_author
                );
            }
            $lastId = $row->id;
        }
        
        return $report;
    }

    /**
     * Convert string to the correct encoding
     *
     * @param string $outputString String to encode.
     *
     * @return string Encoded string.
     */
    public function convertEncoding($outputString)
    {
        $outputString = html_entity_decode($outputString, ENT_QUOTES, 'utf-8');
        $outputString = str_replace(',', ' ', $outputString);

        if (extension_loaded('mbstring')) {
            $detected = mb_detect_encoding($outputString);

            if ($detected !== 'ASCII') {
                $outputString = mb_convert_encoding($outputString, 'UTF-16', $detected);
            }
        }

        return $outputString;
    }
}
