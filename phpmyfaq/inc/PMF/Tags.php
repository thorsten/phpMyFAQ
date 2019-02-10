<?php

/**
 * The main Tags class.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Georgi Korchev <korchev@yahoo.com>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-08-10
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Tags.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Georgi Korchev <korchev@yahoo.com>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-08-10
 */
class PMF_Tags
{
    /**
     * @var PMF_Configuration
     */
    private $_config;

    /**
     * @var array
     */
    private $recordsByTagName = [];

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Returns all tags.
     *
     * @param string $search       Move the returned result set to be the result of a start-with search
     * @param int    $limit        Limit the returned result set
     * @param bool   $showInactive Show inactive tags
     *
     * @return array
     */
    public function getAllTags($search = null, $limit = PMF_TAGS_CLOUD_RESULT_SET_SIZE, $showInactive = false)
    {
        $allTags = [];

        // Hack: LIKE is case sensitive under PostgreSQL
        switch (PMF_Db::getType()) {
            case 'pgsql':
                $like = 'ILIKE';
                break;
            default:
                $like = 'LIKE';
                break;
        }

        $query = sprintf('
            SELECT
                MIN(t.tagging_id) AS tagging_id, t.tagging_name AS tagging_name
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
            GROUP BY
                tagging_name
            ORDER BY
                tagging_name ASC',
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            ($showInactive ? '' : "AND d.active = 'yes'"),
            (isset($search) && ($search != '') ? 'AND tagging_name '.$like." '".$search."%'" : '')
        );

        $result = $this->_config->getDb()->query($query);

        if ($result) {
            $i = 0;
            while ($row = $this->_config->getDb()->fetchObject($result)) {
                if ($i < $limit) {
                    $allTags[$row->tagging_id] = $row->tagging_name;
                } else {
                    break;
                }
                ++$i;
            }
        }

        return array_unique($allTags);
    }

