<?php

/**
 * Service class for Gravatar support.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-01-14
 */

namespace phpMyFAQ\Services;

/**
 * Class Gravatar
 *
 * @package phpMyFAQ\Services
 */
class Gravatar
{
    /**
     * @var string
     */
    private $httpBaseUrl = 'http://gravatar.com/';

    /**
     * @var string
     */
    private $httpsBaseUrl = 'https://secure.gravatar.com/';

    /**
     * Returns a image or the URL to the image of a Gravatar based off an email
     * address.
     *
     * @param string $email Email address
     * @param string[] $params Allows multiple keys with values to give more control
     * @return string
     */
    public function getImage(string $email, array $params = []): string
    {
        $imageUrl = $this->getUrl() . 'avatar/' . $this->getHash($email);
        $opts = [];

        if (isset($params['default'])) {
            $opts[] = 'default=' . $params['default'];
        }
        if (isset($params['size'])) {
            $opts[] = 'size=' . $params['size'];
        }
        if (isset($params['rating'])) {
            $opts[] = 'rating=' . $params['rating'];
        }
        if (isset($params['force_default']) && $params['force_default']) {
            $opts[] = 'forcedefault=y';
        }
        if (!isset($params['class'])) {
            $params['class'] = '';
        }

        $gravatar = $imageUrl . (sizeof($opts) > 0 ? '?' . implode('&', $opts) : false);

        return sprintf(
            '<img src="%s" class="%s" alt="Gravatar">',
            htmlspecialchars($gravatar),
            $params['class']
        );
    }

    /**
     * Returns the base URL we are working with depending what protocol we
     * are using.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return (isset($_SERVER['HTTPS'])) ? $this->httpsBaseUrl : $this->httpBaseUrl;
    }

    /**
     * Returns a MD5 hash of an email address.
     *
     * @param string $email Email address
     * @return string
     */
    public static function getHash(string $email): string
    {
        return md5($email);
    }
}
