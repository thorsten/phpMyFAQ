<?php
/**
 * Handle ajax requests for the interface translation tool
 * 
 * PHP 5.2
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
 * 
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-05-12
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajax_action = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);

switch($ajax_action) {
    
    case 'save_page_buffer':
        /**
         * Build language variable definitions
         * @todo Change input handling using PMF_Filter
         */
        foreach ((array)@$_POST['PMF_LANG'] as $key => $val) {
        
            if (is_string($val)) {
                $val = str_replace(array('\\\\', '\"', '\\\''), array('\\', '"', "'"), $val);
                $val = str_replace("'", "\\'", $val);
                $_SESSION['trans']['rightVarsOnly']["PMF_LANG[$key]"]  = $val;
            } elseif (is_array($val)) {
                /**
                 * Here we deal with a two dimensional array
                 */
                foreach ($val as $key2 => $val2) {
                    $_SESSION['trans']['rightVarsOnly']["PMF_LANG[$key][$key2]"] = $val2;
                }
            }
        }
        
        foreach ((array)@$_POST['LANG_CONF'] as $key => $val) {
            $_SESSION['trans']['rightVarsOnly']["LANG_CONF[$key]"] = $val;
        }
        
        print 1;
    break;
    
    case 'save_translated_lang':
        
        if (!$permission["edittranslation"]) {
            print $PMF_LANG['err_NotAuth'];
            exit;
        }
        
        $lang     = $_SESSION['trans']['rightVarsOnly']["PMF_LANG[metaLanguage]"];
        $filename = PMF_ROOT_DIR . "/lang/language_$lang.php"; 
        
        if (!is_writable(PMF_ROOT_DIR . "/lang")) {
            print 0;
            exit;
        }     
        
        if (!copy($filename, PMF_ROOT_DIR . "/lang/language_$lang.bak.php")) {
            print 0;
            exit;
        }
        
        $newFileContents = '';
        $tmpLines        = array();
        
        /**
         * Read in the head of the file we're writing to
         */
        $fh = fopen($filename, 'r');
        do {
            $line = fgets($fh);
            array_push($tmpLines, rtrim($line));
        }
        while ('*/' != substr(trim($line), -2));
        fclose($fh);
       
        /**
         * Construct lines with variable definitions
         */
        foreach ($_SESSION['trans']['rightVarsOnly'] as $key => $val) {
            if (0 === strpos($key, 'PMF_LANG')) {
                $val = "'$val'";
            }
            array_push($tmpLines, '$' . str_replace(array('[', ']'), array("['", "']"), $key) . " = $val;");
        }
        
        $newFileContents .= implode("\n", $tmpLines);
        
        unset($_SESSION['trans']);
        
        $retval = @file_put_contents($filename, $newFileContents);
        print intval($retval);
    break;
    
    case 'remove_lang_file':
        
        if (!$permission['deltranslation']) {
            print $PMF_LANG['err_NotAuth'];
            exit;
        }
         
        $lang = PMF_Filter::filterInput(INPUT_GET, 'translang', FILTER_SANITIZE_STRING);
        
        if (!is_writable(PMF_ROOT_DIR . "/lang")) {
            print 0;
            exit;
        }     
        
        if (!copy(PMF_ROOT_DIR . "/lang/language_$lang.php", PMF_ROOT_DIR . "/lang/language_$lang.bak.php")) {
            print 0;
            exit;
        }
        
        if (!unlink(PMF_ROOT_DIR . "/lang/language_$lang.php")) {
            print 0;
            exit;
        }
        
        print 1;
    break;
    
    case 'save_added_trans':
        
        if (!$permission["addtranslation"]) {
            print $PMF_LANG['err_NotAuth'];
            exit;
        }        
        
        if (!is_writable(PMF_ROOT_DIR . "/lang")) {
            print 0;
            exit;
        }
        
        $langCode    = PMF_Filter::filterInput(INPUT_POST, 'translang', FILTER_SANITIZE_STRING);
        $langName    = @$languageCodes[$langCode];
        $langCharset = "UTF-8";
        $langDir     = PMF_Filter::filterInput(INPUT_POST, 'langdir', FILTER_SANITIZE_STRING);
        $langNPlurals= strval(PMF_Filter::filterVar(@$_POST['langnplurals'], FILTER_VALIDATE_INT, -1));
        $langDesc    = PMF_Filter::filterInput(INPUT_POST, 'langdesc', FILTER_SANITIZE_STRING);
        $author      = (array) @$_POST['author'];
        
        if(empty($langCode) || empty($langName) || empty($langCharset) ||
           empty($langDir) || empty($langDesc) || empty($author)) {
            print 0;
            exit;
        }
        
        $fileTpl     = <<<FILE
<?php
/**
 * %s
 *
 * PHP Version 5.2
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
 *
 * @category  phpMyFAQ
 * @package   i18n
%s * @copyright  2004-%d phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since      %s
 */

\$PMF_LANG['metaCharset'] = '%s';
\$PMF_LANG['metaLanguage'] = '%s';
\$PMF_LANG['language'] = '%s';
\$PMF_LANG['dir'] = '%s';
\$PMF_LANG['nplurals'] = '%s';
FILE;

        $authorTpl = '';
        foreach ($author as $authorData) {
            $authorTpl .= " * @author    $authorData\n";
        }
        
        $fileTpl = sprintf($fileTpl, $langDesc, $authorTpl, date('Y-m-d'), $langCode, date('Y'),
                                     $langCharset, strtolower($langCode), $langName, $langDir, $langNPlurals);

        $retval = @file_put_contents(PMF_ROOT_DIR . '/lang/language_' . strtolower($langCode) . '.php', $fileTpl);
        print intval($retval);
    break;
    
    
    case 'send_translated_file':
        
        $lang     = PMF_Filter::filterInput(INPUT_GET, 'translang', FILTER_SANITIZE_STRING);
        $filename = PMF_ROOT_DIR . "/lang/language_" . $lang . ".php";
        
        if (!file_exists($filename)) {
            print 0;
            exit;
        }

        $letterTpl = '';
        
        $mail          = new PMF_Mail();
        $mail->subject = 'New phpMyFAQ language file submitted';
        $mail->message = sprintf('The file below was sent by %s, which is using phpMyFAQ %s on %s',
            $user->userdata->get('email'), 
            PMF_Configuration::getInstance()->get('main.currentVersion'), 
            $_SERVER['HTTP_HOST']);
            
        $mail->addTo('thorsten@phpmyfaq.de');
        $mail->addAttachment($filename, null, 'text/plain');
        
        print (int) $mail->send();
    break;
}
