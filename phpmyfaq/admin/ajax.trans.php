<?php

/**
 * Handle ajax requests for the interface translation tool.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @author    Alexander Melnik <old@km.ua>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-05-12
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajax_action = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$csrfToken  = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
if (is_null($csrfToken)) {
    $csrfToken  = PMF_Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);
}

if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
    exit(1);
}

switch ($ajax_action) {

    case 'save_page_buffer':
        /*
         * Build language variable definitions
         * @todo Change input handling using PMF_Filter
         */
        foreach ((array) @$_POST['PMF_LANG'] as $key => $val) {
            if (is_string($val)) {
                $val = str_replace(array('\\\\', '\"', '\\\''), array('\\', '"', "'"), $val);
                $val = str_replace("'", "\\'", $val);
                $_SESSION['trans']['rightVarsOnly']["PMF_LANG[$key]"] = $val;
            } elseif (is_array($val)) {
                /*
                 * Here we deal with a two dimensional array
                 */
                foreach ($val as $key2 => $val2) {
                    $val2 = str_replace(array('\\\\', '\"', '\\\''), array('\\', '"', "'"), $val2);
                    $val2 = str_replace("'", "\\'", $val2);
                    $_SESSION['trans']['rightVarsOnly']["PMF_LANG[$key][$key2]"] = $val2;
                }
            }
        }

        foreach ((array) @$_POST['LANG_CONF'] as $key => $val) {
            // if string like array(blah-blah-blah), extract the contents inside the brackets
            if (preg_match('/^\s*array\s*\(\s*(\d+.+)\s*\).*$/', $val, $matches1)) {
                // split the resulting string of delimiters such as "number =>"
                $valArr = preg_split(
                    '/\s*(\d+)\s*\=\>\s*/',
                    $matches1[1],
                    null,
                    PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
                );
                $numVal = count($valArr);
                if ($numVal > 1) {
                    $newValArr = [];
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
                    $_SESSION['trans']['rightVarsOnly']["LANG_CONF[$key]"] = 'array('.implode(', ', $newValArr).')';
                }
            } else {  // compatibility for old behavior
                $val = str_replace(array('\\\\', '\"', '\\\''), array('\\', '"', "'"), $val);
                $val = str_replace("'", "\\'", $val);
                $_SESSION['trans']['rightVarsOnly']["LANG_CONF[$key]"] = $val;
            }
        }

        echo 1;
    break;
    
    case 'save_translated_lang':

        if (!$user->perm->checkRight($user->getUserId(), 'edittranslation')) {
            echo $PMF_LANG['err_NotAuth'];
            exit;
        }

        $lang = strtolower($_SESSION['trans']['rightVarsOnly']['PMF_LANG[metaLanguage]']);
        $filename = PMF_ROOT_DIR.'/lang/language_'.$lang.'.php';

        if (!is_writable(PMF_ROOT_DIR.'/lang')) {
            echo 0;
            exit;
        }

        if (!copy($filename, PMF_ROOT_DIR.'/lang/language_'.$lang.'.bak.php')) {
            echo 0;
            exit;
        }

        $newFileContents = '';
        $tmpLines = [];

        // Read in the head of the file we're writing to
        $fh = fopen($filename, 'r');
        do {
            $line = fgets($fh);
            array_push($tmpLines, rtrim($line));
        } while ('*/' != substr(trim($line), -2));
        fclose($fh);

        // Construct lines with variable definitions
        foreach ($_SESSION['trans']['rightVarsOnly'] as $key => $val) {
            if (0 === strpos($key, 'PMF_LANG')) {
                $val = "'$val'";
            }
            array_push($tmpLines, '$'.str_replace(array('[', ']'), array("['", "']"), $key)." = $val;");
        }

        $newFileContents .= implode("\n", $tmpLines);

        unset($_SESSION['trans']);

        $retval = file_put_contents($filename, $newFileContents);
        echo intval($retval);
        break;

    case 'remove_lang_file':

        if (!$user->perm->checkRight($user->getUserId(), 'deltranslation')) {
            echo $PMF_LANG['err_NotAuth'];
            exit;
        }

        $lang = PMF_Filter::filterInput(INPUT_GET, 'translang', FILTER_SANITIZE_STRING);

        if (!is_writable(PMF_ROOT_DIR.'/lang')) {
            echo 0;
            exit;
        }

        if (!copy(PMF_ROOT_DIR."/lang/language_$lang.php", PMF_ROOT_DIR."/lang/language_$lang.bak.php")) {
            echo 0;
            exit;
        }

        if (!unlink(PMF_ROOT_DIR."/lang/language_$lang.php")) {
            echo 0;
            exit;
        }

        echo 1;
        break;

    case 'save_added_trans':

        if (!$user->perm->checkRight($user->getUserId(), 'addtranslation')) {
            echo $PMF_LANG['err_NotAuth'];
            exit;
        }

        if (!is_writable(PMF_ROOT_DIR.'/lang')) {
            echo 0;
            exit;
        }

        $langCode = PMF_Filter::filterInput(INPUT_POST, 'translang', FILTER_SANITIZE_STRING);
        $langName = @$languageCodes[$langCode];
        $langCharset = 'UTF-8';
        $langDir = PMF_Filter::filterInput(INPUT_POST, 'langdir', FILTER_SANITIZE_STRING);
        $langNPlurals = strval(PMF_Filter::filterVar(@$_POST['langnplurals'], FILTER_VALIDATE_INT, -1));
        $langDesc = PMF_Filter::filterInput(INPUT_POST, 'langdesc', FILTER_SANITIZE_STRING);
        $author = (array) @$_POST['author'];

        if (empty($langCode) || empty($langName) || empty($langCharset) ||
           empty($langDir) || empty($langDesc) || empty($author)) {
            echo 0;
            exit;
        }

        $fileTpl = <<<FILE
<?php
/**
 * %s
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   i18n
%s * @copyright  2001-%d phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
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

        $retval = @file_put_contents(PMF_ROOT_DIR.'/lang/language_'.strtolower($langCode).'.php', $fileTpl);
        echo intval($retval);
    break;

    case 'send_translated_file':

        $lang = PMF_Filter::filterInput(INPUT_GET, 'translang', FILTER_SANITIZE_STRING);
        $filename = PMF_ROOT_DIR.'/lang/language_'.$lang.'.php';

        if (!file_exists($filename)) {
            echo 0;
            exit;
        }

        $letterTpl = '';

        $mail = new PMF_Mail($faqConfig);
        $mail->subject = 'New phpMyFAQ language file submitted';
        $mail->message = sprintf('The file below was sent by %s, which is using phpMyFAQ %s on %s',
            $user->userdata->get('email'),
            $faqConfig->get('main.currentVersion'),
            $_SERVER['HTTP_HOST']);

        $mail->addTo('thorsten@phpmyfaq.de');
        $mail->addAttachment($filename, null, 'text/plain');

        echo (int) $mail->send();
    break;
}
