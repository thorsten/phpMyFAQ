<?php
/**
 * Handle ajax requests for the interface translation tool
 * 
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-05-12
 * @copyright  2003-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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
if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajax_action = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);

switch($ajax_action) {
    
    case 'save_translated_lang':
        
        if (!$permission["edittranslation"]) {
            print $PMF_LANG['err_NotAuth'];
            exit;
        }
        
        $lang     = $_POST['PMF_LANG']['metaLanguage'];
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
        /**
         * Read in the head of the file we're writing to
         */
        $fh   = fopen($filename, 'r');
        $line = '';
        do {
            $line             = fgets($fh);
            $newFileContents .= $line;
        }
        while ('*/' != substr(trim($line), -2));
        fclose($fh);
        
        /**
         * Build language variable definitions
         */
        foreach ($_POST['PMF_LANG'] as $key => $val) {
            if (is_string($val)) {
                /**
                 * Since we get data per ajax, it's always utf-8 encoded
                 */
                if(PMF_String::isUTF8($val)) {
                    $val = @iconv('UTF-8', $PMF_LANG["metaCharset"], $val);
                }
                       
                $val = str_replace(array('\\\\', '\"', '\\\''), array('\\', '"', "'"), $val);
                $val = str_replace("'", "\\'", $val);
                $newFileContents .= "\$PMF_LANG['$key'] = '$val';\n";
            } elseif (is_array($val)) {
                /**
                 * Here we deal with a two dimensional array
                 */
                foreach ($val as $key2 => $val2) {
                    /**
                     * Since we get data per ajax, it's always utf-8 encoded
                     */
                    if (PMF_String::isUTF8($val2)) {
                       $val2 = iconv('UTF-8', $PMF_LANG["metaCharset"], $val2);
                    }
                    
                    $newFileContents .= "\$PMF_LANG['$key']['$key2'] = '$val2';\n";
                }
            }
        }
        
        foreach ($_POST['LANG_CONF'] as $key => $val) {
            /**
             * Since we get data per ajax, it's always utf-8 encoded
             */
            if(PMF_String::isUTF8($val)) {
                $val = iconv('UTF-8', $PMF_LANG["metaCharset"], $val);
            }
            
            $newFileContents .= "\$LANG_CONF['$key'] = $val;\n";
        }
        
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
 * @package    phpMyFAQ
 * @subpackage i18n
%s * @since      %s
 * @version    SVN: \$Id: language_%s.php \$
 * @copyright  2004-%d phpMyFAQ Team
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

\$PMF_LANG['metaCharset'] = '%s';
\$PMF_LANG['metaLanguage'] = '%s';
\$PMF_LANG['language'] = '%s';
\$PMF_LANG['dir'] = '%s';
\$PMF_LANG['nplurals'] = '%s';
FILE;

        $authorTpl = '';
        foreach($author as $authorData) {
            $authorTpl .= " * @author     $authorData\n";
        }
        
        $fileTpl = sprintf($fileTpl, $langDesc, $authorTpl, date('Y-m-d'), $langCode, date('Y'),
                                     $langCharset, strtolower($langCode), $langName, $langDir, $langNPlurals);

        $retval = @file_put_contents(PMF_ROOT_DIR . '/lang/language_' . strtolower($langCode) . '.php', $fileTpl);
        print intval($retval);
    break;
    
    
    case 'send_translated_file':
        
        $lang     = PMF_Filter::filterInput(INPUT_GET, 'translang', FILTER_SANITIZE_STRING);
        $filename = PMF_ROOT_DIR . "/lang/language_$lang.php";
        
        if (!file_exists($filename)) {
            print 0;
            exit;
        }

        $letterTpl = '';
        
        $mail = new PMF_Mail();
        $mail->subject = 'New phpMyFAQ language file submitted';
        $mail->message = sprintf('The file below was sent by %s, which is using phpMyFAQ %s on %s',
                                 $user->userdata->get('email'), $PMF_CONF['main.currentVersion'], $_SERVER['HTTP_HOST']);
        $mail->addTo('thorsten@phpmyfaq.de');
        $mail->addAttachment($filename, null, 'text/plain');
        
        print (int) $mail->send();
    break;
}
