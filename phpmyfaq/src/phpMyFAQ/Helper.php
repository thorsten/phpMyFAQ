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
 * @copyright 2009-2021 phpMyFAQ Team
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
     * @var string[]
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
     * @return Helper
     */
    public function setCategory(Category $Category): Helper
    {
        $this->Category = $Category;
        return $this;
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
     * @param Tags $Tags
     * @return Helper
     */
    public function setTags(Tags $Tags): Helper
    {
        $this->Tags = $Tags;
        return $this;
    }

    /**
     * Template Setter.
     *
     * @param Template $Template Template object
     * @return Helper
     */
    public function setTemplate(Template $Template): Helper
    {
        $this->Template = $Template;
        return $this;
    }

    /**
     * Plurals setter.
     *
     * @param Plurals $plurals Language_Plurals object
     * @return Helper
     */
    public function setPlurals(Plurals $plurals): Helper
    {
        $this->plurals = $plurals;
        return $this;
    }

    /**
     * Session ID setter.
     *
     * @param int|string $sid Session id
     * @return Helper
     */
    public function setSessionId($sid): Helper
    {
        $this->sessionId = $sid;
        return $this;
    }

    /**
     * Sets configuration.
     *
     * @param Configuration $config
     * @return Helper
     */
    public function setConfiguration(Configuration $config): Helper
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Loads all translation strings.
     *
     * @return string[]
     */
    public function getTranslations(): array
    {
        global $PMF_LANG;

        return $this->translation = $PMF_LANG;
    }
}
