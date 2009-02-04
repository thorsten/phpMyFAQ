<?php
/**
 * $Id: record.show.php,v 1.48 2008-01-26 10:35:16 thorstenr Exp $
 *
 * Shows the list of records ordered by categories
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Minoru TODA <todam@netjapan.co.jp>
 * @since       2003-02-23
 * @copyright   (c) 2003-2007 phpMyFAQ Team
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
    $category = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $category->transform(0);
    $category->buildTree();

    $linkverifier = new PMF_Linkverifier($db, $user->getLogin());
    if ($linkverifier->isReady()) {
        link_verifier_javascript();
    }

    $comment = new PMF_Comment($db, $LANGCODE);

    $faq = new PMF_Faq($db, $LANGCODE);

    $cond             = array();
    $numCommentsByFaq = array();
    $active           = 'yes';
    $internalSearch   = '';
    $linkState        = '';
    $searchterm       = '';
    $searchcat        = 0;
    $currentcategory  = 0;
    $orderby          = 1;
    $sortby           = null;

    if (isset($_REQUEST['linkstate'])) {
        $cond[SQLPREFIX.'faqdata.links_state'] = 'linkbad';
        $linkState = ' checked="checked" ';
        $internalSearch .= '&amp;linkstate=linkbad';
    }

    if (isset($_POST['searchcat']) && is_numeric($_POST['searchcat']) && $_POST['searchcat'] != 0) {
        $searchcat = (int)($_POST['searchcat']);
        $internalSearch .= "&amp;searchcat=".$searchcat;
        $cond[SQLPREFIX.'faqcategoryrelations.category_id'] = array_merge(array($searchcat), $category->getChildNodes($searchcat));
    }

    if (isset($_POST['searchterm'])) {
        $searchterm = safeSQL($_POST['searchterm']);
    }

    if (isset($_GET['action']) && $_GET['action'] == 'accept') {
        $active = 'no';
    }

    if (isset($_GET['category']) && is_numeric($_GET['category'])) {
        $currentcategory = (int)$_GET['category'];
    }

    if (isset($_GET['orderby'])) {
        switch ($db->escape_string($_GET['orderby'])) {
            case 'id':
                $orderby = 1;
                break;
            case 'title':
                $orderby = 2;
                break;
            case 'date':
                $orderby = 3;
                break;
            default:
                $orderby = 1;
                break;
        }
    }

    if (isset($_GET['sortby'])) {
        $sortby = $db->escape_string($_GET['sortby']);
    }
?>
    <form action="?action=view" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["msgSearch"]; ?></legend>
        <table class="admin">
        <tr>
            <td><strong><?php print $PMF_LANG["msgSearchWord"]; ?>:</strong></td>
            <td><input class="admin" type="text" name="searchterm" size="50" value="<?php print $searchterm; ?>" /></td>
            <td>
            <?php if ($linkverifier->isReady() == true) { ?>
            <input class="admin" type="checkbox" name="linkstate" value="linkbad" <?php print $linkState; ?> /><?php print $PMF_LANG['ad_linkcheck_searchbadonly']; ?>
            <?php } ?>
            </td>
        </tr>
        <tr>
            <td><strong><?php print $PMF_LANG["msgCategory"]; ?>:</strong></td>
            <td><select class="admin" name="searchcat">
            <option value="0"><?php print $PMF_LANG["msgShowAllCategories"]; ?></option>
            <?php print $category->printCategoryOptions($searchcat); ?>
            </select></td>
            <td><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["msgSearch"]; ?>" /></td>
        </tr>
        </table>
    </fieldset>

    <fieldset>
    <legend><?php print ((isset($_REQUEST['action']) && 'accept' == $_REQUEST['action']) ? $PMF_LANG['ad_menu_entry_aprove'] : $PMF_LANG['ad_menu_entry_edit']); ?></legend>
<?php
    $numCommentsByFaq = $comment->getNumberOfComments();

    // FIXME: Count "comments"/"entries" for each category also within a search context. Now the count is broken.
    // FIXME: we are not considering 'faqdata.links_state' for filtering the faqs.
    if (!(isset($_POST['searchterm']) && $_POST['searchterm'] != '')) {

        $matrix = $category->getCategoryRecordsMatrix();
        foreach ($matrix as $catkey => $value) {
            $numCommentsByCat[$catkey] = 0;
            foreach ($value as $faqkey => $value) {
                if (isset($numCommentsByFaq[$faqkey])) {
                    $numCommentsByCat[$catkey] += $numCommentsByFaq[$faqkey];
                }
            }
        }

        $numRecordsByCat = $category->getNumberOfRecordsOfCategory($active);
    }

    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && !(isset($_REQUEST["searchterm"]) && $_REQUEST["searchterm"] != "")) {

        $faq->getAllRecords($orderby, null, $sortby);
        $laction = 'view';
        $internalSearch = '';

    } else if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "view" && isset($_REQUEST["searchterm"]) && $_REQUEST["searchterm"] != "") {
        // Search for:
        // a. solution id
        // b. full text search
        // TODO: Decide if the search will be performed upon all entries or upon the active ones.
        $searchterm = strip_tags($_REQUEST["searchterm"]);
        if (is_numeric($searchterm)) {
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
                        $searchterm);
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
                        $searchterm,
                        array(),
                        array(SQLPREFIX.'faqcategoryrelations.category_id',  SQLPREFIX.'faqdata.id')
                        );
        }
        $laction = 'view';
        $internalSearch = '&amp;search='.$searchterm;
        $wasSearch = true;

        while ($row = $db->fetch_object($result)) {
            $faq->faqRecords[] = array(
                'id'            => $row->id,
                'category_id'   => $row->category_id,
                'lang'          => $row->lang,
                'title'         => $row->thema,
                'content'       => $row->content,
                'date'          => makeDate($row->datum));
        }

    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'accept') {

        $cond['fd.active'] = 'no';
        $faq->getAllRecords($orderby, $cond, $sortby);
        $laction = 'accept';
        $internalSearch = '';

    }

    $num = count($faq->faqRecords);

    if ($num > 0) {
        $old = 0;
        foreach ($faq->faqRecords as $record) {
            $catInfo =  '';
            $isBracketOpened = false;
            $needComma = false;
            $cid = $record['category_id'];
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
    <a name="cat_<?php print $cid; ?>"></a>
    <div class="categorylisting"><a href="#cat_<?php print $cid; ?>" onclick="showhideCategory('category_<?php print $cid; ?>');"><img src="../images/more.gif" width="11" height="11" alt="" /> <?php print $category->getPath($cid); ?></a><?php print $catInfo;?></div>
    <div id="category_<?php print $cid; ?>" class="categorybox" style="display: <?php print ($currentcategory == $cid) ? 'block' : 'none'; ?>;">
    <table class="listrecords">
    <thead>
    <tr>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=asc">&darr;</a></th>
        <th class="listhead">&nbsp;</th>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=asc">&darr;</a></th>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=asc">&darr;</a></th>
        <th class="listhead" colspan="2">&nbsp;</th>
    </tr>
    </thead>
<?php
                } else {
?>
    </table>
    </div>
    <!--<a name="cat_<?php print $cid; ?>" />--><div class="categorylisting"><a href="#cat_<?php print $cid; ?>" onclick="showhideCategory('category_<?php print $cid; ?>');"><img src="../images/more.gif" width="11" height="11" alt="" /> <?php print $category->getPath($cid); ?></a><?php print $catInfo;?></div>
    <div id="category_<?php print $cid; ?>" class="categorybox" style="display: <?php print ($currentcategory == $cid) ? 'block' : 'none'; ?>;">
    <table class="listrecords">
    <thead>
    <tr>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=asc">&darr;</a></th>
        <th class="listhead">&nbsp;</th>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=asc">&darr;</a></th>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=asc">&darr;</a></th>
        <th class="listhead" colspan="3">&nbsp;</th>
    </tr>
    </thead>
<?php
                }
?>
    <tbody>
<?php
            }
?>
    <tr>
        <td class="list" style="width: 24px; text-align: right;"><?php print $record['id']; ?></td>
        <td class="list" style="width: 16px;"><?php print $record['lang']; ?></td>
        <td class="list"><a href="?action=editentry&amp;id=<?php print $record['id']; ?>&amp;lang=<?php print $record['lang']; ?>" title="<?php print $PMF_LANG["ad_user_edit"]; ?> '<?php print str_replace("\"", "´", $record['title']); ?>'"><?php print PMF_htmlentities($record['title'], ENT_QUOTES, $PMF_LANG['metaCharset']); ?></a>
<?php
        if (isset($numCommentsByFaq[$record['id']])) {
            print " (".$numCommentsByFaq[$record['id']]." ".$PMF_LANG["ad_start_comments"].")";
        }
?>
        </td>
        <td class="list" width="50"><?php print substr($record['date'], 0, 10); ?></td>
        <td class="list" width="100"><?php print $linkverifier->getEntryStateHTML($record['id'], $record['lang']); ?></td>
        <td class="list" width="17"><a href="?action=saveentry&amp;id=<?php print $record['id']; ?>&amp;language=<?php print $record['lang']; ?>&amp;submit%5B0%5D=<?php print urlencode($PMF_LANG["ad_entry_delete"]); ?>" title="<?php print $PMF_LANG["ad_user_delete"]; ?> '<?php print str_replace("\"", "´", $record['title']); ?>'"><img src="images/delete.gif" width="17" height="18" alt="<?php print $PMF_LANG["ad_entry_delete"]; ?>" /></a></td>
        <td class="list" width="17"><a href="?action=copyentry&amp;id=<?php print $record['id']; ?>&amp;lang=<?php print $record['lang']; ?>">copy</a></td>
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
        print $PMF_LANG['err_nothingFound'];
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
