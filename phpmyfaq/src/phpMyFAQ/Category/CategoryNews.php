<?php

namespace phpMyFAQ\Category;

/**
 * The category news class.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-07-20
 */

use phpMyFAQ\Configuration;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * CategoryNews
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-07-20
 */
class CategoryNews
{
    /** @var Configuration */
    private $config = null;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Adds a new news $newsId to the category $categoryId.
     * Returns true on success, otherwise false.
     *
     * @param int $newsId
     * @param int $categoryId
     * @return bool
     */
    public function addNewsToCategory($newsId, $categoryId)
    {
        // @todo implement me
        return false;
    }

    /**
     * Removes a news $newsId to the category $categoryId.
     * Returns true on success, otherwise false.
     *
     * @param int $newsId
     * @param int $categoryId
     * @return bool
     */
    public function removeNewsFromCategory($newsId, $categoryId)
    {
        // @todo implement me
        return false;
    }

    /**
     * Returns an array that contains the news IDs of all news
     * of the category $categoryId.
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryNews($categoryId)
    {
        // @todo implement me
        return [];
    }

    /**
     * Removes the news $newsId from all categories.
     * Returns true on success, otherwise false.
     *
     * @param int $categoryId
     * @return bool
     */
    public function removeNewsFromAllCategories($newsId)
    {
        // @todo implement me
        return false;
    }

}
