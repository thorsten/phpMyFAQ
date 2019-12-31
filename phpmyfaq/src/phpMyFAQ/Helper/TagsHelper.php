<?php

/**
 * Helper class for phpMyFAQ tags.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-12-26
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Helper;

/**
 * Class TagsHelper
 *
 * @package phpMyFAQ\Helper
 */
class TagsHelper extends Helper
{
    /**
     * @var array
     */
    private $taggingIds;

    /**
     * Renders the tag list.
     *
     * @param array $tags
     *
     * @return string
     */
    public function renderTagList(array $tags): string
    {
        $tagList = '';
        foreach ($tags as $tagId => $tagName) {
            $tagList .= $this->renderSearchTag($tagId, $tagName);
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
    public function renderSearchTag($tagId, $tagName): string
    {
        $taggingIds = str_replace($tagId, '', $this->getTaggingIds());
        $taggingIds = str_replace(' ', '', $taggingIds);
        $taggingIds = str_replace(',,', ',', $taggingIds);
        $taggingIds = trim(implode(',', $taggingIds), ',');

        return ($taggingIds != '') ?
            sprintf(
                '<a class="btn btn-primary m-1" href="?action=search&amp;tagging_id=%s">%s ' .
                '<i aria-hidden="true" class="fa fa-minus-square"></i></a> ',
                $taggingIds,
                $tagName
            )
            :
            sprintf(
                '<a class="btn btn-primary m-1" href="?action=search&amp;search=">%s ' .
                '<i aria-hidden="true" class="fa fa-minus-square"></i></a> ',
                $tagName
            );
    }

    /**
     * @return mixed
     */
    public function getTaggingIds()
    {
        return $this->taggingIds;
    }

    /**
     * @param mixed $taggingIds
     */
    public function setTaggingIds($taggingIds)
    {
        $this->taggingIds = $taggingIds;
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
    public function renderRelatedTag($tagId, $tagName, $relevance): string
    {
        return sprintf(
            '<a class="btn btn-primary" href="?action=search&amp;tagging_id=%s">%s %s ' .
            '<span class="badge badge-dark">%d</span></a>',
            implode(',', $this->getTaggingIds()) . ',' . $tagId,
            '<i aria-hidden="true" class="fa fa-plus-square"></i> ',
            $tagName,
            $relevance
        );
    }
}
