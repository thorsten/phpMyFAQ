<?php
/**
* $Id: record.show.php,v 1.37 2007-02-11 13:59:26 thorstenr Exp $
*
* Shows the list of records ordered by categories
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Minoru TODA <todam@netjapan.co.jp>
* @since        2003-02-23
* @copyright    (c) 2001-2007 phpMyFAQ Team
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

printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);

if ($permission["editbt"] || $permission["delbt"]) {
    // (re)evaluate the Category object w/o passing the user language
    $tree = new PMF_Category();
    $tree->transform(0);
    $tree->buildTree();

    $linkverifier = new PMF_Linkverifier($db, $user->getLogin());
    if ($linkverifier->isReady()) {
        link_verifier_javascript();
    }

    $cond = array();
    $internalSearch = '';

    if (isset($_REQUEST["linkstate"])) {
        $cond[SQLPREFIX."faqdata.links_state"] = "linkbad";
        $linkState = " checked ";
        $internalSearch .= "&amp;linkstate=linkbad";
    } else {
        $linkState = "";
    }

    if (isset($_REQUEST["searchcat"]) && is_numeric($_REQUEST["searchcat"]) && $_REQUEST["searchcat"] != "0" ) {
        $searchcat = safeSQL($_REQUEST["searchcat"]);
        $internalSearch .= "&amp;searchcat=".$searchcat;
        $cond[SQLPREFIX."faqcategoryrelations.category_id"] = array_merge(array($searchcat),$tree->getChildNodes($searchcat));
    } else {
        $searchcat = 0;
    }
    
    if (isset($_REQUEST["suchbegriff"])) {
        $begriff = safeSQL($_REQUEST["suchbegriff"]);
    } else {
        $begriff = "";
    }
?>
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;action=view" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["msgSearch"]; ?></legend>
        <table class="admin">
        <tr>
            <td><strong><?php print $PMF_LANG["msgSearchWord"]; ?>:</strong></td>
            <td><input class="admin" type="text" name="suchbegriff" size="50" value="<?php print $begriff; ?>"></td>
            <td>
            <?php if ($linkverifier->isReady() == true) { ?>
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

    <fieldset>
    <legend><?php print ((isset($_REQUEST['action']) && 'accept' == $_REQUEST['action']) ? $PMF_LANG['ad_menu_entry_aprove'] : $PMF_LANG['ad_menu_entry_edit']); ?></legend>
<?php
    // 1. Count the comments for each faq
    $resultComments = $db->query("SELECT count(id) as anz, id FROM ".SQLPREFIX."faqcomments GROUP BY id ORDER BY id;");
    if ($db->num_rows($resultComments) > 0) {
        while ($row = $db->fetch_object($resultComments)) {
            $numCommentsByFaq[$row->id] = $row->anz;
        }
    }

    // FIXME: Count "comments"/"entries" for each category also within a search context. Now the count is broken.
    // FIXME: we are not considering 'faqdata.links_state' for filtering the faqs.
    if (!(isset($_REQUEST["suchbegriff"]) && $_REQUEST["suchbegriff"] != "")) {
        $matrix = array();
        // 2. Count the comments for each category
        // 2.1 Create a matrix for representing categories and faq records
        $query  = 'SELECT '.SQLPREFIX.'faqcategoryrelations.category_id AS id_cat, '.SQLPREFIX.'faqdata.id AS id'
             .' FROM '.SQLPREFIX.'faqdata'
             .' INNER JOIN '.SQLPREFIX.'faqcategoryrelations'
             .' ON '.SQLPREFIX.'faqdata.id='.SQLPREFIX.'faqcategoryrelations.record_id'
             .' AND '.SQLPREFIX.'faqdata.lang='.SQLPREFIX.'faqcategoryrelations.category_lang'
             .' ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.id';
        $resultFaq = $db->query($query);
        if ($db->num_rows($resultFaq) > 0) {
            while ($row = $db->fetch_object($resultFaq)) {
                $matrix[$row->id_cat][$row->id] = true;
            }
        }

        // 2.2 Evaluate the comments for each category using the matrix above
        foreach ($matrix as $catkey => $value) {
            $numCommentsByCat[$catkey] = 0;
            foreach ($value as $faqkey => $value) {
                if (isset($numCommentsByFaq[$faqkey])) {
                    $numCommentsByCat[$catkey] += $numCommentsByFaq[$faqkey];
                }
            }
        }

        // 3. Count the entries
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'accept') {
            $active = 'no';
        } else {
            $active = 'yes';
        }

        $query = "SELECT ".SQLPREFIX."faqcategoryrelations.category_id AS category_id, count(".SQLPREFIX."faqcategoryrelations.record_id) AS number"
            ." FROM ".SQLPREFIX."faqcategoryrelations, ".SQLPREFIX."faqdata"
            ." WHERE ".SQLPREFIX."faqcategoryrelations.record_id = ".SQLPREFIX."faqdata.id"
            ." AND ".SQLPREFIX."faqcategoryrelations.record_lang = ".SQLPREFIX."faqdata.lang"
            ." AND ".SQLPREFIX."faqdata.active = '".$active."'"
            ." GROUP BY ".SQLPREFIX."faqcategoryrelations.category_id";
        
        $result = $db->query($query);
        if ($db->num_rows($result) > 0) {
            while ($row = $db->fetch_object($result)) {
                $numRecordsByCat[$row->category_id] = $row->number;
            }
        }
    }

    if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "view" && !(isset($_REQUEST["suchbegriff"]) && $_REQUEST["suchbegriff"] != "")) {
        // No search requested
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
        $query = 'SELECT '.SQLPREFIX.'faqdata.id AS id, '.SQLPREFIX.'faqdata.lang AS lang, '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id, '.SQLPREFIX.'faqdata.thema AS thema FROM '.SQLPREFIX.'faqdata INNER JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang ='.SQLPREFIX.'faqcategoryrelations.record_lang AND '.SQLPREFIX.'faqdata.active = \'yes\' '.$where.' ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.id ';

        $result = $db->query($query);
        $laction = 'view';
        $internalSearch = '';
    } else if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "view" && isset($_REQUEST["suchbegriff"]) && $_REQUEST["suchbegriff"] != "") {
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
        $laction = 'view';
        $internalSearch = '&amp;search='.$begriff;
        $wasSearch = true;

    } elseif (isset($_REQUEST["action"]) && $_REQUEST["action"] == "accept") {
        $query = 'SELECT '.SQLPREFIX.'faqdata.id AS id,'.SQLPREFIX.'faqdata.lang AS lang, '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id, '.SQLPREFIX.'faqdata.thema AS thema FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang ='.SQLPREFIX.'faqcategoryrelations.record_lang WHERE '.SQLPREFIX.'faqdata.active = \'no\' ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.id';
        $result = $db->query($query);
        $laction = 'accept';
        $internalSearch = '';
    }

    if ($db->num_rows($result) > 0) {
        $old = 0;
        while ($row = $db->fetch_object($result)) {
            $catInfo =  '';
            $isBracketOpened = false;
            $needComma = false;
            $cid = $row->category_id;
            if (isset($numRecordsByCat[$cid]) && ($numRecordsByCat[$cid] > 0)) {
                if (!$isBracketOpened) {
                    $catInfo .= ' (';
                    $isBracketOpened = true;
                }
                $catInfo .= sprintf('%d %s', $numRecordsByCat[$cid], $PMF_LANG['msgEntries']);
                $needComma = true;
            }
            if (isset($numCommentsByCat[$cid]) && ($numCommentsByCat[$cid] > 0) && $laction != 'accept') {
                if (!$isBracketOpened) {
                    $catInfo .= ' (';
                    $isBracketOpened = true;
                }
                $catInfo .= sprintf('%s%d %s', ($needComma ? ', ' : ''), $numCommentsByCat[$cid], $PMF_LANG['ad_start_comments']);
            }
            $catInfo .= $isBracketOpened ? ')' : '';
            if ($cid != $old) {
                if ($old == 0) {
?>
    <!--<a name="cat_<?php print $cid; ?>" />--><div class="categorylisting"><a href="#cat_<?php print $cid; ?>" onclick="showhideCategory('category_<?php print $cid; ?>');"><img src="../images/more.gif" width="11" height="11" alt="" /> <?php print $tree->getPath($cid); ?></a><?php print $catInfo;?></div>
    <div id="category_<?php print $cid; ?>" class="categorybox" style="display: none;">
    <table class="listrecords">
    <thead>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=asc">&darr;</a></th>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=lang&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=lang&amp;sortby=asc">&darr;</a></th>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=asc">&darr;</a></th>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=asc">&darr;</a></th>
        <th class="listhead" colspan="2">&nbsp;</th>
    </thead>
<?php
                } else {
?>
    </table>
    </div>
    <!--<a name="cat_<?php print $cid; ?>" />--><div class="categorylisting"><a href="#cat_<?php print $cid; ?>" onclick="showhideCategory('category_<?php print $cid; ?>');"><img src="../images/more.gif" width="11" height="11" alt="" /> <?php print $tree->getPath($cid); ?></a><?php print $catInfo;?></div>
    <div id="category_<?php print $cid; ?>" class="categorybox" style="display: none;">
    <table class="listrecords">
<?php
                }
?>
    <tbody>
<?php
            }
?>
    <tr>
        <td class="list" style="width: 24px; text-align: right;"><?php print $row->id; ?></td>
        <td class="list" style="width: 16px;"><?php print $row->lang; ?></td>
        <td class="list"><a href="?action=editentry&amp;id=<?php print $row->id; ?>&amp;lang=<?php print $row->lang; ?>" title="<?php print $PMF_LANG["ad_user_edit"]; ?> '<?php print str_replace("\"", "´", $row->thema); ?>'"><?php print PMF_htmlentities($row->thema, ENT_NOQUOTES, $PMF_LANG['metaCharset']); ?></a>
<?php
        if (isset($numCommentsByFaq[$row->id])) {
            print " (".$numCommentsByFaq[$row->id]." ".$PMF_LANG["ad_start_comments"].")";
        }
?>
        </td>
        <td class="list"></td>
        <td class="list" width="100"><?php print $linkverifier->getEntryStateHTML($row->id, $row->lang); ?></td>
        <td class="list" width="17"><a href="?action=saveentry&amp;id=<?php print $row->id; ?>&amp;language=<?php print $row->lang; ?>&amp;submit[0]=<?php print $PMF_LANG["ad_entry_delete"]; ?>" title="<?php print $PMF_LANG["ad_user_delete"]; ?> '<?php print str_replace("\"", "´", $row->thema); ?>'"><img src="images/delete.gif" width="17" height="18" alt="<?php print $PMF_LANG["ad_entry_delete"]; ?>" /></a></td>
    </tr>
<?php
            $old = $cid;
        }
?>
    </tbody>
    </table>
    </div>
    </fieldset>
    </form>
<?php
    } else {
        print $PMF_LANG["err_nothingFound"];
    }
} else {
    print $PMF_LANG["err_NotAuth"];
}
