<?php

/**
 * Helper class for phpMyFAQ tags.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package    phpMyFAQ
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright  2013-2022 phpMyFAQ Team
 * @license    http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link       https://www.phpmyfaq.de
 * @since      2013-12-26
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Filter;
use phpMyFAQ\Helper;
use phpMyFAQ\Strings;

/**
 * Class TagsHelper
 * @package phpMyFAQ\Helper
 */
class TagsHelper extends Helper
{
    /**
     * The array of Tag IDs
     *
     * @var array
     */
    private array $taggingIds;

    /**
     * Renders the tag list.
     *
     * @param array $tags Array of tags.
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
     * @param int    $tagId   The ID of the tag
     * @param string $tagName The tag name
     *
     * @return string
     */
    public function renderSearchTag(int $tagId, string $tagName): string
    {
        $taggingIds = str_replace((string) $tagId, '', $this->getTaggingIds());
        $taggingIds = str_replace(' ', '', $taggingIds);
        $taggingIds = str_replace(',,', ',', $taggingIds);
        $taggingIds = trim(implode(',', $taggingIds), ',');

        return ($taggingIds != '') ? sprintf(
            '<a class="btn btn-primary m-1" href="?action=search&amp;tagging_id=%s">%s ' .
            '<i aria-hidden="true" class="fa fa-minus-square"></i></a> ',
            $taggingIds,
            Strings::htmlentities($tagName)
        ) : sprintf(
            '<a class="btn btn-primary m-1" href="?action=search&amp;search=">%s ' .
            '<i aria-hidden="true" class="fa fa-minus-square"></i></a> ',
            Strings::htmlentities($tagName)
        );
    }

    /**
     * Returns all tag IDs as array.
     *
     * @return array
     */
    public function getTaggingIds(): array
    {
        return $this->taggingIds;
    }

    /**
     * Sets the tag IDs.
     *
     * @param array $taggingIds The tag IDs as array
     */
    public function setTaggingIds(array $taggingIds): void
    {
        $this->taggingIds = array_filter($taggingIds, function ($tagId) {
            return Filter::filterVar($tagId, FILTER_VALIDATE_INT);
        });
    }


    /**
     * Renders the related tag.
     *
     * @param int     $tagId     The given Tag ID.
     * @param string  $tagName   The name of the tag.
     * @param int     $relevance The relevance of the tag.
     *
     * @return string
     */
    public function renderRelatedTag(int $tagId, string $tagName, int $relevance): string
    {
        return sprintf(
            '<a class="btn btn-primary" href="?action=search&amp;tagging_id=%s">%s %s ' .
            '<span class="badge badge-dark">%d</span></a>',
            implode(',', $this->getTaggingIds()) . ',' . $tagId,
            '<i aria-hidden="true" class="fa fa-plus-square"></i> ',
            Strings::htmlentities($tagName),
            $relevance
        );
    }
}
