<?php
/**
 * Helper class for phpMyFAQ tags
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2013-12-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper_Tags
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2013-12-26
 */
class PMF_Helper_Tags extends PMF_Helper
{
    /**
     * @var
     */
    private $taggingIds;

    /**
     * Renders the tag list
     *
     * @param array $tags
     *
     * @return string
     */
    public function renderTagList(Array $tags)
    {
        $tagList = '';
        foreach ($tags as $tagId => $tagName) {
            $tagList .= $this->renderSearchTag($tagId, $tagName, $tags);
        }
        return $tagList;
    }


    /**
     * Renders a search tag
     *
     * @param $tagId
     * @param $tagName
     * @return string
     */
    public function renderSearchTag($tagId, $tagName)
    {
        $taggingIds = str_replace($tagId, '', $this->getTaggingIds());
        $taggingIds = str_replace(' ', '', $taggingIds);
        $taggingIds = str_replace(',,', ',', $taggingIds);
        $taggingIds = trim(implode(',', $taggingIds), ',');

        return ($taggingIds != '') ?
            sprintf(
                '<a class="btn tag" href="?action=search&amp;tagging_id=%s">%s | X</a> ',
                $taggingIds,
                $tagName
            )
            :
            sprintf(
                '<a class="btn tag" href="?action=search&amp;search=">%s | X</a> ',
                $tagName
            );
    }

    /**
     * Renders the related tag
     *
     * @param $tagId
     * @param $tagName
     * @param $relevance
     * @return string
     */
    function renderRelatedTag($tagId, $tagName, $relevance)
    {

        return sprintf(
            '<a class="btn tag" href="?action=search&amp;tagging_id=%s">%s (%d)</a> ',
            implode(',', $this->getTaggingIds()) . ',' . $tagId,
            $tagName,
            $relevance
        );
    }

    /**
     * @param mixed $taggingIds
     */
    public function setTaggingIds($taggingIds)
    {
        $this->taggingIds = $taggingIds;
    }

    /**
     * @return mixed
     */
    public function getTaggingIds()
    {
        return $this->taggingIds;
    }

}