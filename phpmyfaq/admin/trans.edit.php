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
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
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

if (!$user->perm->checkRight($user->getUserId(), 'edittranslation')) {
    echo $PMF_LANG['err_NotAuth'];

    return;
}

$translateLang = PMF_Filter::filterInput(INPUT_GET, 'translang', FILTER_SANITIZE_STRING);

$page = PMF_Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = 1 > $page ? 1 : $page;

if (empty($translateLang) || !file_exists(PMF_ROOT_DIR."/lang/language_$translateLang.php")) {
    header('Location: ?action=translist');
}

$tt = new PMF_TransTool();

/*
 * There are meanwhile over 600 language
 * vars and we won't to show them all
 * at once, so let's paginate.
 */
$itemsPerPage = 32;
if (!isset($_SESSION['trans'])) {
    /*
     * English is our exemplary language
     */
    $_SESSION['trans']['leftVarsOnly'] = $tt->getVars(PMF_ROOT_DIR.'/lang/language_en.php');
    $_SESSION['trans']['rightVarsOnly'] = $tt->getVars(PMF_ROOT_DIR."/lang/language_$translateLang.php");
}

$leftVarsOnly = array_slice($_SESSION['trans']['leftVarsOnly'],
                              ($page - 1) * $itemsPerPage,
                              $itemsPerPage);
$rightVarsOnly = &$_SESSION['trans']['rightVarsOnly'];

$options = array(
    'baseUrl' => PMF_Link::getSystemRelativeUri('index.php').'?'.str_replace('&', '&amp;', $_SERVER['QUERY_STRING']),
    'total' => count($_SESSION['trans']['leftVarsOnly']),
    'perPage' => $itemsPerPage,
);

$pagination = new PMF_Pagination($faqConfig, $options);
$pageBar = $pagination->render();

/*
 * These keys always exist as they are defined when creating translation.
 * We use these values to add the correct number of input boxes.
 * Left column will always have 2 boxes, right - 1 to 6+ boxes.
 */
$leftNPlurals = (int) $_SESSION['trans']['leftVarsOnly']['PMF_LANG[nplurals]'];
$rightNPlurals = (int) $rightVarsOnly['PMF_LANG[nplurals]'];

printf(
    '<header class="row"><div class="col-lg-12"><h2 class="page-header"><i aria-hidden="true" class="fa fa-wrench"></i> %s</h2></div></header>',
    $PMF_LANG['ad_menu_translations']
);

