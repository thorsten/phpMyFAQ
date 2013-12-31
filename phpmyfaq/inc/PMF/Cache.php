<?php
/**
 * The Cache class implements caching factory to be used with different concrete cache services
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Cache
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-09-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Cache
 *
 * @category  phpMyFAQ
 * @package   Cache
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-09-23
 */
class PMF_Cache
{
    /**
     * @var PMF_Cache
     */
    protected static $instance = NULL;

    /**
     * @static
     * @param PMF_Configuration $faqConfig
     */
    public static function init(PMF_Configuration $faqConfig)
    {
        $config = array();
        if ($faqConfig->get('cache.varnishEnable')) {
            $config[VARNISH_CONFIG_PORT]    = $faqConfig->get('cache.varnishPort');
            $config[VARNISH_CONFIG_SECRET]  = $faqConfig->get('cache.varnishSecret');
            $config[VARNISH_CONFIG_TIMEOUT] = $faqConfig->get('cache.varnishTimeout');
            $config[VARNISH_CONFIG_HOST]    = $faqConfig->get('cache.varnishHost');

            self::$instance = new PMF_Cache_Varnish($config);
        } else {
            self::$instance = new PMF_Cache_Dummy($config);
        }
    }

    /**
     * @static
     * @return null|PMF_Cache
     */
    public static function getInstance()
    {
        return self::$instance;
    }
}
