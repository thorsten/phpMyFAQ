<?php
/**
 * Handle ajax requests for the interface translation tool
 *
 * PHP 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <ab@php.net>
 * @author    Alexander Melnik <old@km.ua>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-05-12
 */

use Symfony\Component\HttpFoundation\JsonResponse;

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$response = new JsonResponse;
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
                    $val2 = str_replace(array('\\\\', '\"', '\\\''), array('\\', '"', "'"), $val2);
                    $val2 = str_replace("'", "\\'", $val2);
                    $_SESSION['trans']['rightVarsOnly']["PMF_LANG[$key][$key2]"] = $val2;
                }
            }
        }
        
        foreach ((array)@$_POST['LANG_CONF'] as $key => $val) {
            // if string like array(blah-blah-blah), extract the contents inside the brackets
            if (preg_match('/^\s*array\s*\(\s*(\d+.+)\s*\).*$/',$val,$matches1)) {
                // split the resulting string of delimiters such as "number =>"
                $valArr = preg_split(
                    '/\s*(\d+)\s*\=\>\s*/',
                    $matches1[1],
                    null,
                    PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY
                );
                // in an array $valArr contents like  "number substring", "substring", "number substring", "substring", ...
                $numVal = count($valArr);
                if ($numVal > 1) {
                    $newValArr = array();
                    for ($i = 0; $i < $numVal; $i += 2) {
                        if (is_numeric($valArr[$i])) {
                            // clearing quotes
                            if (preg_match('/^\s*\\\\*[\"|\'](.+)\\\\*[\"|\'][\s\,]*$/', $valArr[$i + 1], $matches2)) {
                                $subVal = $matches2[1];
                                // normalize quotes
                                $subVal = str_replace(array('\\\\', '\"', '\\\''), array('\\', '"', "'"), $subVal);
                                $subVal = str_replace("'", "\\'", $subVal);
                                // assembly of the original substring back
                                $newValArr[] = $valArr[$i].' => \''.$subVal.'\'';
                            }
                        }
                    }
                    $_SESSION['trans']['rightVarsOnly']["LANG_CONF[$key]"] = 'array('.implode(", ",$newValArr).')';
                }
            } else {  // compatibility for old behavior
                $val = str_replace(array('\\\\', '\"', '\\\''), array('\\', '"', "'"), $val);
                $val = str_replace("'", "\\'", $val);
                $_SESSION['trans']['rightVarsOnly']["LANG_CONF[$key]"]  = $val;
            }
        }
        
        $response->setData(1);
    break;
    
    case 'save_translated_lang':
        
        if (!$permission["edittranslation"]) {
            $response->setData($PMF_LANG['err_NotAuth']);
            break;
        }
        
        $lang     = $_SESSION['trans']['rightVarsOnly']["PMF_LANG[metaLanguage]"];
        $filename = PMF_ROOT_DIR . "/lang/language_$lang.php"; 
        
        if (!is_writable(PMF_ROOT_DIR . "/lang")) {
            $response->setData(0);
            break;
        }     
        
        if (!copy($filename, PMF_ROOT_DIR . "/lang/language_$lang.bak.php")) {
            $response->setData(0);
            break;
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
        $response->setData(intval($retval));
    break;
    
    case 'remove_lang_file':
        
        if (!$permission['deltranslation']) {
            $response->setData($PMF_LANG['err_NotAuth']);
            break;
        }
         
        $lang = PMF_Filter::filterInput(INPUT_GET, 'translang', FILTER_SANITIZE_STRING);
        
        if (!is_writable(PMF_ROOT_DIR . "/lang")) {
            $response->setData(0);
            break;
        }     
        
        if (!copy(PMF_ROOT_DIR . "/lang/language_$lang.php", PMF_ROOT_DIR . "/lang/language_$lang.bak.php")) {
            $response->setData(0);
            break;
        }
        
        if (!unlink(PMF_ROOT_DIR . "/lang/language_$lang.php")) {
            $response->setData(0);
            break;
        }

        $response->setData(1);
    break;
    
    case 'save_added_trans':
        
        if (!$permission["addtranslation"]) {
            $response->setData($PMF_LANG['err_NotAuth']);
            break;
        }        
        
        if (!is_writable(PMF_ROOT_DIR . "/lang")) {
            $response->setData(0);
            break;
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
            $response->setData(0);
            break;
        }
        
        $fileTpl     = <<<FILE
<?php
/**
 * %s
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   i18n
%s * @copyright  2004-%d phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
        $response->setData(intval($retval));
    break;
    
    
    case 'send_translated_file':
        
        $lang     = PMF_Filter::filterInput(INPUT_GET, 'translang', FILTER_SANITIZE_STRING);
        $filename = PMF_ROOT_DIR . "/lang/language_" . $lang . ".php";
        
        if (!file_exists($filename)) {
            $response->setData(0);
            break;
        }

        $letterTpl = '';
        
        $mail          = new PMF_Mail($faqConfig);
        $mail->subject = 'New phpMyFAQ language file submitted';
        $mail->message = sprintf('The file below was sent by %s, which is using phpMyFAQ %s on %s',
            $user->userdata->get('email'),
            $faqConfig->get('main.currentVersion'),
            $_SERVER['HTTP_HOST']);
            
        $mail->addTo('thorsten@phpmyfaq.de');
        $mail->addAttachment($filename, null, 'text/plain');

        $response->setData((int) $mail->send());
    break;
}

$response->send();
