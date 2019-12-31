<?php

/**
 * Main helper class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */

namespace phpMyFAQ;

use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Language\Plurals;

/**
 * Class Helper
 *
 * @package phpMyFAQ
 */
abstract class Helper
{
    /**
     * Entity class.
     *
     * @var Category
     */
    protected $Category = null;

    /**
     * @var CategoryRelation
     */
    protected $categoryRelation;

    /**
     * Tagging class.
     *
     * @var Tags
     */
    protected $Tags = null;

    /**
     * Template class.
     *
     * @var Template
     */
    protected $Template = null;

    /**
     * Plurals class.
     *
     * @var Plurals
     */
    protected $plurals = null;

    /**
     * phpMyFAQ's session ID.
     *
     * @var int
     */
    protected $sessionId = null;

    /**
     * Array with all translations.
     *
     * @var array
     */
    protected $translation = [];

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * Entity Setter.
     *
     * @param Category $Category Entity object
     */
    public function setCategory(Category $Category)
    {
        $this->Category = $Category;
    }

    /**
     * @param  CategoryRelation $categoryRelation
     * @return Helper
     */
    public function setCategoryRelation(CategoryRelation $categoryRelation): Helper
    {
        $this->categoryRelation = $categoryRelation;
        return $this;
    }

    /**
     * Tagging Setter.
     *
     * @param Tags $Tags PMF_Tags object
     */
    public function setTags(Tags $Tags)
    {
        $this->Tags = $Tags;
    }

    /**
     * Template Setter.
     *
     * @param Template $Template Template object
     *
     * @var Template
     */
    public function setTemplate(Template $Template)
    {
        $this->Template = $Template;
    }

    /**
     * Plurals setter.
     *
     * @param Plurals $plurals Language_Plurals object
     */
    public function setPlurals(Plurals $plurals)
    {
        $this->plurals = $plurals;
    }

    /**
     * Session ID setter.
     *
     * @param int $sid Session id
     */
    public function setSessionId($sid)
    {
        $this->sessionId = $sid;
    }

    /**
     * Sets configuration.
     *
     * @param Configuration $config
     */
    public function setConfiguration(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Loads all translation strings.
     *
     * @return array
     */
    public function getTranslations(): array
    {
        global $PMF_LANG;

        return $this->translation = $PMF_LANG;
    }
}
