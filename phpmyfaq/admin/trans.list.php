<?php
/**
 * List avaliable interface translations and actions
 * depending on user right
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-05-11
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

clearstatcache();
if(isset($_SESSION['trans'])) {
    unset($_SESSION['trans']);
}

$langDir            = PMF_ROOT_DIR . DIRECTORY_SEPARATOR . "lang";
$transDir           = new DirectoryIterator($langDir);
$isTransDirWritable = is_writable($langDir);
$tt                 = new PMF_TransTool;

printf('<header><h2>%s</h2></header>', $PMF_LANG['ad_menu_translations']);
?>
        <p><?php print $PMF_LANG['msgChooseLanguageToTranslate'] ?>:</p>

        <?php if(!$isTransDirWritable):
            print '<p class="error">'. $PMF_LANG['msgLangDirIsntWritable'] . "</p>";
        endif; ?>

        <table class="table table-striped">
        <thead>
            <?php if($permission["addtranslation"] && $isTransDirWritable): ?>
            <tr>
                <th colspan="6">
                    <a href="?action=transadd"><?php print $PMF_LANG['msgTransToolAddNewTranslation'] ?></a>
                </th>
            </tr>
            <?php endif; ?>
            <tr>
                <th><?php print $PMF_LANG['msgTransToolLanguage'] ?></th>
                <th colspan="3"><?php print $PMF_LANG['msgTransToolActions'] ?></th>
                <th><?php print $PMF_LANG['msgTransToolWritable'] ?></th>
                <th><?php print $PMF_LANG['msgTransToolPercent'] ?></th>
            </tr>
        </thead>
        <tbody>
<?php
    $sortedLangList = array();
    
    foreach ($transDir as $file) {
        if ($file->isFile() && '.php' == PMF_String::substr($file, -4) && 'bak' != PMF_String::substr($file, -7, -4)) {
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
    
    while (list(,$lang) = each($sortedLangList)) { 
        $isLangFileWritable = is_writable($langDir . DIRECTORY_SEPARATOR . "language_$lang.php");
        $showActions        = $isTransDirWritable && $isLangFileWritable;
        ?>
            <tr class="lang_<?php print $lang ?>_container">
                <td><?php print $languageCodes[strtoupper($lang)] ?></td>
                <?php if($permission["edittranslation"] && $showActions): ?>
                <td>[<a href="?action=transedit&amp;translang=<?php print $lang ?>" ><?php print $PMF_LANG['msgEdit'] ?></a>]</td>
                <?php else: ?>
                <td>[<?php print $PMF_LANG['msgEdit'] ?>]</td>
                <?php endif; ?>
                <?php if($permission["deltranslation"] && $showActions): ?>
                <td>[<a href="javascript: del('<?php print $lang ?>');" ><?php print $PMF_LANG['msgDelete'] ?></a>]</td>
                <?php else: ?>
                <td>[<?php print $PMF_LANG['msgDelete'] ?>]</td>
                <?php endif; ?>
                <?php if($permission["edittranslation"] && $showActions): ?>
                <td>[<a href="javascript: sendToTeam('<?php print $lang ?>');" ><?php print $PMF_LANG['msgTransToolSendToTeam'] ?></a>]</td>
                <?php else: ?>
                <td>[<?php print $PMF_LANG['msgTransToolSendToTeam'] ?>]</td>
                <?php endif;?>
                <?php if($isLangFileWritable): ?>
                <td><font color="green"><?php print $PMF_LANG['msgYes'] ?></font></td>
                <?php else: ?>
                <td><font color="red"><?php print $PMF_LANG['msgNo'] ?></font></td>
                <?php endif; ?>
                <td><?php print $tt->getTranslatedPercentage($langDir . DIRECTORY_SEPARATOR . "language_en.php",
                                                            $langDir . DIRECTORY_SEPARATOR . "language_$lang.php"); ?>%</td>
            </tr>
        <?php 
    }
?>
        </tbody>
        </table>
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
            if (!confirm('<?php print $PMF_LANG['msgTransToolSureDeleteFile'] ?>')) {
                return;
            }

            $('#saving_data_indicator').html('<img src="images/indicator.gif" /> <?php print $PMF_LANG['msgRemoving3Dots'] ?>');

            $.get('index.php?action=ajax&ajax=trans&ajaxaction=remove_lang_file',
                  {translang: lang},
                  function(retval, status) {
                      if (1*retval > 0 && 'success' == status) {
                          $('.lang_' + lang + '_container').fadeOut('slow');
                          $('#saving_data_indicator').html('<?php print $PMF_LANG['msgTransToolFileRemoved'] ?>');
                      } else {
                          $('#saving_data_indicator').html('<?php print $PMF_LANG['msgTransToolErrorRemovingFile'] ?>');
                          alert('<?php print $PMF_LANG['msgTransToolErrorRemovingFile'] ?>');
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
             $('#saving_data_indicator').html('<img src="images/indicator.gif" /> <?php print $PMF_LANG['msgSending3Dots'] ?>');

             var msg = '';;

             $.get('index.php?action=ajax&ajax=trans&ajaxaction=send_translated_file',
                     {translang: lang},
                     function(retval, status) {
                         if (1*retval > 0 && 'success' == status) {
                             msg = '<?php print $PMF_LANG['msgTransToolFileSent'] ?>';
                         } else {
                             msg = '<?php print $PMF_LANG['msgTransToolErrorSendingFile'] ?>';
                         }
                   }
               );

             $('#saving_data_indicator').html('<?php print $PMF_LANG['msgTransToolFileSent'] ?>');
             alert('<?php print $PMF_LANG['msgTransToolFileSent'] ?>');
        }
        </script>