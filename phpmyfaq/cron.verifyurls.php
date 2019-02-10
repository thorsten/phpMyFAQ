<?php

/**
 * Performs an Automatic Link Verification over all the faq records.
 *
 * You can set a cron entry:
 * a. using PHP CLI
 * b. using a Web Hit to this file
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2006-09-17
 */

/**
 * This is the flag with which you define the language of this cron script.
 *
 * @var const en
 */
define('LANGCODE', 'en');

// Do not change anything below this line!
define('PMF_ROOT_DIR', __DIR__);

$output = '';
$isCronRequest = false;
$isRequestedByCLI = isset($_SERVER['argv']) && (isset($_SERVER['argv'][0]));
$isRequestedByWebLocalhost = isset($_SERVER['REMOTE_ADDR']) && ('127.0.0.1' == $_SERVER['REMOTE_ADDR']);

$isCronRequest = $isRequestedByCLI || $isRequestedByWebLocalhost;

if ($isCronRequest && file_exists(PMF_ROOT_DIR.'/config/database.php')) {
    // Hack: set dummy values for those entries evaluated during a Web request but not during a CLI request
    if ($isRequestedByCLI) {
        $_SERVER['HTTP_HOST'] = '';
        $_SERVER['HTTP_USER_AGENT'] = '';
    }

    define('IS_VALID_PHPMYFAQ', null);

    require PMF_ROOT_DIR.'/inc/Bootstrap.php';

    // Preload English strings
    require_once PMF_ROOT_DIR.'/lang/language_en.php';

    if ((LANGCODE != 'en') && PMF_Language::isASupportedLanguage(LANGCODE)) {
        // Overwrite English strings with the ones we have in the current language
        require_once PMF_ROOT_DIR.'/lang/language_'.LANGCODE.'.php';
    }

    //Load plurals support for selected language
    $plr = new PMF_Language_Plurals($PMF_LANG);

    //
    // Initalizing static string wrapper
    //
    PMF_String::init(LANGCODE);

    $oLnk = new PMF_Linkverifier($faqConfig);
    $faq = new PMF_Faq($faqConfig);
    $totStart = microtime(true);

    // Read the data directly from the faqdata table (all faq records in all languages)
    $start = microtime(true);
    $output .= ($isRequestedByWebLocalhost ? '' : "\n");
    $output .= 'Extracting faq records...';

    $faq->getAllRecords();
    $_records = $faq->faqRecords;
    $tot = count($_records);
    $end = microtime(true);
    $output  .= ' #'.$tot.', done in '.round($end - $start, 4).' sec.'.($isRequestedByWebLocalhost ? '' : "\n");
    $output  .= ($isRequestedByWebLocalhost ? '' : "\n");
    if ($isRequestedByWebLocalhost) {
        echo '<pre>';
    }
    $output = $output."\n";
    echo $output;

    $i = 0;
    foreach ($_records as $_r) {
        ++$i;
        $output = '';
        $output .= sprintf('%0'.strlen((string) $tot).'d', $i).'/'.$tot.'. Checking '.$_r['solution_id'].' ('.PMF_Utils::makeShorterText(strip_tags($_r['title']), 8).'):';
        $start = microtime(true);
        if ($oLnk->getEntryState($_r['id'], $_r['lang'], true) === true) {
            $output .= $oLnk->verifyArticleURL($_r['content'], $_r['id'], $_r['lang'], true);
        }
        $end = microtime(true);
        $output .= ' done in '.round($end - $start, 4).' sec.';
        $output .= ($isRequestedByWebLocalhost ? '' : "\n");
        if ($isRequestedByWebLocalhost) {
            $output = $output."\n";
        }
        echo $output;
    }

    $output = '';
    $totEnd = microtime(true);
    $output .= ($isRequestedByWebLocalhost ? '' : "\n");
    $output .= 'Done in '.round($totEnd - $totStart, 4).' sec.';
    $output .= ($isRequestedByWebLocalhost ? '' : "\n");
    if ($isRequestedByWebLocalhost) {
        $output = $output."\n";
    }
    echo $output;

    if ($isRequestedByWebLocalhost) {
        echo '</pre>';
    }
}

//
// Disconnect from database
//
$faqConfig->getDb()->close();
