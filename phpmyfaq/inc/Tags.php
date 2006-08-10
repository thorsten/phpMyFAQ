<?php
/**
* $Id: Tags.php,v 1.2 2006-08-10 19:50:20 thorstenr Exp $
*
* The main Tags class
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @package      phpMyFAQ
* @since        2006-08-10
* @copyright    (c) 2006 phpMyFAQ Team
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

class PMF_Tags
{
    /**
     * DB handle
     *
     * @var object
     */
    var $db;

    /**
     * Language
     *
     * @var string
     */
    var $language;
    
    /**
     * Constructor
     *
     * @param   object  PMF_Db
     * @param   string  $language
     * @since   2006-08-10
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function PMF_Tags(&$db, $language)
    {
        $this->db = &$db;
        $this->language = $language;
    }
    
    /**
     * Returns all tags for a FAQ record
     *
     * @param   integer $reord_id
     * @array   array   $tags
     * @access  public
     * @since   2006-08-10
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getAllTagsById($record_id)
    {
        $tags = array();
        
        
        
        return $tags;
    }
    
    /**
     * Returns the FAQ record IDs where all tags are included
     *
     * @param   array   $arrayOfTags
     * @return  array   $records
     * @access  public
     * @since   2006-08-10
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getRecordsByIntersectionTags($arrayOfTags)
    {
        if (!is_array($arrayOfTags)) {
            return false;
        }
        
        $query = sprintf("
            SELECT
                d.record_id AS record_id
            FROM
                %sfaqdata_tags d, %sfaqtags t
            WHERE
                t.tagging_id = d.tagging_id
            AND
                (t.tagging_name IN ('%s'))
            GROUP BY
                d.record_id
            HAVING
                COUNT(d.record_id) = %d",
            SQLPREFIX,
            SQLPREFIX,
            implode("', '", $arrayOfTags),
            count($arrayOfTags));
        
        $records = array();
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_object($result)) {
            $records[] = $row->record_id;
        }
        
        return $records;
    }

    /**
     * Returns all FAQ record IDs where all tags are included
     *
     * @param   array   $arrayOfTags
     * @return  array   $records
     * @access  public
     * @since   2006-08-10
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getRecordsByUnionTags($arrayOfTags)
    {
        if (!is_array($arrayOfTags)) {
            return false;
        }
        
        $query = sprintf("
            SELECT
                d.record_id AS record_id
            FROM
                %sfaqdata_tags d, %sfaqtags t
            WHERE
                t.tagging_id = d.tagging_id
            AND
                (t.tagging_name IN ('%s'))
            GROUP BY
                d.record_id",
            SQLPREFIX,
            SQLPREFIX,
            implode("', '", $arrayOfTags));
        
        $records = array();
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_object($result)) {
            $records[] = $row->record_id;
        }
        
        return $records;
    }
    
}