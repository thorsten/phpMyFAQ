<?php

namespace phpMyFAQ\Helper;

/**
 * Helper class for phpMyFAQ tags.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-12-26
 */

use phpMyFAQ\Helper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper_Tags.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-12-26
 */
class TagsHelper extends Helper
{
    /**
     * @var
     */
    private $taggingIds;

    /**
     * Renders the tag list.
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
     * Renders a search tag.
     *
     * @param $tagId
     * @param $tagName
     *
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
                '<li><a class="btn tag" href="?action=search&amp;tagging_id=%s">%s <i aria-hidden="true" class="fa fa-minus-square"></i></a></li> ',
                $taggingIds,
                $tagName
            )
            :
            sprintf(
                '<li><a class="btn tag" href="?action=search&amp;search=">%s <i aria-hidden="true" class="fa fa-minus-square"></i></a></li> ',
                $tagName
            );
    }

    /**
     * Renders the related tag.
     *
     * @param $tagId
     * @param $tagName
     * @param $relevance
     *
     * @return string
     */
    public function renderRelatedTag($tagId, $tagName, $relevance)
    {
        return sprintf(
            '<li><a class="btn tag" href="?action=search&amp;tagging_id=%s">%s %s <span class="badge">%d</span></a></li>',
            implode(',', $this->getTaggingIds()).','.$tagId,
            '<i aria-hidden="true" class="fa fa-plus-square"></i> ',
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
