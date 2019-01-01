<?php

namespace phpMyFAQ;

/**
 * API handler class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-03-27
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Class Api
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-03-27
 */
class Api
{
    /**
     * @var string
     */
    private $apiUrl = 'https://api.phpmyfaq.de';

    /**
     * @var Configuration
     */
    private $config = null;

    /**
     * @var System
     */
    private $system = null;

    /**
     * @var string
     */
    private $remoteHashes = null;

    /**
     * Api constructor.
     *
     * @param Configuration $config
     * @param System $system
     */
    public function __construct(Configuration $config, System $system)
    {
        $this->config = $config;
        $this->system = $system;
    }

    /**
     * Returns the installed, the current available and the next version
     * as array.
     *
     * @return array
     * @throws Exception
     */
    public function getVersions()
    {
        $json = file_get_contents($this->apiUrl . '/versions');
        $result = json_decode($json);
        if ($result instanceof \stdClass) {
            return [
                'installed' => $this->config->get('main.currentVersion'),
                'current' => $result->stable,
                'next' => $result->development
            ];
        }

        throw new Exception('phpMyFAQ Version API is not available.');
    }

    /**
     * Returns true, if installed version can be verified. Otherwise false.
     *
     * @return bool
     * @throws Exception
     */
    public function isVerified()
    {
        $this->remoteHashes = file_get_contents($this->apiUrl . '/verify/' . $this->config->get('main.currentVersion'));

        if (json_decode($this->remoteHashes) instanceof \stdClass) {
            if (!is_array(json_decode($this->remoteHashes, true))) {
                return false;
            }

            return true;
        }

        throw new Exception('phpMyFAQ Verification API is not available.');
    }

    /**
     * @return array
     */
    public function getVerificationIssues()
    {
        return array_diff(
            json_decode($this->system->createHashes(), true),
            json_decode($this->remoteHashes, true)
        );
    }
}
