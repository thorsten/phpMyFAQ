<?php

/**
 * The Setup Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-17
 */

namespace phpMyFAQ\Controller;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\System;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SetupController
{
    public function update(): StreamedResponse
    {
        $update = new Update(new System(), Configuration::getConfigurationInstance());
        $update->setVersion(System::getVersion());

        return new StreamedResponse(function () use ($update) {
            $progressCallback = function ($progress) {
                echo json_encode(['progress' => $progress], JSON_THROW_ON_ERROR) . "\n";
                ob_flush();
                flush();
            };

            try {
                if ($update->applyUpdates($progressCallback)) {
                    echo json_encode(['message' => '✅ Database successfully updated.']);
                }
            } catch (Exception $e) {
                echo json_encode(['message' => 'Update database failed: ' . $e->getMessage()], JSON_THROW_ON_ERROR);
            }
        });
    }
}