$NPluralsErrorReported = false;
?>
        <div class="row">
            <div class="col-lg-12">
                <p class="alert alert-info">
                    <?php echo $PMF_LANG['msgTransToolNoteFileSaving'] ?>
                </p>
                <form id="transDiffForm" accept-charset="utf-8">
                <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>">
                <table class="table table-hover">
                <tr>
                    <th><?php echo $PMF_LANG['msgVariable'] ?></th>
                    <th>en</th>
                    <th><?php echo $translateLang ?></th>
                </tr>
        <?php while (list($key, $line) = each($leftVarsOnly)): ?>

        <?php
    // These parameters are not real translations, so don't offer to translate them
    if ($tt->isKeyIgnorable($key)) {
        echo "<tr>\n";
        echo '<td>'.$key."</td>\n";
        echo '<td><input class="form-control" type="text" value="'.PMF_String::htmlspecialchars($line).'" disabled="disabled" /></td>'."\n";
        echo '<td><input class="form-control" type="text" name="'.$key.'" value="'.PMF_String::htmlspecialchars($rightVarsOnly[$key]).'" disabled="disabled" />';
        echo '<input type="hidden" name="'.$key.'" value="'.PMF_String::htmlspecialchars($rightVarsOnly[$key]).'" /></td>'."\n";
        echo "</tr>\n";
        continue;
    }

    /*
     *  Plural form support in translation interface
     */

    // We deal with the second plural form when dealing with the first, so skip it here
    if ($tt->isKeyASecondPluralForm($key)) {
        continue;
    }

    if ($tt->isKeyAFirstPluralForm($key)) {
        if ($rightNPlurals == -1) {
            // Report missing plural form support once.
            if (!$NPluralsErrorReported) {
                echo "<tr>\n";
                echo '<td class="text-center" colspan="3">'.sprintf($PMF_LANG['msgTransToolLanguagePluralNotSet'], $translateLang)."</td>\n";
                echo "</tr>\n";
                $NPluralsErrorReported = true;
            }
            continue;
        }
        /*
         * We echo one box for English and one for other language
         * because other language will always have at least 1 form
         */
        echo "<tr>\n";
        echo '<td>'.$key."</td>\n";
        echo '<td><input class="form-control" type="text" value="'.PMF_String::htmlspecialchars($line).'" disabled="disabled" /></td>'."\n";
        if (array_key_exists($key, $rightVarsOnly) && ($line != $rightVarsOnly[$key] ||
           $tt->isKeyIgnorable($key) || $tt->isValIgnorable($line))) {
            echo '<td><input class="form-control" type="text" name="'.$key.'" value="'.PMF_String::htmlspecialchars($rightVarsOnly[$key]).'" /></td>'."\n";
        } else {
            echo '<td><input style="width: 300px;border-color: red;" type="text" name="'.$key.'" value="'.PMF_String::htmlspecialchars($line).'" /></td>'."\n";
        }
        echo "</tr>\n";

        // Add second English form and translation
        $key2 = str_replace('[0]', '[1]', $key);
        echo "<tr>\n";
        echo '<td>'.$key2."</td>\n";
        echo '<td><input class="form-control" type="text" value="'.PMF_String::htmlspecialchars($leftVarsOnly[$key2]).'" disabled="disabled" /></td>'."\n";
        if ($rightNPlurals == 1) {
            // Other language has only one form
            echo '<td><input class="form-control" type="text" value="'.$PMF_LANG['msgTransToolLanguageOnePlural'].'" disabled="disabled" /></td>'."\n";
        } else {
            if (array_key_exists($key2, $rightVarsOnly)) {
                echo '<td><input class="form-control" type="text" name="'.$key2.'" value="'.PMF_String::htmlspecialchars($rightVarsOnly[$key2]).'" /></td>'."\n";
            } else {
                echo '<td><input class="form-control alert-danger" type="text" name="'.$key2.'" value="'.PMF_String::htmlspecialchars($leftVarsOnly[$key2]).'" /></td>'."\n";
            }
        }
        echo "</tr>\n";

        // Other language has more than 2 forms
        for ($i = 2; $i < $rightNPlurals; ++$i) {
            $keyI = str_replace('[0]', "[$i]", $key);
            echo "<tr>\n";
            echo '<td>'.$keyI."</td>\n";
            echo '<td><input class="form-control" type="text" value="" disabled="disabled" /></td>'."\n";
            if (array_key_exists($keyI, $rightVarsOnly) && $leftVarsOnly[$key2] != $rightVarsOnly[$key]) {
                echo '<td><input class="form-control" type="text" name="'.$keyI.'" value="'.PMF_String::htmlspecialchars($rightVarsOnly[$keyI]).'" /></td>'."\n";
            } else {
                echo '<td><input class="form-control alert-danger" type="text" name="'.$keyI.'" value="'.PMF_String::htmlspecialchars($leftVarsOnly[$key2]).'" /></td>'."\n";
            }
            echo "</tr>\n";
        }
        // We do not need to process this $key any further
        continue;
    }
