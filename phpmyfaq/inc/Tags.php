<?php
/**
* $Id: Tags.php,v 1.6 2006-08-29 19:39:54 thorstenr Exp $
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
     * @var object PMF_Db
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
     * Returns all tags
     *
     * @return  array   $tags
     * @access  public
     * @since   2006-08-28
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getAllTags()
    {
        $tags = array();

        $query = sprintf("
            SELECT
                tagging_id, tagging_name
            FROM
                %sfaqtags",
            SQLPREFIX);

        $result = $this->db->query($query);
        if ($result) {
            while ($row = $this->db->fetch_object($result)) {
                $tags[$row->tagging_id] = $row->tagging_name;
            }
        }

        return $tags;
    }

    /**
     * Returns all tags for a FAQ record
     *
     * @param   integer $record_id
     * @return  array   $tags
     * @access  public
     * @since   2006-08-10
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getAllTagsById($record_id)
    {
        $tags = array();
        $taglisting = '';

        $query = sprintf("
            SELECT
                dt.tagging_id, t.tagging_name
            FROM
                %sfaqdata_tags dt, %sfaqtags t
            WHERE
                dt.record_id = %d
            AND
                dt.tagging_id = t.tagging_id",
            SQLPREFIX,
            SQLPREFIX,
            $record_id);

        $result = $this->db->query($query);
        if ($result) {
            while ($row = $this->db->fetch_object($result)) {
                $tags[$row->tagging_id] = $row->tagging_name;
            }
        }

        foreach ($tags as $tagging_id => $tagging_name) {
            // @todo: Add Matteos Link class
            $taglisting .= sprintf('<a href="index.php?action=tagcloud&amp;tagging_id=%d">%s</a>, ',
                $tagging_id,
                $tagging_name);
        }

        return substr($taglisting, 0, -2);
    }

    /**
     * Saves all tags from a FAQ record
     *
     * @param   integer $record_id
     * @param   array   $tags
     * @return  boolean
     * @since   2006-08-28
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function saveTags($record_id, $tags)
    {
        if (!is_array($tags)) {
            return false;
        }
        $current_tags = $this->getAllTags();

        foreach ($tags as $tagging_name) {
            if (!in_array($tagging_name, $current_tags)) {

                $tagging_id = $this->db->nextID(SQLPREFIX.'faqtags', 'tagging_id');

                $query = sprintf("
                    INSERT INTO
                        %sfaqdata_tags
                    (record_id, tagging_id)
                        VALUES
                    (%d, %d)",
                    SQLPREFIX,
                    $record_id,
                    $tagging_id);
                $this->db->query($query);

                $query = sprintf("
                    INSERT INTO
                        %sfaqtags
                    (tagging_id, tagging_name)
                        VALUES
                    (%d, '%s')",
                    SQLPREFIX,
                    $tagging_id,
                    $tagging_name);
                $this->db->query($query);
            }
        }

        return true;
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