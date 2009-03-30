<?php
/**
 * Frontend for search log statistics
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2003-03-30
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
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

if ($permission['viewlog']) {
	
    $perpage = 15;
    $pages   = PMF_Filter::filterInput(INPUT_GET, 'pages', FILTER_VALIDATE_INT);
    $page    = PMF_Filter::filterInput(INPUT_GET, 'page' , FILTER_VALIDATE_INT, 1);
    
   	$search = new PMF_Search;
	$searchesList = $search->getMostPopularSearches(0, true);
    
    if (is_null($pages)) {
        $pages = round((count($searchesList) + ($perpage / 3)) / $perpage, 0);
    }
    
    $start = ($page - 1) * $perpage;
    $ende  = $start + $perpage;

    $PageSpan = PageSpan("<a href=\"?action=searchstats&amp;pages=".$pages."&amp;page=<NUM>\">", 1, $pages, $page);
?>
<table class="list">
<thead>
<tr>
	<th class="list"><?php print $PMF_LANG['ad_searchstats_search_term'] ?></th>
	<th class="list"><?php print $PMF_LANG['ad_searchstats_search_term_count'] ?></th>
	<th class="list"><?php print $PMF_LANG['ad_searchstats_search_term_lang'] ?></th>
</tr>
</thead>
   <tfoot>
       <tr>
           <td class="list" colspan="4"><?php print $PageSpan; ?></td>
       </tr>
   </tfoot>
<tbody>
<?php 

	$counter = $displayedCounter = 0;

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
?>
<tr>
	<td class="list"><?php print $searchItem['searchterm'] ?></td>
	<td class="list"><?php print $searchItem['number'] ?></td>
	<td class="list"><?php print $searchItem['lang'] ?></td>
</tr>
<?php
	}
?>
</tbody>
</table>
<?php 

} else {
    print $PMF_LANG["err_NotAuth"];
}