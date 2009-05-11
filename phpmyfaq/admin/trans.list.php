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

$transDir = new DirectoryIterator(PMF_ROOT_DIR . "/lang");

?>
<?php if(!$transDir->isWritable()):
    echo '<font color="red">'. $PMF_LANG['msgLangDirIsntWritable'] . "</font><br>";
endif; ?>
<?php echo $PMF_LANG['msgChooseLanguageToTranslate'] ?>: <br>
<table>
<tr><td>Filename</td><td colspan="2">Actions</td></tr>
<?php if($permission["addtranslation"]): ?>
<tr><td colspan="3">
Add language
</td></tr>
<?php endif; ?>
<?php 
    foreach($transDir as $file) {
        if('.php' == substr($file, -4)) {
            $lang = str_replace(array('language_', '.php'), '', $file);
            ?>
            <tr>
            <td><?php echo $file ?></td>
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
            </tr>
            <?php 
        }
    }
?></table>