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
$tt = new PMF_TransTool;
?>
<?php echo $PMF_LANG['msgChooseLanguageToTranslate'] ?>: <br />
<table cellspacing="7">
<?php if(!$transDir->isWritable()):
    echo '<tr><td colspan="5"><font color="red">'. $PMF_LANG['msgLangDirIsntWritable'] . "</font></tr></td>";
endif; ?>
<?php if($permission["addtranslation"]): ?>
<tr><td colspan="6">
<a href="?action=transadd"><?php echo $PMF_LANG['msgTransToolAddNewTranslation'] ?></a>
</td></tr>
<?php endif; ?>
<tr><td><?php echo $PMF_LANG['msgTransToolFilename'] ?></td>
    <td colspan="3"><?php echo $PMF_LANG['msgTransToolActions'] ?></td>
    <td><?php echo $PMF_LANG['msgTransToolWritable'] ?></td>
    <td><?php echo $PMF_LANG['msgTransToolPercent'] ?></td>
</tr>
<?php
    $sortedLangList = array();
    
    foreach($transDir as $file) {
        if($file->isFile() && '.php' == PMF_String::substr($file, -4) && 'bak' != PMF_String::substr($file, -7, -4)) {
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
        <tr class="lang_<?php echo $lang ?>_container">
        <td><?php echo $lang ?></td>
        <?php if($permission["edittranslation"]): ?>
        <td>[<a href="?action=transedit&amp;translang=<?php print $lang ?>" ><?php echo $PMF_LANG['msgEdit'] ?></a>]</td>
        <?php else: ?>
        <td>&nbsp;</td>
        <?php endif; ?>
        <?php if($permission["edittranslation"]): ?>
        <td>[<a href="javascript: del('<?php print $lang ?>');" ><?php echo $PMF_LANG['msgDelete'] ?></a>]</td>
        <?php else: ?>
        <td>&nbsp;</td>
        <?php endif; ?>
        <td>[<a href="javascript: sendToTeam('<?php print $lang ?>');" ><?php echo $PMF_LANG['msgTransToolSendToTeam'] ?></a>]</td>
        <?php if(is_writable(PMF_ROOT_DIR . "/lang/language_$lang.php")): ?>
        <td><font color="green"><?php echo $PMF_LANG['msgYes'] ?></font></td>
        <?php else: ?>
        <td><font color="red"><?php echo $PMF_LANG['msgNo'] ?></font></td>
        <?php endif; ?>
        <td><?php echo$tt->getTranslatedPercentage(PMF_ROOT_DIR . "/lang/language_en.php", PMF_ROOT_DIR . "/lang/language_$lang.php"); ?>%</td>
        </tr>
        <?php 
    }
?></table>
<script>
/**
 * Remove a language file
 *
 * @param string lang Language to remove 
 *
 * @return void
 */
function del(lang)
{
    if(!confirm('<?php echo $PMF_LANG['msgTransToolSureDeleteFile'] ?>')) {
        return;
    }
    
    $('#saving_data_indicator').html('<img src="images/indicator.gif" /> <?php echo $PMF_LANG['msgRemoving3Dots'] ?>');

    $.get('index.php?action=ajax&ajax=trans&ajaxaction=remove_lang_file',
          {translang: lang},
          function(retval, status) {
              if(1*retval > 0 && 'success' == status) {
                  $('.lang_' + lang + '_container').fadeOut('slow');
                  $('#saving_data_indicator').html('<?php echo $PMF_LANG['msgTransToolFileRemoved'] ?>');
              } else {
                  $('#saving_data_indicator').html('<?php echo $PMF_LANG['msgTransToolErrorRemovingFile'] ?>');
                  alert('<?php echo $PMF_LANG['msgTransToolErrorRemovingFile'] ?>');
              }
        }
    );
}

/**
 * Send a translation file to the phpMyFAQ team
 * 
 * @param string lang
 *
 * @return void
 */
function sendToTeam(lang)
{
     $('#saving_data_indicator').html('<img src="images/indicator.gif" /> <?php echo $PMF_LANG['msgSending3Dots'] ?>');

     var msg = '';;
               
     $.get('index.php?action=ajax&ajax=trans&ajaxaction=send_translated_file',
             {translang: lang},
             function(retval, status) {
                 if(1*retval > 0 && 'success' == status) {
                     msg = '<?php echo $PMF_LANG['msgTransToolFileSent'] ?>';
                 } else {
                     msg = '<?php echo $PMF_LANG['msgTransToolErrorSendingFile'] ?>';
                 }
           }
       );

     $('#saving_data_indicator').html('<?php echo $PMF_LANG['msgTransToolFileSent'] ?>');
     alert('<?php echo $PMF_LANG['msgTransToolFileSent'] ?>');
}
</script>