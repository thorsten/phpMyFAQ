<?php

/**
 * JSON, XML, HTML5 and PDF export
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @copyright 2005-2021 phpMyFAQ Team
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Export\Html5;
use phpMyFAQ\Export\Json;
use phpMyFAQ\Export\Pdf;

/**
 * Class Export
 *
 * @package phpMyFAQ
 */
class Export
{
    /** @var Faq */
    protected $faq = null;

    /** @var Category */
    protected $category = null;

    /** @var Configuration */
    protected $config = null;

    /**
     * Factory.
     *
     * @param Faq           $faq FaqHelper object
     * @param Category      $category Entity object
     * @param Configuration $config Configuration object
     * @param string        $mode Export
     * @return mixed
     * @throws \Exception
     */
    public static function create(Faq $faq, Category $category, Configuration $config, string $mode = 'pdf')
    {
        switch ($mode) {
            case 'json':
                return new Json($faq, $category, $config);
            case 'pdf':
                return new Pdf($faq, $category, $config);
            case 'html5':
                return new Html5($faq, $category, $config);
            default:
                throw new Exception('Export not implemented!');
        }
    }

    /**
     * Returns the timestamp of the export.
     *
     * @return string
     */
    public static function getExportTimestamp(): string
    {
        return date('Y-m-d-H-i-s', $_SERVER['REQUEST_TIME']);
    }
}
