<?php

/**
 * API handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-03-27
 */

namespace phpMyFAQ;

use ErrorException;
use JsonException;
use phpMyFAQ\Core\Exception;
use stdClass;

/**
 * Class Api
 *
 * @package phpMyFAQ
 */
class Api
{
    private string $apiUrl = 'https://api.phpmyfaq.de/';

    private ?string $remoteHashes = null;

    /**
     * Api constructor.
     */
    public function __construct(private Configuration $config, private System $system)
    {
    }

    /**
     * Returns the installed, the current available and the next version
     * as array.
     *
     * @throws JsonException
     */
    public function getVersions(): array
    {
        $json = $this->fetchData($this->apiUrl . 'versions');
        $result = json_decode($json, null, 512, JSON_THROW_ON_ERROR);
        if ($result instanceof stdClass) {
            return [
                'installed' => $this->config->getVersion(),
                'current' => $result->stable,
                'next' => $result->development
            ];
        }

        throw new JsonException('phpMyFAQ Version API is not available.');
    }

    /**
     * Returns true, if installed version can be verified. Otherwise, false.
     *
     * @throws JsonException
     */
    public function isVerified(): bool
    {
        $this->remoteHashes = $this->fetchData($this->apiUrl . 'verify/' . $this->config->getVersion());

        if (json_decode($this->remoteHashes, null, 512, JSON_THROW_ON_ERROR) instanceof stdClass) {
            if (!is_array(json_decode($this->remoteHashes, true, 512, JSON_THROW_ON_ERROR))) {
                return false;
            }

            return true;
        }

        throw new JsonException('phpMyFAQ Verification API is not available.');
    }

    /**
     * @throws JsonException
     * @throws \Exception
     */
    public function getVerificationIssues(): array
    {
        return array_diff(
            json_decode($this->system->createHashes(), true, 512, JSON_THROW_ON_ERROR),
            json_decode($this->remoteHashes, true, 512, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Return the fetched content from a given URL
     */
    public function fetchData(string $url): string
    {
        return file_get_contents($url);
    }
}
