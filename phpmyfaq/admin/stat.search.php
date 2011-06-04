<?php
/**
 * Frontend for search log statistics
 *
 * PHP Version 5.2
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
 * 
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-03-30
 */

if (isset($_GET['num']) && !defined('PMF_ROOT_DIR')) {
    define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
    
    require_once PMF_ROOT_DIR . '/inc/Init.php';
    PMF_Init::cleanRequest();
    session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
    session_start();
    
    $num = PMF_Filter::filterInput(INPUT_GET, 'num', FILTER_VALIDATE_FLOAT);
    if (!is_null($num)) {
        $bar = new PMF_Bar($num);
        $bar->renderImage();
        exit;
    }
}

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['viewlog']) {

    printf('<header><h2>%s</h2></header>', $PMF_LANG['ad_menu_searchstats']);

    $perpage = 15;
    $pages   = PMF_Filter::filterInput(INPUT_GET, 'pages', FILTER_VALIDATE_INT);
    $page    = PMF_Filter::filterInput(INPUT_GET, 'page' , FILTER_VALIDATE_INT, 1);
    
    $search        = new PMF_Search($db, $Language);
    $searchesCount = $search->getSearchesCount();
    $searchesList  = $search->getMostPopularSearches($searchesCount + 1, true);
    
    if (is_null($pages)) {
        $pages = round((count($searchesList) + ($perpage / 3)) / $perpage, 0);
    }
    
    $start = ($page - 1) * $perpage;
    $ende  = $start + $perpage;

    $PageSpan = PageSpan("<a href=\"?action=searchstats&amp;pages=".$pages."&amp;page=<NUM>\">", 1, $pages, $page);
?>
        <table class="list" style="width: 100%">
        <thead>
        <tr>
            <th><?php print $PMF_LANG['ad_searchstats_search_term'] ?></th>
            <th><?php print $PMF_LANG['ad_searchstats_search_term_count'] ?></th>
            <th><?php print $PMF_LANG['ad_searchstats_search_term_lang'] ?></th>
            <th><?php print $PMF_LANG['ad_searchstats_search_term_percentage'] ?></th>
        </tr>
        </thead>
        <tfoot>
            <tr>
                <td colspan="4"><?php print $PageSpan; ?></td>
            </tr>
        </tfoot>
        <tbody>
<?php 

    $counter = $displayedCounter = 0;
    $self    = substr(__FILE__, strlen($_SERVER['DOCUMENT_ROOT']));

    foreach($searchesList as $searchItem) {

        if ($displayedCounter >= $perpage) {
            $displayedCounter++;
            continue;
        }

        $counter++;
        if ($counter <= $start) {
            continue;
        }
        $displayedCounter++;
        
        $num = round(($searchItem['number']*100 / $searchesCount), 2);
?>
        <tr>
            <td><?php print PMF_String::htmlspecialchars($searchItem['searchterm']);  ?></td>
            <td><?php print $searchItem['number'] ?></td>
            <td><?php print $languageCodes[PMF_String::strtoupper($searchItem['lang'])] ?></td>
            <td><?php print $num ?> %</td>
        </tr>
<?php
	}
?>
        </tbody>
        </table>
<?php 

} else {
    print $PMF_LANG['err_NotAuth'];
}