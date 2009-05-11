<?php
/**
 * Sessionbrowser
 * 
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-05-11
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

if(!$permission["edittranslation"]) {
    print $PMF_LANG['err_NotAuth'];
    return;
}

$translateLang = PMF_Filter::filterInput(INPUT_GET, 'translang', FILTER_SANITIZE_STRING);

if(empty($translateLang) || !file_exists(PMF_ROOT_DIR . "/lang/language_$translateLang.php")) {
    header("Location: ?action=translist");
}

/**
 * English is our exemplary language
 */
$leftVarsOnly  = getTransVars(PMF_ROOT_DIR . "/lang/language_en.php");
$rightVarsOnly = getTransVars(PMF_ROOT_DIR . "/lang/language_$translateLang.php");

?>
<form>
<input type="hidden" name="translang" value="<?php  ?>" />
<table>
<?php
    while(list($key, $line) = each($leftVarsOnly)):   
    
?>
<tr>
<td><input style="width: 350px;" type="text" name="<?php echo $key?>" value="<?php echo htmlspecialchars($line)?>" disabled="disabled"     /></td>
<td><input style="width: 350px;" type="text" name="<?php echo $key?>" value="<?php echo @htmlspecialchars($rightVarsOnly[$key]) ?>" /></td>
</tr>
<?php endwhile; ?>
</table>
</form>
<?php 
/**
 * Parse language file
 *
 * @param string $filepath
 * 
 * @return array
 */
function getTransVars($filepath)
{
    $orig = file($filepath);
    $retval = array();
    
    while(list(,$line) = each($orig)) {
        $line = rtrim($line);
        /**
         * Bypass all but variable definitions
         */
        $m = array();
        if(strlen($line) && '$' == $line[0]) {
            preg_match('/\$([^\=]+)=(\s?)(\"(.+)\"|.+)\;.*/xU', $line, $m);
            $key = str_replace(array('["', '"]', '[\'', '\']'), array('[', ']', '[', ']'), trim($m[1]));
            
            $tmp = trim($m[3]);
            if(0 === strstr($tmp, 'array')) {
                $tmp2 = preg_split('/\,\s?/', $tmp);
                foreach($tmp2 as $val) {
                    $tmp3 = explode('=>', $val);
                    $retval[$key][trim($tmp3[0])] = substr(trim($tmp[1]), 1, -1);
                }
            } else {
                $retval[$key] = substr($tmp, 1, -1);
            }
        }
    }
//print '<pre>' . print_r($retval, true) . '</pre>';
    return $retval;
}
?>