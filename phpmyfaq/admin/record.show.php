<?php
/**
* $Id: record.show.php,v 1.26 2006-06-29 20:52:47 matteo Exp $
*
* Shows the list of records ordered by categories
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Minoru TODA <todam@netjapan.co.jp>
* @since        2003-02-23
* @copyright    (c) 2001-2006 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";
if ($permission["editbt"] || $permission["delbt"]) {
    $tree = new PMF_Category();
    $tree->transform(0);
    $tree->buildTree();

    $linkverifier = new PMF_Linkverifier();
    if ($linkverifier->isReady()) {
        link_verifier_javascript();
    }

    $cond = array();
    $internalSearch = '';

    if (isset($_REQUEST["searchcat"]) && is_numeric($_REQUEST["searchcat"]) && $_REQUEST["searchcat"] != "0" ) {
        $searchcat = safeSQL($_REQUEST["searchcat"]);
        $internalSearch .= "&amp;searchcat=".$searchcat;
        $cond[SQLPREFIX."faqcategoryrelations.category_id"] = array_merge(array($searchcat),$tree->getChildNodes($searchcat));
    } else {
        $searchcat = 0;
    }

    if (isset($_REQUEST["linkstate"])) {
        $cond[SQLPREFIX."faqdata.linkState"] = "linkbad";
        $linkState = " checked ";
        $internalSearch .= "&amp;linkstate=linkbad";
    } else {
        $linkState = "";
    }

    if (isset($_REQUEST["suchbegriff"])) {
        $begriff = safeSQL($_REQUEST["suchbegriff"]);
    } else {
        $begriff = "";
    }

    if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "view" && $begriff == "") {
        $where = "";
        foreach ($cond as $field => $data) {
            $where .= " AND ".$field;
            if (is_array($data)) {
                $where .= " IN (";
                $separator = "";
                foreach ($data as $value) {
                    $where .= $separator."'".$db->escape_string($value)."'";
                    $separator = ", ";
                }
                $where .= ")";
            } else {
                $where .= " = '".$db->escape_string($data)."'";
            }
        }

        $query = 'SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.thema,'.SQLPREFIX.'faqdata.author FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang ='.SQLPREFIX.'faqcategoryrelations.record_lang WHERE '.SQLPREFIX.'faqdata.active = \'yes\' '.$where.' ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.id ';

        $result = $db->query($query);
        $laktion = 'view';
        $internalSearch .= '';

        $resultComments = $db->query("SELECT count(id) as anz, id FROM ".SQLPREFIX."faqcomments GROUP BY id ORDER BY id;");
        if ($db->num_rows($resultComments) > 0) {

            while ($row = $db->fetch_object($resultComments)) {

                $numComments[$row->id] = $row->anz;
            }
        }
    } else if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "view" && isset($_REQUEST["suchbegriff"]) && $_REQUEST["suchbegriff"] != "") {
        // Search for:
        // a. solution id
        // b. full text search
        // TODO: Decide if the search will be performed upon all entries or upon the active ones.
        $begriff = strip_tags($_REQUEST["suchbegriff"]);
        if (is_numeric($begriff)) {
            // a. solution id
            $result = $db->search(SQLPREFIX.'faqdata',
                        array(SQLPREFIX.'faqdata.id AS id',
                            SQLPREFIX.'faqdata.lang AS lang',
                            SQLPREFIX.'faqcategoryrelations.category_id AS category_id',
                            SQLPREFIX.'faqdata.thema AS thema',
                            SQLPREFIX.'faqdata.content AS content'),
                        SQLPREFIX.'faqcategoryrelations',
                        array(SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id',
                            SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang'),
                        array(SQLPREFIX.'faqdata.solution_id'),
                        $begriff);
        } else {
            // b. full text search
            $result = $db->search(SQLPREFIX."faqdata",
                        array(SQLPREFIX.'faqdata.id AS id',
                            SQLPREFIX.'faqdata.lang AS lang',
                            SQLPREFIX.'faqcategoryrelations.category_id AS category_id',
                            SQLPREFIX.'faqdata.thema AS thema',
                            SQLPREFIX.'faqdata.content AS content'),
                        SQLPREFIX.'faqcategoryrelations',
                        array(SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id',
                            SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang'),
                        array(SQLPREFIX.'faqdata.thema',
                            SQLPREFIX.'faqdata.content',
                            SQLPREFIX.'faqdata.keywords'),
                        $begriff,
                        array(),
                        array(SQLPREFIX.'faqcategoryrelations.category_id',  SQLPREFIX.'faqdata.id')
                        );
        }

        $laktion = "view";
        $internalSearch = "&amp;search=".$begriff;

    } elseif (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "accept") {
        $query = 'SELECT '.SQLPREFIX.'faqdata.id AS id,'.SQLPREFIX.'faqdata.lang AS lang, '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id, '.SQLPREFIX.'faqdata.thema AS thema,'.SQLPREFIX.'faqdata.author AS author FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang ='.SQLPREFIX.'faqcategoryrelations.record_lang WHERE '.SQLPREFIX.'faqdata.active = \'no\' ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.id';
        $result = $db->query($query);

        $laktion = "accept";
        $internalSearch = "";
    }
    
    $perpage = 20;
    if (!isset($_REQUEST["pages"])) {
        $anz = $db->num_rows($result);
        $pages = ceil($anz / $perpage);
        if ($pages < 1) {
            $pages = 1;
        }
    } else {
        $pages = $_REQUEST["pages"];
    }

    if (!isset($_REQUEST["page"])) {
        $page = 1;
    } else {
        $page = $_REQUEST["page"];
    }

    $start = ($page - 1) * $perpage;

    $PageSpan = PageSpan('<a href="?aktion='.$laktion.'&amp;pages='.$pages.'&amp;page=<NUM>'.$internalSearch.'">', 1, $pages, $page);

    $old = 0;
    $previousID = 0;

    if ($db->num_rows($result) > 0) {
        if ($laktion == "view") {
?>
    <form action="?aktion=view" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["msgSearch"]; ?></legend>
        <table class="admin">
        <tr>
            <td><strong><?php print $PMF_LANG["msgSearchWord"]; ?>:</strong></td>
            <td><input class="admin" type="text" name="suchbegriff" size="50" value="<?php print $begriff; ?>"></td>
            <td>
            <?php if ($linkverifier->isReady() == TRUE) { ?>
            <input class="admin" type="checkbox" name="linkstate" value="linkbad" <?php print $linkState; ?>><?php print $PMF_LANG['ad_linkcheck_searchbadonly']; ?>
            <?php } ?>
            </td>
        </tr>
        <tr>
            <td><strong><?php print $PMF_LANG["msgCategory"]; ?>:</strong></td>
            <td><select class="admin" name="searchcat">
            <option value="0"><?php print $PMF_LANG["msgShowAllCategories"]; ?></option>
            <?php print $tree->printCategoryOptions($searchcat); ?>
            </select></td>
            <td><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["msgSearch"]; ?>"></td>
        </tr>
        </table>
    </fieldset>
    </form>
<?php
        }
        $counter = 0;
        $displayedCounter = 0;
        while (($row = $db->fetch_object($result)) && $displayedCounter < $perpage) {
            $counter ++;
            if ($counter <= $start) {
                continue;
            }
            $displayedCounter++;
            if ($row->category_id != $old) {
                if ($old == 0) {
?>
    <table class="list">
<?php
                } else {
?>
	</table>
	<br />
    <table class="list">
<?php
                }
?>
    <thead>
        <tr>
            <th colspan="5" class="list"><?php print $tree->getPath($row->category_id); ?></th>
        </tr>
    </thead>
    <tfoot>
        <tr>
		    <td colspan="5" class="list"><?php print $PageSpan; ?></td>
        </tr>
    </tfoot>
    <tbody>
<?php
            }
?>
        <tr>
            <td class="list" width="35"><?php print $row->id; ?></td>
            <td class="list" width="18"><?php print $row->lang; ?></td>
            <td class="list" width="18"><a href="?aktion=saveentry&amp;id=<?php print $row->id; ?>&amp;language=<?php print $row->lang; ?>&amp;submit[0]=<?php print $PMF_LANG["ad_entry_delete"]; ?>" title="<?php print $PMF_LANG["ad_user_delete"]; ?> '<?php print str_replace("\"", "´", stripslashes($row->thema)); ?>'"><img src="images/delete.gif" width="17" height="18" alt="<?php print $PMF_LANG["ad_entry_delete"]; ?>" /></a></td>
            <td class="list" width="50"><?php print $linkverifier->getEntryStateHTML($row->id, $row->lang); ?></td>
            <td class="list"><a href="?aktion=editentry&amp;id=<?php print $row->id; ?>&amp;lang=<?php print $row->lang; ?>" title="<?php print $PMF_LANG["ad_user_edit"]; ?> '<?php print str_replace("\"", "´", stripslashes($row->thema)); ?>'"><?php print stripslashes($row->thema); ?></a><?php
            if (isset($numComments[$row->id])) {
                print " (".$numComments[$row->id]." ".$PMF_LANG["ad_start_comments"].")";
            }
?></td>
        </tr>
<?php
            $previousID = $row->id;
            $old = $row->category_id;
        }
?>
    </tbody>
	</table>
	<p align="right"><strong>[ <a href="#top"><?php print $PMF_LANG["ad_gen_top"]; ?></a> ]</strong></p>
<?php
    } else {
        print "n/a";
    }
} else {
    print $PMF_LANG["err_NotAuth"];
}
