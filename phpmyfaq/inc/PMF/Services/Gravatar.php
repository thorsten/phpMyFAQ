<?php

/**
 * Service class for Gravatar support.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2013-01-14
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Services_Gravatar.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2013-01-14
 */
class PMF_Services_Gravatar extends PMF_Services
{
    /**
     * http://gravatar.com/.
     * 
     * @var string
     */
    private $httpBaseUrl = 'http://gravatar.com/';

    /**
     * https://secure.gravatar.com/.
     * 
     * @var string
     */
    private $httpsBaseUrl = 'https://secure.gravatar.com/';

    /**
     * Returns a image or the URL to the image of a Gravatar based off an email
     * address.
     *
     * @param string $email  Email address
     * @param array  $params Allows multiple keys with values to give more control
     *
     * @return string
     */
    public function getImage($email, $params = [])
    {
        $imageUrl = $this->getUrl().'avatar/'.$this->getHash($email);

        $opts = [];

        if (isset($params['default'])) {
            $opts[] = 'default='.$params['default'];
        }
        if (isset($params['size'])) {
            $opts[] = 'size='.$params['size'];
        }
        if (isset($params['rating'])) {
            $opts[] = 'rating='.$params['rating'];
        }
        if (isset($params['force_default']) && $params['force_default'] === true) {
            $opts[] = 'forcedefault=y';
        }
        if (!isset($params['class'])) {
            $params['class'] = '';
        }

        $gravatar = $imageUrl.(sizeof($opts) > 0 ? '?'.implode($opts, '&') : false);

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
    public function getUrl()
    {
        return (isset($_SERVER['HTTPS'])) ? $this->httpsBaseUrl : $this->httpBaseUrl;
    }

    /**
     * Returns a MD5 hash of an email address.
     *
     * @param string $email Email address
     *
     * @return string
     */
    public static function getHash($email)
    {
        return md5($email);
    }
}
