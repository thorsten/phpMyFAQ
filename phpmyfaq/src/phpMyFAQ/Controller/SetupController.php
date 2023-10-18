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
use phpMyFAQ\Filter;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SetupController
{
    /**
     * @throws \JsonException
     */
    public function update(Request $request): StreamedResponse
    {
        $update = new Update(new System(), Configuration::getConfigurationInstance());
        $update->setVersion(System::getVersion());

        //$postBody = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        //$step = Filter::filterVar($postBody->step, FILTER_VALIDATE_INT);
        $step = 1;

        return new StreamedResponse(function () use ($step, $update) {
            $progressCallback = function ($progress) {
                echo json_encode(['progress' => $progress]) . "\n";
                ob_flush();
                flush();
            };

            try {
                if ($update->applyUpdates($progressCallback)) {
                    echo json_encode(['message' => '✅ Database successfully updated.']);
                }
            } catch (Exception $e) {
                echo json_encode(['message' => 'Update database failed: ' . $e->getMessage()]);
            }
        });
    }
}
