<?php
/**
 * List avaliable interface translations and actions
 * depending on user right.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-05-11
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

clearstatcache();
if (isset($_SESSION['trans'])) {
    unset($_SESSION['trans']);
}

$langDir = PMF_ROOT_DIR.DIRECTORY_SEPARATOR.'lang';
$transDir = new DirectoryIterator($langDir);
$isTransDirWritable = is_writable($langDir);
$tt = new PMF_TransTool();
?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-wrench fa-fw"></i> <?php echo $PMF_LANG['ad_menu_translations'] ?>
                    <?php if ($user->perm->checkRight($user->getUserId(), 'addtranslation') && $isTransDirWritable): ?>
                        <div class="pull-right">
                            <a class="btn btn-success" href="?action=transadd">
                                <i aria-hidden="true" class="fa fa-plus fa-fw"></i> <?php echo $PMF_LANG['msgTransToolAddNewTranslation'] ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
                <p><?php echo $PMF_LANG['msgChooseLanguageToTranslate'] ?>:</p>

                <?php if (!$isTransDirWritable):
                    echo '<p class="alert alert-danger">'.$PMF_LANG['msgLangDirIsntWritable'].'</p>';
                endif; ?>

                <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo $PMF_LANG['msgTransToolLanguage'] ?></th>
                        <th colspan="3"><?php echo $PMF_LANG['msgTransToolActions'] ?></th>
                        <th><?php echo $PMF_LANG['msgTransToolWritable'] ?></th>
                        <th><?php echo $PMF_LANG['msgTransToolPercent'] ?></th>
                    </tr>
                </thead>
                <tbody>
<?php
    $sortedLangList = [];

    foreach ($transDir as $file) {
        if ($file->isFile() && '.php' == PMF_String::substr($file, -4) && 'bak' != PMF_String::substr($file, -7, -4)) {
            $lang = str_replace(array('language_', '.php'), '', $file);

            /*
             * English is our exemplary language which won't be changed
             */
            if ('en' == $lang) {
                continue;
            }

            $sortedLangList[] = $lang;
        }
    }

    sort($sortedLangList);

    while (list(, $lang) = each($sortedLangList)) {
        $isLangFileWritable = is_writable($langDir.DIRECTORY_SEPARATOR."language_$lang.php");
        $showActions = $isTransDirWritable && $isLangFileWritable;
        $percents = $tt->getTranslatedPercentage(
            $langDir.DIRECTORY_SEPARATOR.'language_en.php',
            $langDir.DIRECTORY_SEPARATOR."language_$lang.php"
        );
        ?>
            <tr class="lang_<?php echo $lang ?>_container">
                <td><?php echo $languageCodes[strtoupper($lang)] ?></td>
                <?php if ($user->perm->checkRight($user->getUserId(), 'edittranslation') && $showActions): ?>
                <td>
                    <a class="btn btn-primary" href="?action=transedit&amp;translang=<?php echo $lang ?>" >
                        <i aria-hidden="true" class="fa fa-edit fa fa-white"></i>
                        <?php echo $PMF_LANG['msgEdit'] ?>
                    </a>
                </td>
                <?php else: ?>
                <td><?php echo $PMF_LANG['msgEdit'] ?></td>
                <?php endif; ?>
                <?php if ($user->perm->checkRight($user->getUserId(), 'deltranslation') && $showActions): ?>
                <td>
                    <a class="btn btn-danger" href="javascript: del('<?php echo $lang ?>');" >
                        <i aria-hidden="true" class="fa fa-remove fa fa-white"></i>
                        <?php echo $PMF_LANG['msgDelete'] ?>
                    </a>
                </td>
                <?php else: ?>
                <td><?php echo $PMF_LANG['msgDelete'] ?></td>
                <?php endif; ?>
                <?php if ($user->perm->checkRight($user->getUserId(), 'edittranslation') && $showActions): ?>
                <td>
                    <a class="btn btn-success" href="javascript: sendToTeam('<?php echo $lang ?>');" >
                        <i aria-hidden="true" class="fa fa-upload fa fa-white"></i>
                        <?php echo $PMF_LANG['msgTransToolSendToTeam'] ?>
                    </a>
                </td>
                <?php else: ?>
                <td><?php echo $PMF_LANG['msgTransToolSendToTeam'] ?></td>
                <?php endif; ?>
                <?php if ($isLangFileWritable): ?>
                <td><i aria-hidden="true" class="fa fa-ok-circle"></i> <?php echo $PMF_LANG['msgYes'] ?></td>
                <?php else: ?>
                <td><i aria-hidden="true" class="fa fa-ban-circle"></i> <?php echo $PMF_LANG['msgNo'] ?></td>
                <?php endif; ?>
                <td>
                    <?php echo $percents ?>%
                    <meter value="<?php echo $percents ?>" max="100" min="0" title="<?php echo $percents ?>%">
                </td>
            </tr>
        <?php 
    }
?>
                    </tbody>
                </table>
            </div>
        </div>
        <script>
        /**
         * Remove a language file
         *
         * @param lang
         *
         * @return void
         */
        function del(lang) {
            var $indicator = $('#saving_data_indicator');

            if (!confirm('<?php echo $PMF_LANG['msgTransToolSureDeleteFile'] ?>')) {
                return;
            }

            $indicator.html('<i aria-hidden="true" class="fa fa-spinner fa-spin"></i> <?php echo $PMF_LANG['msgRemoving3Dots'] ?>');

            $.get('index.php?action=ajax&ajax=trans&ajaxaction=remove_lang_file',
                {translang: lang},
                function (retval, status) {
                    if (1 * retval > 0 && 'success' === status) {
                        $('.lang_' + lang + '_container').fadeOut('slow');
                        $indicator.html('<?php echo $PMF_LANG['msgTransToolFileRemoved'] ?>');
                    } else {
                        $indicator.html('<?php echo $PMF_LANG['msgTransToolErrorRemovingFile'] ?>');
                        alert('<?php echo $PMF_LANG['msgTransToolErrorRemovingFile'] ?>');
                    }
                }
            );
        }

        /**
         * Send a translation file to the phpMyFAQ team
         *
         * @param lang
         *
         * @return void
         */
        function sendToTeam(lang) {
            var $indicator = $('#saving_data_indicator');

            $indicator.html('<i aria-hidden="true" class="fa fa-spinner fa-spin"></i> <?php echo $PMF_LANG['msgSending3Dots'] ?>');

            var msg = '';

            $.get('index.php?action=ajax&ajax=trans&ajaxaction=send_translated_file',
                {translang: lang},
                function (retval, status) {
                    if (1 * retval > 0 && 'success' === status) {
                        msg = '<?php echo $PMF_LANG['msgTransToolFileSent'] ?>';
                    } else {
                        msg = '<?php echo $PMF_LANG['msgTransToolErrorSendingFile'] ?>';
                    }
                }
            );

            $indicator.html('<?php echo $PMF_LANG['msgTransToolFileSent'] ?>');
            alert('<?php echo $PMF_LANG['msgTransToolFileSent'] ?>');
        }
        </script>