    /**
     * Returns all tags for a FAQ record.
     *
     * @param int $recordId Record ID
     *
     * @return array
     */
    public function getAllTagsById($recordId)
    {
        $tags = [];

        $query = sprintf('
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
                t.tagging_name',
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            $recordId
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
     * Returns all tags for a FAQ record.
     *
     * @param int $recordId Record ID
     *
     * @return string
     */
    public function getAllLinkTagsById($recordId)
    {
        $tagListing = '';

        foreach ($this->getAllTagsById($recordId) as $taggingId => $taggingName) {
            $title = PMF_String::htmlspecialchars($taggingName, ENT_QUOTES, 'utf-8');
            $url = sprintf(
                '%s?action=search&amp;tagging_id=%d',
                PMF_Link::getSystemRelativeUri(),
                $taggingId
            );
            $oLink = new PMF_Link($url, $this->_config);
            $oLink->itemTitle = $taggingName;
            $oLink->text = $taggingName;
            $oLink->tooltip = $title;
            $tagListing      .= $oLink->toHtmlAnchor().', ';
        }

        return '' == $tagListing ? '-' : PMF_String::substr($tagListing, 0, -2);
    }

    /**
     * Saves all tags from a FAQ record.
     *
     * @param int   $recordId Record ID
     * @param array $tags     Array of tags
     *
     * @return bool
     */
    public function saveTags($recordId, Array $tags)
    {
        $currentTags = $this->getAllTags();

        // Delete all tag references for the faq record
        if (count($tags) > 0) {
            $this->deleteTagsFromRecordId($recordId);
        }

        // Store tags and references for the faq record
        foreach ($tags as $tagName) {
            $tagName = trim($tagName);
            if (PMF_String::strlen($tagName) > 0) {
                if (!in_array(PMF_String::strtolower($tagName),
                              array_map(array('PMF_String', 'strtolower'), $currentTags))) {
                    // Create the new tag
                    $newTagId = $this->_config->getDb()->nextId(PMF_Db::getTablePrefix().'faqtags', 'tagging_id');
                    $query = sprintf("
                        INSERT INTO
                            %sfaqtags
                        (tagging_id, tagging_name)
                            VALUES
                        (%d, '%s')",
                        PMF_Db::getTablePrefix(),
                        $newTagId,
                        $tagName
                    );
                    $this->_config->getDb()->query($query);

                    // Add the tag reference for the faq record
                    $query = sprintf('
                        INSERT INTO
                            %sfaqdata_tags
                        (record_id, tagging_id)
                            VALUES
                        (%d, %d)',
                        PMF_Db::getTablePrefix(),
                        $recordId,
                        $newTagId
                    );
                    $this->_config->getDb()->query($query);
                } else {
                    // Add the tag reference for the faq record
                    $query = sprintf('
                        INSERT INTO
                            %sfaqdata_tags
                        (record_id, tagging_id)
                            VALUES
                        (%d, %d)',
                        PMF_Db::getTablePrefix(),
                        $recordId,
                        array_search(
                            PMF_String::strtolower($tagName),
                            array_map(array('PMF_String', 'strtolower'), $currentTags)
                        )
                    );
                    $this->_config->getDb()->query($query);
                }
            }
        }

        return true;
    }

    /**
     * Updates a tag.
     *
     * @param PMF_Entity_Tags $entity
     *
     * @return bool
     */
    public function updateTag(PMF_Entity_Tags $entity)
    {
        $query = sprintf("
            UPDATE
                %sfaqtags
            SET
                tagging_name = '%s'
            WHERE
                tagging_id = %d",
            PMF_Db::getTablePrefix(),
            $entity->getName(),
            $entity->getId()
        );

        return $this->_config->getDb()->query($query);
    }

    /**
     * Deletes all tags from a given record id.
     *
     * @param int $recordId Record ID
     *
     * @return bool
     */
    public function deleteTagsFromRecordId($recordId)
    {
        if (!is_integer($recordId)) {
            return false;
        }

        $query = sprintf('
            DELETE FROM
                %sfaqdata_tags
            WHERE
                record_id = %d',
            PMF_Db::getTablePrefix(),
            $recordId
        );

        $this->_config->getDb()->query($query);

        return true;
    }

    /**
     * Deletes a given tag.
     *
     * @param int $tagId
     *
     * @return bool
     */
    public function deleteTag($tagId)
    {
        if (!is_integer($tagId)) {
            return false;
        }

        try {
            $query = sprintf('
                DELETE FROM
                    %sfaqtags
                WHERE
                    tagging_id = %d',
                PMF_Db::getTablePrefix(),
                $tagId
            );

            $this->_config->getDb()->query($query);
        } catch (PMF_Exception $e) {
            // @todo Handle exception!
        }

        try {
            $query = sprintf('
                DELETE FROM
                    %sfaqdata_tags
                WHERE
                    tagging_id = %d',
                PMF_Db::getTablePrefix(),
                $tagId
            );

            $this->_config->getDb()->query($query);
        } catch (PMF_Exception $e) {
            // @todo Handle exception!
        }

        return true;
    }

    /**
     * Returns the FAQ record IDs where all tags are included.
     *
     * @param array $arrayOfTags Array of Tags
     *
     * @return array
     */
    public function getRecordsByIntersectionTags(Array $arrayOfTags)
    {
        if (!is_array($arrayOfTags)) {
            return false;
        }

        $query = sprintf("
            SELECT
                td.record_id AS record_id
            FROM
                %sfaqdata_tags td
            JOIN
                %sfaqtags t ON (td.tagging_id = t.tagging_id)
            JOIN
                %sfaqdata d ON (td.record_id = d.id)
            WHERE
                (t.tagging_name IN ('%s'))
            AND
                (d.lang = '%s')
            GROUP BY
                td.record_id
            HAVING
                COUNT(td.record_id) = %d",
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            implode("', '", $arrayOfTags),
            $this->_config->getLanguage()->getLanguage(),
            count($arrayOfTags)
        );

        $records = [];
        $result = $this->_config->getDb()->query($query);
        while ($row = $this->_config->getDb()->fetchObject($result)) {
            $records[] = $row->record_id;
        }

        return $records;
    }

    /**
     * Returns all FAQ record IDs where all tags are included.
     *
     * @param array $arrayOfTags Array of Tags
     *
     * @return array
     */
    public function getRecordsByUnionTags(Array $arrayOfTags)
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

        $records = [];
        $result = $this->_config->getDb()->query($query);
        while ($row = $this->_config->getDb()->fetchObject($result)) {
            $records[] = $row->record_id;
        }

        return $records;
    }

    /**
     * Returns the tagged item.
     *
     * @param int $tagId Tagging ID
     *
     * @return string
     */
    public function getTagNameById($tagId)
    {
        if (!is_numeric($tagId)) {
            return;
        }

        $query = sprintf('
            SELECT
                tagging_name
            FROM
                %sfaqtags
            WHERE
                tagging_id = %d',
            PMF_Db::getTablePrefix(),
            $tagId
        );

        $result = $this->_config->getDb()->query($query);
        if ($row = $this->_config->getDb()->fetchObject($result)) {
            return $row->tagging_name;
        }
    }

    /**
     * Returns the HTML for the Tags Cloud.
     *
     * @return string
     */
    public function printHTMLTagsCloud()
    {
        $tags = [];

        // Limit the result set (see: PMF_TAGS_CLOUD_RESULT_SET_SIZE)
        // for avoiding an 'heavy' load during the evaluation
        // of the number of records for each tag
        $tagList = $this->getAllTags('', PMF_TAGS_CLOUD_RESULT_SET_SIZE);

        foreach ($tagList as $tagId => $tagName) {
            $totFaqByTag = count($this->getRecordsByTagName($tagName));
            if ($totFaqByTag > 0) {
                $tags[$tagName]['id'] = $tagId;
                $tags[$tagName]['name'] = $tagName;
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

        $html = '';
        $i = 0;
        foreach ($tags as $tag) {
            ++$i;
            $html .= '<li>';
            $title = PMF_String::htmlspecialchars($tag['name'].' ('.$tag['count'].')', ENT_QUOTES, 'utf-8');
            $url = sprintf(
                '%s?action=search&amp;tagging_id=%d',
                PMF_Link::getSystemRelativeUri(),
                $tag['id']
            );
            $oLink = new PMF_Link($url, $this->_config);
            $oLink->itemTitle = $tag['name'];
            $oLink->text = $tag['name'];
            $oLink->tooltip = $title;
            $html .= $oLink->toHtmlAnchor();
            $html .= (count($tags) == $i ? '' : ' ').'</li>';
        }

        return $html;
    }

    /**
     * Returns all FAQ record IDs where all tags are included.
     *
     * @param string $tagName The name of the tag
     *
     * @return array
     */
    public function getRecordsByTagName($tagName)
    {
        if (!is_string($tagName)) {
            return [];
        }

        if (count($this->recordsByTagName)) {
            return $this->recordsByTagName;
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

        $this->recordsByTagName = [];
        $result = $this->_config->getDb()->query($query);
        while ($row = $this->_config->getDb()->fetchObject($result)) {
            $this->recordsByTagName[] = $row->record_id;
        }

        return $this->recordsByTagName;
    }

    /**
     * Returns all FAQ record IDs where all tags are included.
     *
     * @param int $tagId Tagging ID
     *
     * @return array
     */
    public function getRecordsByTagId($tagId)
    {
        if (!is_integer($tagId)) {
            return [];
        }

        $query = sprintf('
            SELECT
                d.record_id AS record_id
            FROM
                %sfaqdata_tags d, %sfaqtags t
            WHERE
                t.tagging_id = d.tagging_id
            AND
                t.tagging_id = %d
            GROUP BY
                record_id',
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            $tagId);

        $records = [];
        $result = $this->_config->getDb()->query($query);
        while ($row = $this->_config->getDb()->fetchObject($result)) {
            $records[] = $row->record_id;
        }

        return $records;
    }

    /**
     * Check if at least one faq has been tagged with a tag.
     *
     * @return bool
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

    /**
     * @param int $limit Specify the maximum amount of records to return
     *
     * @return array $tagId => $tagFrequency
     */
    public function getPopularTags($limit = 0)
    {
        $tags = [];

        $query = sprintf("
            SELECT
                COUNT(record_id) as freq, tagging_id
            FROM
                %sfaqdata_tags
            JOIN
                %sfaqdata ON id = record_id
            WHERE
              lang = '%s'
            GROUP BY tagging_id
            ORDER BY freq DESC",
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            $this->_config->getLanguage()->getLanguage()
        );

        $result = $this->_config->getDb()->query($query);
        if ($result) {
            while ($row = $this->_config->getDb()->fetchObject($result)) {
                $tags[$row->tagging_id] = $row->freq;
                if (--$limit === 0) {
                    break;
                }
            }
        }

        return $tags;
    }

    /**
     * @param integer $limit
     *
     * @return string
     */
    public function renderPopularTags($limit = 0)
    {
        $html = '';
        foreach ($this->getPopularTags($limit) as $tagId => $tagFreq) {
            $tagName = $this->getTagNameById($tagId);
            $direction = $this->isEnglish($tagName[0]) ? 'ltr' : 'rtl';
            $html .= sprintf(
                '<li><a style="direction:%s;" href="?action=search&tagging_id=%d">%s <span class="badge">%d</span></a></li>',
                $direction,
                $tagId,
                $tagName,
                $tagFreq
            );
        }

        return $html;
    }

    /**
     * Returns the popular Tags as an array
     *
     * @param integer $limit
     *
     * @return array
     */
    public function getPopularTagsAsArray($limit = 0)
    {
        $data = [];
        foreach ($this->getPopularTags($limit) as $tagId => $tagFreq) {
            $tagName = $this->getTagNameById($tagId);
            $data[] = [
                'tagId' => $tagId,
                'tagName' => $tagName,
                'tagFrequency' => (int)$tagFreq
            ];
        }

        return $data;
    }

    /**
     * @param $chr
     *
     * @return bool
     */
    public function isEnglish($chr)
    {
        if (($chr >= 'A') && ($chr <= 'Z')) {
            return true;
        }
        if (($chr >= 'a') && ($chr <= 'z')) {
            return true;
        }

        return false;
    }
}
