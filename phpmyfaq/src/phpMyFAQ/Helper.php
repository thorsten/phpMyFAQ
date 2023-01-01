<?php

/**
 * Main helper class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     */
    protected ?Category $Category = null;

    protected CategoryRelation $categoryRelation;

    /**
     * Tagging class.
     */
    protected ?Tags $Tags = null;

    /**
     * Template class.
     */
    protected ?Template $Template = null;

    /**
     * Plurals class.
     */
    protected ?Plurals $plurals = null;

    /**
     * phpMyFAQ's session ID.
     *
     * @var int|string|null
     */
    protected mixed $sessionId = null;

    protected Configuration $config;

    /**
     * Entity Setter.
     *
     * @param Category $Category Entity object
     */
    public function setCategory(Category $Category): Helper
    {
        $this->Category = $Category;
        return $this;
    }

    public function setCategoryRelation(CategoryRelation $categoryRelation): Helper
    {
        $this->categoryRelation = $categoryRelation;
        return $this;
    }

    /**
     * Tagging Setter.
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
     */
    public function setTemplate(Template $Template): Helper
    {
        $this->Template = $Template;
        return $this;
    }

    /**
     * Plurals setter.
     *
     * @param Plurals $plurals Plurals object
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
     */
    public function setSessionId(int|string $sid): Helper
    {
        $this->sessionId = $sid;
        return $this;
    }

    /**
     * Sets configuration.
     */
    public function setConfiguration(Configuration $config): Helper
    {
        $this->config = $config;
        return $this;
    }
}
