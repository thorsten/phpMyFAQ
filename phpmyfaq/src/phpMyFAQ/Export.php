<?php

declare(strict_types=1);

/**
 * JSON, and PDF export
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @copyright 2005-2025 phpMyFAQ Team
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-02
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Export\Json;
use phpMyFAQ\Export\Pdf;
use Symfony\Component\HttpFoundation\Request;

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
     * @throws Exception
     */
    public static function create(
        Faq $faq,
        Category $category,
        Configuration $configuration,
        string $mode = 'pdf',
    ): Pdf|Json {
        return match ($mode) {
            'json' => new Json($faq, $category, $configuration),
            'pdf' => new Pdf($faq, $category, $configuration),
            default => throw new Exception('Export not implemented!'),
        };
    }

    /**
     * Returns the timestamp of the export.
     */
    public static function getExportTimestamp(): string
    {
        return date('Y-m-d-H-i-s', Request::createFromGlobals()->server->get('REQUEST_TIME'));
    }
}
