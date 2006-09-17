<?php
/**
 * $Id: cron.verifyurls.php,v 1.1 2006-09-17 20:13:04 matteo Exp $
 *
 * Performs an Automatic Link Verification over all the faq records
 *
 * You can set a cron entry:
 * a. using PHP CLI
 * b. using a Web Hit to this file
 *
 * @author      Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since       2006-09-17
 * @copyright   (c) 2006 phpMyFAQ Team
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

/**
 * This is the flag with which you define the language of this cron script
 *
 * @var const   en
 */
define('LANGCODE', 'en');


// Do not change anything below this line!
define('PMF_ROOT_DIR', dirname(__FILE__));
$output                     = '';
$isCronRequest              = false;
$isRequestedByCLI           = isset($_SERVER['argv']) && (isset($_SERVER['argv'][0]));
$isRequestedByWebLocalhost  = isset($_SERVER['REMOTE_ADDR']) && ('127.0.0.1' == $_SERVER['REMOTE_ADDR']);

$isCronRequest = $isRequestedByCLI || $isRequestedByWebLocalhost;

if ($isCronRequest && file_exists(PMF_ROOT_DIR.'/inc/data.php')) {
    // Hack: set dummy values for those entries evaluated during a Web request but not during a CLI request
    if ($isRequestedByCLI) {
        $_SERVER['HTTP_HOST'] = '';
        $_SERVER['HTTP_USER_AGENT'] = '';
    }
    require_once(PMF_ROOT_DIR.'/inc/Init.php');
    define('IS_VALID_PHPMYFAQ', null);
    PMF_Init::cleanRequest();

    // Preload English strings
    require_once(PMF_ROOT_DIR.'/lang/language_en.php');

    if ((LANGCODE != 'en') && PMF_Init::isASupportedLanguage(LANGCODE)) {
        // Overwrite English strings with the ones we have in the current language
        require_once(PMF_ROOT_DIR.'/lang/language_'.LANGCODE.'.php');
    }

    require_once(PMF_ROOT_DIR.'/inc/Linkverifier.php');
    $oLnk = new PMF_Linkverifier();
    $_records = array();

    // Read the data directly from the faqdata table (all faq records in all languages)
    $start = pmf_microtime_float();
    $output .= 'Extracting faq records...';
    $_result = $db->query('SELECT id, solution_id, revision_id, lang, thema, content FROM '.SQLPREFIX.'faqdata ORDER BY id');
    while ($row = $db->fetch_object($_result)) {
        $_records[] = array('id'            => $row->id,
                            'solution_id'   => $row->solution_id,
                            'revision_id'   => $row->revision_id,
                            'lang'          => $row->lang,
                            'title'         => $row->thema,
                            'content'       => $row->content
                            );
    }
    $tot = count($_records);
    $end = pmf_microtime_float();
    $output .= ' #'.$tot.', done in '.round($end - $start, 4).' sec'.($isRequestedByWebLocalhost ? '' : "\n");;
    if ($isRequestedByWebLocalhost) {
        $output = '<pre>'.$output."</pre>\n";
    }
    print($output);
    flush();

    $i = 0;
    foreach ($_records as $_r) {
        $i++;
        $output = '';
        $output .= sprintf('%0'.strlen((string)$tot).'d', $i).'/'.$tot.'. Checking '.$_r['solution_id'].' ('.strip_tags($_r['title']).')...';
        $start = pmf_microtime_float();
        $output .= verifyArticleURL($_r['content'], $_r['id'], $_r['lang'], true);
        $end = pmf_microtime_float();
        $output .= ' done in '.round($end - $start, 4).' sec';
        $output .= ($isRequestedByWebLocalhost ? '' : "\n");
        if ($isRequestedByWebLocalhost) {
            $output = '<pre>'.$output."</pre>\n";
        }
        print($output);
        flush();
    }
}
?>
