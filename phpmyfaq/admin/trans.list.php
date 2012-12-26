<?php
/**
 * List avaliable interface translations and actions
 * depending on user right
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2013 phpMyFAQ Team
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
        <p><?php echo $PMF_LANG['msgChooseLanguageToTranslate'] ?>:</p>

        <?php if(!$isTransDirWritable):
            echo '<p class="alert alert-error">'. $PMF_LANG['msgLangDirIsntWritable'] . "</p>";
        endif; ?>

        <table class="table table-striped">
        <thead>
            <?php if($permission["addtranslation"] && $isTransDirWritable): ?>
            <tr>
                <th colspan="6">
                    <a class="btn btn-primary" href="?action=transadd">
                        <?php echo $PMF_LANG['msgTransToolAddNewTranslation'] ?>
                    </a>
                </th>
            </tr>
            <?php endif; ?>
            <tr>
                <th><?php echo $PMF_LANG['msgTransToolLanguage'] ?></th>
                <th colspan="3"><?php echo $PMF_LANG['msgTransToolActions'] ?></th>
                <th><?php echo $PMF_LANG['msgTransToolWritable'] ?></th>
                <th><?php echo $PMF_LANG['msgTransToolPercent'] ?></th>
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
            <tr class="lang_<?php echo $lang ?>_container">
                <td><?php echo $languageCodes[strtoupper($lang)] ?></td>
                <?php if($permission["edittranslation"] && $showActions): ?>
                <td>
                    <a class="btn btn-primary" href="?action=transedit&amp;translang=<?php echo $lang ?>" >
                        <i class="icon-edit icon-white"></i>
                        <?php echo $PMF_LANG['msgEdit'] ?>
                    </a>
                </td>
                <?php else: ?>
                <td><?php echo $PMF_LANG['msgEdit'] ?></td>
                <?php endif; ?>
                <?php if($permission["deltranslation"] && $showActions): ?>
                <td>
                    <a class="btn btn-danger" href="javascript: del('<?php echo $lang ?>');" >
                        <i class="icon-remove icon-white"></i>
                        <?php echo $PMF_LANG['msgDelete'] ?>
                    </a>
                </td>
                <?php else: ?>
                <td><?php echo $PMF_LANG['msgDelete'] ?></td>
                <?php endif; ?>
                <?php if($permission["edittranslation"] && $showActions): ?>
                <td>
                    <a class="btn btn-success" href="javascript: sendToTeam('<?php echo $lang ?>');" >
                        <i class="icon-upload icon-white"></i>
                        <?php echo $PMF_LANG['msgTransToolSendToTeam'] ?>
                    </a>
                </td>
                <?php else: ?>
                <td><?php echo $PMF_LANG['msgTransToolSendToTeam'] ?></td>
                <?php endif;?>
                <?php if($isLangFileWritable): ?>
                <td><i class="icon-ok-circle"></i> <?php echo $PMF_LANG['msgYes'] ?></td>
                <?php else: ?>
                <td><i class="icon-ban-circle"></i> <?php echo $PMF_LANG['msgNo'] ?></td>
                <?php endif; ?>
                <td><?php echo $tt->getTranslatedPercentage(
                    $langDir . DIRECTORY_SEPARATOR . "language_en.php",
                    $langDir . DIRECTORY_SEPARATOR . "language_$lang.php"
                ); ?>%</td>
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
            if (!confirm('<?php echo $PMF_LANG['msgTransToolSureDeleteFile'] ?>')) {
                return;
            }

            $('#saving_data_indicator').html('<img src="images/indicator.gif" /> <?php echo $PMF_LANG['msgRemoving3Dots'] ?>');

            $.get('index.php?action=ajax&ajax=trans&ajaxaction=remove_lang_file',
                  {translang: lang},
                  function(retval, status) {
                      if (1*retval > 0 && 'success' == status) {
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
                         if (1*retval > 0 && 'success' == status) {
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