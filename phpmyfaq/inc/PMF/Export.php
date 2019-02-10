<?php

/**
 * XML, XHTML and PDF export - Classes and Functions.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @copyright 2005-2019 phpMyFAQ Team
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

require_once PMF_CONFIG_DIR.'/constants.php';

/**
 * Export Class.
 *
 * This class manages the export formats supported by phpMyFAQ:
 * - JSON
 * - PDF
 * - XHTML
 * - XML
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @copyright 2005-2019 phpMyFAQ Team
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */
class PMF_Export
{
    /**
     * Faq object.
     *
     * @var PMF_Faq
     */
    protected $faq = null;

    /**
     * Category object.
     *
     * @var PMF_Category
     */
    protected $category = null;

    /**
     * Configuration.
     *
     * @var PMF_Configuration
     */
    protected $_config = null;

    /**
     * Factory.
     *
     * @param PMF_Faq           $faq      Faq object
     * @param PMF_Category      $category Category object
     * @param PMF_Configuration $config   Configuration object
     * @param string            $mode     Export
     *
     * @return mixed
     *
     * @throws PMF_Exception
     */
    public static function create(PMF_Faq $faq, PMF_Category $category, PMF_Configuration $config, $mode = 'pdf')
    {
        switch ($mode) {
            case 'json':
                return new PMF_Export_Json($faq, $category, $config);
                break;
            case 'pdf':
                return new PMF_Export_Pdf($faq, $category, $config);
                break;
            case 'xml':
                return new PMF_Export_Xml($faq, $category, $config);
                break;
            case 'xhtml':
                return new PMF_Export_Xhtml($faq, $category, $config);
                break;
            default:
                throw new PMF_Exception('Export not implemented!');
        }
    }

    /**
     * Returns the timestamp of the export.
     *
     * @return string
     */
    public static function getExportTimeStamp()
    {
        return date('Y-m-d-H-i-s', $_SERVER['REQUEST_TIME']);
    }
}