?>
                <tr>
                    <td><?php echo $key?></td>
                    <td><input class="form-control" type="text" value="<?php echo PMF_String::htmlspecialchars($line) ?>" disabled="disabled" /></td>
                    <?php
                        if (array_key_exists($key, $rightVarsOnly) && ($line != $rightVarsOnly[$key] ||
                           $tt->isKeyIgnorable($key) || $tt->isValIgnorable($line))):
                    ?>
                    <td><input class="form-control" type="text" name="<?php echo $key?>" value="<?php echo PMF_String::htmlspecialchars($rightVarsOnly[$key]) ?>" /></td>
                    <?php else: ?>
                    <td><input class="form-control alert-danger" type="text" name="<?php echo $key?>" value="<?php echo PMF_String::htmlspecialchars($line) ?>" /></td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="3"><?php echo $pageBar; ?></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td colspan="2">
                        <button class="btn btn-inverse" type="button" onclick="location.href='?action=translist'">
                            <?php echo $PMF_LANG['msgCancel'] ?>
                        </button>
                        <button class="btn btn-success" type="button"
                                onclick="save()"<?php if (!is_writable(PMF_ROOT_DIR."/lang/language_$translateLang.php")) {
    echo ' disabled="disabled"';
} ?>>
                            <?php echo $PMF_LANG['msgSave'] ?>
                        </button>
                    </td>
                </tr>
                </table>
                </form>

            </div>
        </div>
        <script>

        /**
         * Gather data from the current form
         *
         * @return object
         */
        function getFormData()
        {
            var data = {};
            var form = document.getElementById('transDiffForm');
            for (var i=0; i < form.elements.length;i++) {
                var element = form.elements[i]
                if (('text' == element.type || 'hidden' == element.type) && !element.disabled) {
                    data[element.name] = element.value
                }
            }

            return data;
        }

        /**
         * Go to some page
         *
         * @return void
         */
        function go(url)
        {
            if(savePageBuffer()) {
                document.location = url;
            }
        }

        /**
         * Send page buffer to save it into the session
         *
         * @return boolean
         */
        function savePageBuffer()
        {
            var result = false;

            $('#saving_data_indicator').html('<i aria-hidden="true" class="fa fa-spinner fa-spin"></i> <?php printf($PMF_LANG['msgTransToolRecordingPageBuffer'], $page); ?>');

            $.ajax({url: 'index.php?action=ajax&ajax=trans&ajaxaction=save_page_buffer',
                   data: getFormData(),
                   async: false,
                   type: 'POST',
                   success: function (retval, status) {
                        result = 1*retval > 0 && 'success' == status;
                        if (result) {
                            $('#saving_data_indicator').html('<?php printf($PMF_LANG['msgTransToolPageBufferRecorded'], $page); ?>');
                        } else {
                            $('#saving_data_indicator').html('<?php printf($PMF_LANG['msgTransToolErrorRecordingPageBuffer'], $page); ?>');
                        }
                   },
                   error: function() {
                       $('#saving_data_indicator').html('<?php printf($PMF_LANG['msgTransToolErrorRecordingPageBuffer'], $page); ?>');
                   }
            });

            return result;
        }


        /**
         * Transparently save the translation form
         * @return void
         */
        function save()
        {
            $('#saving_data_indicator').html(
                '<i aria-hidden="true" class="fa fa-spinner fa-spin"></i> <?php echo $PMF_LANG['msgSaving3Dots'] ?>'
            );

            if (savePageBuffer()) {
                $.post('index.php?action=ajax&ajax=trans&ajaxaction=save_translated_lang&csrf=<?php echo $user->getCsrfTokenFromSession() ?>',
                        null,
                        function (retval, status) {
                            if (1*retval > 0 && 'success' == status) {
                                $('#saving_data_indicator').html('<?php echo $PMF_LANG['msgTransToolFileSaved'] ?>');
                                document.location = '?action=translist'
                            } else {
                                $('#saving_data_indicator').html('<?php echo $PMF_LANG['msgTransToolErrorSavingFile'] ?>');
                            }
                        }
                )
            } else {
                 $('#saving_data_indicator').html('<?php echo $PMF_LANG['msgTransToolErrorSavingFile'] ?>');
            }
        }
        </script>
