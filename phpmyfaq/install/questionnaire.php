<?php
/*
 * $Id: questionnaire.php,v 1.5 2007-03-26 23:03:24 johannes Exp $
 *
 * This class collects data which is used to create some usage statistics.
 *
 * The collected data is - after authorization of the administrator - submitted
 * to phpMyFAQ.de. For privacy reasons we try to collect only data which aren't private
 * or don't give any information which might help to identify the user.
 *
 * @author      Johannes Schlueter <johannes@php.net>
 * @since       2007-03-17
 * @copyright   (c) 2007 phpMyFAQ Team
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

class PMF_Questionnaire_Data
{
    var $data = null;
    var $config;
    var $oldversion;

    /**
     * Constructor.
     *
     * @param   array
     * @param   string
     * @return  void
     * @access  public
     * @since   2007-03-17
     * @author  Johannes Schlueter <johannes@php.net>
     */
    function PMF_Questionnaire_Data($config, $oldversion = 0)
    {
        $this->config = $config;
        $this->config['oldversion'] = $oldversion;
    }

    /**
     * Get data as an array.
     *
     * @return  array
     * @access  public
     * @since   2007-03-17
     * @author  Johannes Schlueter <johannes@php.net>
     */
    function get()
    {
        if (!$this->data) {
            $this->collect();
        }

        return $this->data;
    }

    /**
     * Collect info into the data property.
     *
     * @return  void
     * @access  public
     * @since   2007-03-17
     * @author  Johannes Schlueter <johannes@php.net>
     */
    function collect()
    {
        $this->data = array(
            'phpMyFAQ'  => $this->getPmfInfo(),
            'PHP'       => $this->getPHPInfo(),
            'System'    => $this->getSystemInfo()
        );
    }

    /**
     * Get data about this phpMyFAQ installation.
     *
     * @return  array
     * @access  public
     * @since   2007-03-17
     * @author  Johannes Schlueter <johannes@php.net>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function getPMFInfo()
    {
        // oldversion isn't a real PMF config option and it is just used by this class
        $settings = array(
            'version',
            'oldversion',
            'language',
            'permLevel',
            'main.languageDetection',
            'ldap_support'
        );

        if (function_exists('array_intersect_key')) {
            return array_intersect_key($this->config, array_flip($settings));
        } else {
            $result = array();
            $selected = array_flip($settings);
            foreach ($this->config as $key => $value) {
                if (array_key_exists($key, $selected)) {
                    $result[$key] = $this->config[$key];
                }
            }

            return $result;
        }
    }

    /**
     * Get data about the PHP runtime setup.
     *
     * @return  array
     * @access  public
     * @since   2007-03-17
     * @author  Johannes Schlueter <johannes@php.net>
     */
    function getPHPInfo()
    {
        return array(
            'version'                       => PHP_VERSION,
            'sapi'                          => PHP_SAPI,
            'int_size'                      => defined('PHP_INT_SIZE') ? PHP_INT_SIZE : '',
            'safe_mode'                     => (int)ini_get('safe_mode'),
            'open_basedir'                  => (int)ini_get('open_basedir'),
            'memory_limit'                  => ini_get('memory_limit'),
            'allow_url_fopen'               => (int)ini_get('allow_url_fopen'),
            'allow_url_include'             => (int)ini_get('allow_url_include'),
            'file_uploads'                  => (int)ini_get('file_uploads'),
            'upload_max_filesize'           => ini_get('upload_max_filesize'),
            'post_max_size'                 => ini_get('post_max_size'),
            'disable_functions'             => ini_get('disable_functions'),
            'disable_classes'               => ini_get('disable_classes'),
            'enable_dl'                     => (int)ini_get('enable_dl'),
            'magic_quotes_gpc'              => (int)ini_get('magic_quotes_gpc'),
            'register_globals'              => (int)ini_get('register_globals'),
            'filter.default'                => ini_get('filter.default'),
            'zend.ze1_compatibility_mode'   => (int)ini_get('zend.ze1_compatibility_mode'),
            'unicode.semantics'             => (int)ini_get('unicode.semantics'),
            'zend_thread_safty'             => (int)function_exists('zend_thread_id'),
            'extensions'                    => get_loaded_extensions()
        );
    }

    /**
     * Get data about the general system information, like OS or IP (shortened).
     *
     * @return  array
     * @access  public
     * @since   2007-03-17
     * @author  Johannes Schlueter <johannes@php.net>
     */
    function getSystemInfo()
    {
        $aIPAddress = explode('.', $_SERVER['SERVER_ADDR']);

        return array(
            'os'    => PHP_OS,
            // we don't want the real IP address (for privacy policy reasons) but only
            // a network address to see whether your phpMyFAQ is running on a private or public network.
            // IANA reserved addresses for private networks (RFC 1918) are:
            // - 10.0.0.0/8
            // - 172.16.0.0/12
            // - 192.168.0.0/16
            'ip'    => $aIPAddress[0].'.'.$aIPAddress[1].'.XXX.YYY'
        );
    }
}

/**
 * Output the data as an HTML Definition List.
 *
 * @param   mixed
 * @param   string
 * @param   string
 * @return  void
 * @access  public
 * @since   2007-03-17
 * @author  Johannes Schlueter <johannes@php.net>
 */
function data_printer($value, $key, $ident = "\n\t")
{
    echo $ident, '<dt>', htmlentities($key), '</dt>', $ident, "\t", '<dd>';
    if (is_array($value)) {
        echo '<dl>';
        array_walk($value, 'data_printer', $ident."\t");
        echo $ident, "\t", '</dl>';
    } else {
        echo htmlentities($value);
    }
    echo '</dd>';
}
