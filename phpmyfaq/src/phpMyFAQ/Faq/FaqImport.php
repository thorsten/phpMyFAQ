<?php

/**
 * Class for importing records from a csv file.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-05
 */

namespace phpMyFAQ\Faq;

/**
 * Class FaqImport
 *
 * @package phpMyFAQ\Faq
 */
class FaqImport {
    public function parseCSV($handle): array
    {
        while (($data = fgetcsv($handle)) !== false) {
            $csvData[] = $data;
        }
        return $csvData;
    }

    public function isCSVFile($file): bool
    {
        $allowedExtensions = array("csv");
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

        return in_array(strtolower($fileExtension), $allowedExtensions);
    }
}
