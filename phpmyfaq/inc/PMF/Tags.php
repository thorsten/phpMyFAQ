<?php
/**
 * The main Tags class
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tags
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Georgi Korchev <korchev@yahoo.com>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-08-10
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Tags
 *
 * @category  phpMyFAQ
 * @package   PMF_Tags
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Georgi Korchev <korchev@yahoo.com>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-08-10
 */
class PMF_Tags
{
    /**
     * @var PMF_Configuration
     */
    private $_config;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Tags
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Returns all tags
     *
     * @param  string  $search Move the returned result set to be the result of a start-with search
     * @param  boolean $limit  Limit the returned result set
     * @return array
     */
    public function getAllTags($search = null, $limit = false, $showInactive = false)
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
                t.tagging_id AS tagging_id, t.tagging_name AS tagging_name
            FROM
                %sfaqtags t
            LEFT JOIN
                %sfaqdata_tags dt
            ON
                dt.tagging_id = t.tagging_id
            LEFT JOIN
                %sfaqdata d
            ON
                d.id = dt.record_id
            WHERE
                1=1
                %s
                %s
            ORDER BY tagging_name",
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            ($showInactive ? '' : "AND d.active = 'yes'"),
            (isset($search) && ($search != '') ? "AND tagging_name ".$like." '".$search."%'" : '')
        );

        $result = $this->_config->getDb()->query($query);
        
        if ($result) {
           while ($row = $this->_config->getDb()->fetchObject($result)) {
              $allTags[$row->tagging_id] = $row->tagging_name;
           }
        }
        
        $numberOfItems = $limit ? PMF_TAGS_CLOUD_RESULT_SET_SIZE : $this->_config->getDb()->numRows($result);
        
        if (isset($allTags) && ($numberOfItems < count($allTags))) {
            $keys = array_keys($allTags);
            shuffle($keys);
            foreach ($keys as $current_key) {
                $tags[$current_key] = $allTags[$current_key];
            }
            $tags = array_slice($tags, 0, $numberOfItems, true);
        } else {
            $tags = PMF_Utils::shuffleData($allTags);
        }
        
        return $tags;
    }

    /**
     * Returns all tags for a FAQ record
     *
     * @param  integer $record_id Record ID
     * @return array
     */
    public function getAllTagsById($record_id)
    {
        $tags = array();

        $query = sprintf("
            SELECT
                dt.tagging_id AS tagging_id, 
                t.tagging_name AS tagging_name
            FROM
                %sfaqdata_tags dt, %sfaqtags t
            WHERE
                dt.record_id = %d
            AND
                dt.tagging_id = t.tagging_id
            ORDER BY
                t.tagging_name",
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            $record_id
        );

        $result = $this->_config->getDb()->query($query);
        if ($result) {
            while ($row = $this->_config->getDb()->fetchObject($result)) {
                $tags[$row->tagging_id] = $row->tagging_name;
            }
        }

        return $tags;
    }

    /**
     * Returns all tags for a FAQ record
     *
     * @param  integer $record_id Record ID
     * @return string
     */
    public function getAllLinkTagsById($record_id)
    {
        global $sids;
        $taglisting = '';

        foreach ($this->getAllTagsById($record_id) as $tagging_id => $tagging_name) {
            $title = PMF_String::htmlspecialchars($tagging_name, ENT_QUOTES, 'utf-8');
            $url = sprintf(
                '%s?action=search&amp;tagging_id=%d',
                PMF_Link::getSystemRelativeUri(),
                $tagging_id
            );
            $oLink            = new PMF_Link($url, $this->_config);
            $oLink->itemTitle = $tagging_name;
            $oLink->text      = $tagging_name;
            $oLink->tooltip   = $title;
            $taglisting      .= $oLink->toHtmlAnchor().', ';
        }

        return '' == $taglisting ? '-' : PMF_String::substr($taglisting, 0, -2);
    }

    /**
     * Saves all tags from a FAQ record
     *
     * @param integer $record_id Record ID
     * @param array   $tags      Array of tags
     */
    public function saveTags($record_id, $tags)
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
            $tagging_name = trim($tagging_name);
            if (PMF_String::strlen($tagging_name) > 0) {
                if (!in_array(PMF_String::strtolower($tagging_name),
                              array_map(array('PMF_String', 'strtolower'), $current_tags))) {
                    // Create the new tag
                    $new_tagging_id = $this->_config->getDb()->nextId(PMF_Db::getTablePrefix().'faqtags', 'tagging_id');
                    $query = sprintf("
                        INSERT INTO
                            %sfaqtags
                        (tagging_id, tagging_name)
                            VALUES
                        (%d, '%s')",
                        PMF_Db::getTablePrefix(),
                        $new_tagging_id,
                        $tagging_name);
                    $this->_config->getDb()->query($query);

                    // Add the tag reference for the faq record
                    $query = sprintf("
                        INSERT INTO
                            %sfaqdata_tags
                        (record_id, tagging_id)
                            VALUES
                        (%d, %d)",
                        PMF_Db::getTablePrefix(),
                        $record_id,
                        $new_tagging_id);
                    $this->_config->getDb()->query($query);
                } else {
                    // Add the tag reference for the faq record
                    $query = sprintf("
                        INSERT INTO
                            %sfaqdata_tags
                        (record_id, tagging_id)
                            VALUES
                        (%d, %d)",
                        PMF_Db::getTablePrefix(),
                        $record_id,
                        array_search(
                            PMF_String::strtolower($tagging_name),
                            array_map(array('String', 'strtolower'), $current_tags)
                        )
                    );
                    $this->_config->getDb()->query($query);
                }
            }
        }

        return true;
    }

    /**
     * Deletes all tags from a given record id
     *
     * @param  integer $record_id Record ID
     * @return boolean
     */
    public function deleteTagsFromRecordId($record_id)
    {
        if (!is_integer($record_id)) {
            return false;
        }

        $query = sprintf("
            DELETE FROM
                %sfaqdata_tags
            WHERE
                record_id = %d",
            PMF_Db::getTablePrefix(),
            $record_id);

        $this->_config->getDb()->query($query);

        return true;

    }

    /**
     * Returns the FAQ record IDs where all tags are included
     *
     * @param  array $arrayOfTags Array of Tags
     * @return array
     */
    public function getRecordsByIntersectionTags($arrayOfTags)
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
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            PMF_String::substr(implode("', '", $arrayOfTags), 0, -2),
            count($arrayOfTags)
        );

        $records = array();
        $result  = $this->_config->getDb()->query($query);
        while ($row = $this->_config->getDb()->fetchObject($result)) {
            $records[] = $row->record_id;
        }

        return $records;
    }

    /**
     * Returns all FAQ record IDs where all tags are included
     *
     * @param  array $arrayOfTags Array of Tags
     * @return array
     */
    public function getRecordsByUnionTags($arrayOfTags)
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
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            PMF_String::substr(implode("', '", $arrayOfTags), 0, -2)
        );

        $records = array();
        $result  = $this->_config->getDb()->query($query);
        while ($row = $this->_config->getDb()->fetchObject($result)) {
            $records[] = $row->record_id;
        }
        return $records;
    }

    /**
     * Returns the tagged item
     *
     * @param  integer $tagId Tagging ID
     * @return string
     */
    public function getTagNameById($tagId)
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
            PMF_Db::getTablePrefix(),
            $tagId
        );

        $result = $this->_config->getDb()->query($query);
        if ($row = $this->_config->getDb()->fetchObject($result)) {
            return $row->tagging_name;
        }
    }

    /**
     * Returns the HTML for the Tags Cloud
     *
     * @return string
     */
    public function printHTMLTagsCloud()
    {
        global $sids;
        
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

        $CSSRelevanceLevels   = 5;
        $CSSRelevanceMinLevel = 1;
        $CSSRelevanceMaxLevel = $CSSRelevanceLevels - $CSSRelevanceMinLevel;
        $CSSRelevanceLevel    = 3;

        $html = '<div id="tagcloud-content">';
        $i    = 0;
        foreach ($tags as $tag) {
            $i++;
            if ($max - $min > 0) {
                $CSSRelevanceLevel =
                    (int)($CSSRelevanceMinLevel + $CSSRelevanceMaxLevel * ($tag['count'] - $min) / ($max - $min));
            }
            $class = 'relevance'.$CSSRelevanceLevel;
            $html .= '<span class="'.$class.'">';
            $title = PMF_String::htmlspecialchars($tag['name'].' ('.$tag['count'].')', ENT_QUOTES, 'utf-8');
            $url = sprintf(
                '%s?action=search&amp;tagging_id=%d',
                PMF_Link::getSystemRelativeUri(),
                $tag['id']
            );
            $oLink            = new PMF_Link($url, $this->_config);
            $oLink->itemTitle = $tag['name'];
            $oLink->text      = $tag['name'];
            $oLink->tooltip   = $title;
            $html            .= $oLink->toHtmlAnchor();
            $html            .= (count($tags) == $i ? '' : ' ').'</span>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Returns all FAQ record IDs where all tags are included
     *
     * @param  string $tagName The name of the tag
     * @return array
     */
    public function getRecordsByTagName($tagName)
    {
        if (!is_string($tagName)) {
            return false;
        }

        $query = sprintf("
            SELECT
                dt.record_id AS record_id
            FROM
                %sfaqtags t, %sfaqdata_tags dt
            LEFT JOIN
                %sfaqdata d
            ON
                d.id = dt.record_id
            WHERE
                t.tagging_id = dt.tagging_id
            AND 
                t.tagging_name = '%s'",
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            $this->_config->getDb()->escape($tagName));

        $records = array();
        $result = $this->_config->getDb()->query($query);
        while ($row = $this->_config->getDb()->fetchObject($result)) {
            $records[] = $row->record_id;
        }

        return $records;
    }

    /**
     * Returns all FAQ record IDs where all tags are included
     *
     * @param  integer $tagId Tagging ID
     * @return array
     */
    public function getRecordsByTagId($tagId)
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
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            $tagId);

        $records = array();
        $result  = $this->_config->getDb()->query($query);
        while ($row = $this->_config->getDb()->fetchObject($result)) {
            $records[] = $row->record_id;
        }

        return $records;
    }

    /**
     * Check if at least one faq has been tagged with a tag
     *
     * @return boolean
     */
    public function existTagRelations()
    {
        $query = sprintf('
            SELECT
                COUNT(record_id) AS n
            FROM
                %sfaqdata_tags',
            PMF_Db::getTablePrefix()
        );

        $result = $this->_config->getDb()->query($query);
        if ($row = $this->_config->getDb()->fetchObject($result)) {
            return ($row->n > 0);
        }

        return false;
    }
}
