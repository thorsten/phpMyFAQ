<?php
/**
 * List avaliable interface translations and actions
 * depending on user right
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

$transDir = new DirectoryIterator(PMF_ROOT_DIR . "/lang");
?>
<?php echo $PMF_LANG['msgChooseLanguageToTranslate'] ?>: <br />
<table>
<?php if($permission["addtranslation"]): ?>
<tr><td colspan="4">
<?php if(!$transDir->isWritable()):
    echo '<font color="red">'. $PMF_LANG['msgLangDirIsntWritable'] . "</font><br />";
endif; ?>
<form action="?action=transadd">
<fieldset>
<legend>Add translation</legend>
Lang code: <input name="translang" type="text" /> <input type="submit" />
</fieldset>
</form>
</td></tr>
<?php endif; ?>
<tr><td>Filename</td><td colspan="2">Actions</td><td>Writable</td></tr>
<?php
    $sortedLangList = array();
    
    foreach($transDir as $file) {
        if($file->isFile() && '.php' == PMF_String::substr($file, -4)) {
            $lang = str_replace(array('language_', '.php'), '', $file);

            /**
             * English is our exemplary language which won't be changed
             */
            if('en' == $lang) {
                continue;
            }
            
            $sortedLangList[] = $lang; 
        }
    }
    
    sort($sortedLangList);
    while(list(,$lang) = each($sortedLangList)) {           
        ?>
        <tr>
        <td><?php echo $lang ?></td>
        <?php if($permission["edittranslation"]): ?>
        <td><a href="?action=transedit&amp;translang=<?php print $lang ?>" >Edit</a></td>
        <?php else: ?>
        <td>&nbsp;</td>
        <?php endif; ?>
        <?php if($permission["edittranslation"]): ?>
        <td><a href="?action=transdel&amp;translang=<?php print $lang ?>" >Delete</a></td>
        <?php else: ?>
        <td>&nbsp;</td>
        <?php endif; ?>
        <?php if(is_writable(PMF_ROOT_DIR . "/lang/language_$lang.php")): ?>
        <td><font color="green">yes</font></td>
        <?php else: ?>
        <td><font color="red">no</font></td>
        <?php endif; ?>
        </tr>
        <?php 
    }
?></table>