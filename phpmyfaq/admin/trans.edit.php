<?php
/**
 * Read in files for the translation and show them inside a form.
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

$tt = new PMF_TransTool;
/**
 * English is our exemplary language
 */
$leftVarsOnly  = $tt->getVars(PMF_ROOT_DIR . "/lang/language_en.php");
$rightVarsOnly = $tt->getVars(PMF_ROOT_DIR . "/lang/language_$translateLang.php");

?>
<form id="transDiffForm">
<table>
<tr><td><b><?php echo $PMF_LANG['msgVariable'] ?></b></td><td><b>en</b></td><td><b><?php echo $translateLang ?></b></td></tr>
<?php while(list($key, $line) = each($leftVarsOnly)): ?>
<tr>
<td><?php echo $key?></td>
<td><input style="width: 300px;" type="text" value="<?php echo htmlspecialchars($line) ?>" disabled="disabled" /></td>
<?php 
    if(array_key_exists($key, $rightVarsOnly) && ($line != $rightVarsOnly[$key] ||
       $tt->isKeyIgnorable($key) || $tt->isValIgnorable($line))): 
?>
<td><input style="width: 300px;" type="text" name="<?php echo $key?>" value="<?php echo htmlspecialchars($rightVarsOnly[$key]) ?>" /></td>
<?php else: ?>
<td><input style="width: 300px;border-color: red;" type="text" name="<?php echo $key?>" value="<?php echo htmlspecialchars($line) ?>" /></td>
<?php endif; ?>
</tr>
<?php endwhile; ?>
<tr>
<td>&nbsp;</td>
<td><input type="button" value="<?php echo $PMF_LANG['msgCancel'] ?>" onclick="location.href='?action=translist'" /></td>
<td><input type="button"
           value="<?php echo $PMF_LANG['msgSave'] ?>"
           onclick="save()"<?php if(!is_writable(PMF_ROOT_DIR . "/lang/language_$translateLang.php")) {echo ' disabled="disabled"';} ?> /></td>
</tr>
</table>
</form>
<script>
/**
 * Transparently save the translation form
 * @return void
 */
function save()
{
    $('#saving_data_indicator').html('<img src="images/indicator.gif" /> <?php echo $PMF_LANG['msgSaving3Dots'] ?>');
    
    var data = {};
    var form = document.getElementById('transDiffForm');
    for(var i=0; i < form.elements.length;i++) {
        var element = form.elements[i]
        if(('text' == element.type || 'hidden' == element.type) && !element.disabled) {
            data[element.name] = element.value
        }
    }

    $.post('index.php?action=ajax&ajax=trans&ajaxaction=save_translated_lang',
            data,
            function (retval, status) {
                if(1*retval > 0 && 'success' == status) {
                    $('#saving_data_indicator').html('<?php echo $PMF_LANG['msgTransToolFileSaved'] ?>');
                    document.location = '?action=translist'
                } else {
                    $('#saving_data_indicator').html('<?php echo $PMF_LANG['msgTransToolErrorSavingFile'] ?>');
                }
            }
    )
}
</script>