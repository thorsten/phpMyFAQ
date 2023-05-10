<?php

/**
 * JSON, XML, HTML5 and PDF export
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @copyright 2005-2023 phpMyFAQ Team
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
    protected ?Faq $faq = null;

    protected ?Category $category = null;

    protected ?Configuration $config = null;

    /**
     * Factory.
     *
     * @param Faq           $faq
     * @param Category      $category
     * @return Pdf|Html5|Json
     * @throws Exception
     */
    public static function create(
        Faq $faq,
        Category $category,
        Configuration $config,
        string $mode = 'pdf'
    ): Pdf|Html5|Json {
        return match ($mode) {
            'json' => new Json($faq, $category, $config),
            'pdf' => new Pdf($faq, $category, $config),
            'html5' => new Html5($faq, $category, $config),
            default => throw new Exception('Export not implemented!'),
        };
    }

    /**
     * Returns the timestamp of the export.
     */
    public static function getExportTimestamp(): string
    {
        return date('Y-m-d-H-i-s', $_SERVER['REQUEST_TIME']);
    }
}
