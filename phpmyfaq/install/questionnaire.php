<?php
/*
* This class collects data which is used to create some usage statistics
*
* The collected data is - after authorization of the administrator - submitted
* to phpMyFAQ.de. For privacy reasons we try to collect only data which isn't private
* or doesn't give any information which might help to identify the user
*/
class PMF_Questionnaire_Data
{
    var $data = null;
    var $config;
    var $oldversion;

    function PMF_Questionnaire_Data($config, $oldversion = 0)
    {
        $this->config = $config;
        $this->config['oldversion'] = $oldversion;
    }

    function get()
    {
        if (!$this->data) {
            $this->collect();
        }

        return $this->data;
    }

    function collect()
    {
        $this->data = array(
            'phpMyFAQ' => $this->getPmfInfo(),
            'PHP'      => $this->getPHPInfo(),
            'System'   => $this->getSystemInfo(),
        );
    }

    /**
    * Collect data about this pmf installation
    */
    function getPMFInfo()
    {
        // oldversion isn't a real pmf config option and jsut used by this class
        $settings = array('version', 'oldversion', 'language', 'permLevel', 'main.languageDetection', 'ldap_support');
        return array_intersect_key($this->config, array_flip($settings));
    }

    /**
    * Collect the data about the PHP runtime setup
    */
    function getPHPInfo() {
        return array(
            'version'           => PHP_VERSION,
            'sapi'              => PHP_SAPI,
            'int_size'          => defined('PHP_INT_SIZE') ? PHP_INT_SIZE : '',
            'safe_mode'         => (int)ini_get('safe_mode'),
            'open_basedir'      => (int)ini_get('open_basedir'),
            'memory_limit'      => ini_get('memory_limit'),
            'allow_url_fopen'   => (int)ini_get('allow_url_fopen'),
            'allow_url_include' => (int)ini_get('allow_url_include'),
            'file_uploads'      => (int)ini_get('file_uploads'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'     => ini_get('post_max_size'),
            'disable_functions' => ini_get('disable_functions'),
            'disable_classes'   => ini_get('disable_classes'),
            'enable_dl'         => (int)ini_get('enable_dl'),
            'magic_quotes_gpc'  => (int)ini_get('magic_quotes_gpc'),
            'register_globals'  => (int)ini_get('register_globals'),
            'filter.default'    => ini_get('filter.default'),
            'zend.ze1_compatibility_mode' => (int)ini_get('zend.ze1_compatibility_mode'),
            'unicode.sematics'  => (int)ini_get('unicode.semantics'),
            'zend_thread_safty' => (int)function_exists('zend_thread_id'),
            'extensions'        => get_loaded_extensions(),
        );
    }

    /**
    * Collect general system information, like OS or IP (shortened)
    */
    function getSystemInfo() {
        return array(
            'os' => PHP_OS,
            // we don't want the real IP adress (privacy!) but only the IP range to see whether it'S a private natwork or public
            'ip' => substr_replace($_SERVER["SERVER_ADDR"], '.XXX', strrpos($_SERVER["SERVER_ADDR"], '.')),
        );
    }
}

function data_printer($value, $key) {
    echo '<dt>', htmlentities($key), '</dt><dd>';
    if (is_array($value)) {
        echo '<dl>';
        array_walk($value, 'data_printer');
        echo '</dl>';
    } else {
        echo htmlentities($value);
    }
    echo "</dd>\n";
}

