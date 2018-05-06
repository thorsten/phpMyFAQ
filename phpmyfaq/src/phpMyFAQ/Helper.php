<?php

namespace phpMyFAQ;

/**
 * Main helper class for phpMyFAQ.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */

use phpMyFAQ\Language\Plurals;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Helper.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
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
     * @var integer
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
    protected $_config;

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
        $this->_config = $config;
    }

    /**
     * Loads all translation strings.
     */
    public function getTranslations()
    {
        global $PMF_LANG;

        $this->translation = $PMF_LANG;
    }
}
