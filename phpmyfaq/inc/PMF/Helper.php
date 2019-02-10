<?php

/**
 * Main helper class for phpMyFAQ.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Helper.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */
abstract class PMF_Helper
{
    /**
     * Category class.
     *
     * @var PMF_Category
     */
    protected $Category = null;

    /**
     * Tagging class.
     *
     * @var PMF_Tags
     */
    protected $Tags = null;

    /**
     * Template class.
     *
     * @var PMF_Template
     */
    protected $Template = null;

    /**
     * Plurals class.
     *
     * @var PMF_Language_Plurals
     */
    protected $plurals = null;

    /**
     * phpMyFAQ's session ID.
     *
     * @var onteger
     */
    protected $sessionId = null;

    /**
     * Array with all translations.
     *
     * @var array
     */
    protected $translation = [];

    /**
     * @var PMF_Configuration
     */
    protected $_config;

    /**
     * Category Setter.
     *
     * @param PMF_Category $Category Category object
     */
    public function setCategory(PMF_Category $Category)
    {
        $this->Category = $Category;
    }

    /**
     * Tagging Setter.
     *
     * @param PMF_Tags $Tags PMF_Tags object
     */
    public function setTags(PMF_Tags $Tags)
    {
        $this->Tags = $Tags;
    }

    /**
     * Template Setter.
     *
     * @param PMF_Template $Template PMF_Template object
     *
     * @var PMF_Template
     */
    public function setTemplate(PMF_Template $Template)
    {
        $this->Template = $Template;
    }

    /**
     * Plurals setter.
     *
     * @param PMF_Language_Plurals $plurals PMF_Language_Plurals object
     */
    public function setPlurals(PMF_Language_Plurals $plurals)
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
     * @param PMF_Configuration $config
     */
    public function setConfiguration(PMF_Configuration $config)
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
