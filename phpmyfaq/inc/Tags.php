<?php
/**
* $Id: Tags.php,v 1.37.2.1 2007-07-13 15:11:32 thorstenr Exp $
*
* The main Tags class
*
* @package      phpMyFAQ
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @since        2006-08-10
* @copyright    (c) 2006-2007 phpMyFAQ Team
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
     * @param   string  $search     Move the returned result set to be the result of a start-with search
     * @param   boolean $limit      Limit the returned result set
     * @return  array   $tags
     * @access  public
     * @since   2006-08-28
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @author  Georgi Korchev <korchev@yahoo.com>
     */
    function getAllTags($search = null, $limit = false)
    {
        global $DB;
        $tags = $allTags = array();

        // Hack: LIKE is case sensitive under PostgreSQL
        switch ($DB['type']) {
            case 'pgsql':
                $like = 'ILIKE';
                break;
            default:
                $like = 'LIKE';
                break;
        }

        $query = sprintf("
            SELECT
                tagging_id, tagging_name
            FROM
                %sfaqtags
                %s
            ORDER BY tagging_name",
            SQLPREFIX,
            (isset($search) && ($search != '') ? "WHERE tagging_name ".$like." '".$search."%'" : '')
            );

        $i = 0;
        $result = $this->db->query($query);

        if ($result) {
           while ($row = $this->db->fetch_object($result)) {
              $allTags[$row->tagging_id] = $row->tagging_name;
           }
        }

        $numberOfItems = $limit ? PMF_TAGS_CLOUD_RESULT_SET_SIZE : $this->db->num_rows($result);

        if (isset($allTags) && ($numberOfItems < count($allTags))) {
           for ($n = 0; $n < $numberOfItems; $n++) {
              $valid = false;
              while (!$valid) {
                 $rand = rand(1, count($allTags) + 1);
                  if (!isset($soFar[$rand])) {
                     if (isset($allTags[$rand])) {
                        $valid = true;
                        $soFar[$rand] = '';
                        $tags[$rand] = $allTags[$rand];
                     }

                  }
              }
           }
        } else {
           $tags = PMF_Utils::shuffleData($allTags);
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
            $record_id
        );

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
     * @return  string
     * @access  public
     * @since   2006-08-29
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getAllLinkTagsById($record_id)
    {
        global $sids, $PMF_LANG;
        $taglisting = '';

        foreach ($this->getAllTagsById($record_id) as $tagging_id => $tagging_name) {
            $title = PMF_htmlentities($tagging_name, ENT_QUOTES, $PMF_LANG['metaCharset']);
            $url = sprintf(
                $sids.'action=search&amp;tagging_id=%d',
                $tagging_id
            );
            $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
            $oLink->itemTitle = $tagging_name;
            $oLink->text = $tagging_name;
            $oLink->tooltip = $title;
            $taglisting .= $oLink->toHtmlAnchor().', ';
        }

        return '' == $taglisting ? '-' : substr($taglisting, 0, -2);
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

        // Delete all tag references for the faq record
        if (count($tags) > 0) {
            $this->deleteTagsFromRecordId($record_id);
        }

        // Store tags and references for the faq record
        foreach ($tags as $tagging_name) {
            if (strlen($tagging_name) > 0) {
                if (!in_array($tagging_name, $current_tags)) {
                    // Create the new tag
                    $new_tagging_id = $this->db->nextID(SQLPREFIX.'faqtags', 'tagging_id');
                    $query = sprintf("
                        INSERT INTO
                            %sfaqtags
                        (tagging_id, tagging_name)
                            VALUES
                        (%d, '%s')",
                        SQLPREFIX,
                        $new_tagging_id,
                        $tagging_name);
                    $this->db->query($query);

                    // Add the tag reference for the faq record
                    $query = sprintf("
                        INSERT INTO
                            %sfaqdata_tags
                        (record_id, tagging_id)
                            VALUES
                        (%d, %d)",
                        SQLPREFIX,
                        $record_id,
                        $new_tagging_id);
                    $this->db->query($query);
                } else {
                    // Add the tag reference for the faq record
                    $query = sprintf("
                        INSERT INTO
                            %sfaqdata_tags
                        (record_id, tagging_id)
                            VALUES
                        (%d, %d)",
                        SQLPREFIX,
                        $record_id,
                        array_search($tagging_name, $current_tags));
                    $this->db->query($query);
                }
            }
        }

        return true;
    }

    /**
     * Deletes all tags from a given record id
     *
     * @param   integer $record_id
     * @return  boolean
     * @access  public
     * @since   2007-05-02
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function deleteTagsFromRecordId($record_id)
    {
        if (!is_integer($record_id)) {
            return false;
        }

        $query = sprintf("
            DELETE FROM
                %sfaqdata_tags
            WHERE
                record_id = %d",
            SQLPREFIX,
            $record_id);

        $this->db->query($query);

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
            substr(implode("', '", $arrayOfTags), 0, -2),
            count($arrayOfTags)
        );

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
            substr(implode("', '", $arrayOfTags), 0, -2)
        );

        $records = array();
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_object($result)) {
            $records[] = $row->record_id;
        }
        return $records;
    }

    /**
     * Returns the tagged item
     *
     * @param   integer
     * @return  string
     * @access  public
     * @since   2006-11-19
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getTagNameById($tagId)
    {
        if (!is_numeric($tagId)) {
            return null;
        }

        $query = sprintf("
            SELECT
                tagging_name
            FROM
                %sfaqtags
            WHERE
                tagging_id = %d",
            SQLPREFIX,
            $tagId
        );

        $result = $this->db->query($query);
        if ($row = $this->db->fetch_object($result)) {
            return $row->tagging_name;
        }
    }

    /**
     * Returns the HTML for the Tags Cloud
     *
     * @return  string
     * @access  public
     * @since   2006-09-02
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function printHTMLTagsCloud()
    {
        global $sids, $PMF_LANG;
        $html = '';
        $tags = array();

        // Limit the result set (see: PMF_TAGS_CLOUD_RESULT_SET_SIZE)
        // for avoiding an 'heavy' load during the evaluation
        // of the number of records for each tag
        $tagList = $this->getAllTags('', true);
        foreach ($tagList as $tagId => $tagName) {
            $totFaqByTag = count($this->getRecordsByTagName($tagName));
            if ($totFaqByTag > 0) {
                $tags[$tagName]['id']    = $tagId;
                $tags[$tagName]['name']  = $tagName;
                $tags[$tagName]['count'] = $totFaqByTag;
            }
        }
        $min = 0;
        $max = 0;
        foreach ($tags as $tag) {
            if ($min > $tag['count']) {
                $min = $tag['count'];
            }
            if ($max < $tag['count']) {
                $max = $tag['count'];
            }
        }

        $CSSRelevanceLevels = 5;
        $CSSRelevanceMinLevel = 1;
        $CSSRelevanceMaxLevel = $CSSRelevanceLevels - $CSSRelevanceMinLevel;
        $CSSRelevanceLevel = 3;
        $html = '<div class="tagscloud">';
        $i = 0;
        foreach ($tags as $tag) {
            $i++;
            if ($max - $min > 0) {
                $CSSRelevanceLevel = (int)($CSSRelevanceMinLevel + $CSSRelevanceMaxLevel*($tag['count'] - $min)/($max - $min));
            }
            $class = 'relevance'.$CSSRelevanceLevel;
            $html .= '<span class="'.$class.'">';
            $title = PMF_htmlentities($tag['name'].' ('.$tag['count'].')', ENT_QUOTES, $PMF_LANG['metaCharset']);
            $url = sprintf(
                        $sids.'action=search&amp;tagging_id=%d',
                        $tag['id']
                        );
            $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
            $oLink->itemTitle = $tag['name'];
            $oLink->text = $tag['name'];
            $oLink->tooltip = $title;
            $html .= $oLink->toHtmlAnchor();
            $html .= (count($tags) == $i ? '' : ' ').'</span>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Returns all FAQ record IDs where all tags are included
     *
     * @param   array   $arrayOfTags
     * @return  array   $records
     * @access  public
     * @since   2006-08-30
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function getRecordsByTagName($tagName)
    {
        if (!is_string($tagName)) {
            return false;
        }

        $query = sprintf("
            SELECT
                d.record_id AS record_id
            FROM
                %sfaqdata_tags d, %sfaqtags t
            WHERE
                    t.tagging_id = d.tagging_id
                AND t.tagging_name = '%s'",
            SQLPREFIX,
            SQLPREFIX,
            $tagName
        );

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
     * @param   integer $tagId
     * @return  array   $records
     * @access  public
     * @since   2007-04-20
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getRecordsByTagId($tagId)
    {
        if (!is_integer($tagId)) {
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
                t.tagging_id = %d
            GROUP BY
                record_id",
            SQLPREFIX,
            SQLPREFIX,
            $tagId);

        $records = array();
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_object($result)) {
            $records[] = $row->record_id;
        }

        return $records;
    }

    /**
     * Check if at least one faq has been tagged with a tag
     *
     * @return  bool
     * @access  public
     * @since   2006-12-31
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function existTagRelations()
    {
        $query = sprintf('
            SELECT
                COUNT(record_id) AS n
            FROM
                %sfaqdata_tags',
            SQLPREFIX
        );

        $result = $this->db->query($query);
        if ($row = $this->db->fetch_object($result)) {
            return ($row->n > 0);
        }

        return false;
    }
}
