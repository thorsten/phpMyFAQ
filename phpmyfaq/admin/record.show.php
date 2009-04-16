<?php
/**
 * Shows the list of records ordered by categories
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Minoru TODA <todam@netjapan.co.jp>
 * @since      2003-02-23
 * @version    SVN: $Id$
 * @copyright  2003-2009 phpMyFAQ Team
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

if ($permission['editbt'] || $permission['delbt']) {
    // (re)evaluate the Category object w/o passing the user language
    $category = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $category->transform(0);
    $category->buildTree();

    $linkverifier = new PMF_Linkverifier($user->getLogin());
    if ($linkverifier->isReady()) {
        link_verifier_javascript();
    }

    $comment = new PMF_Comment();
    $faq     = new PMF_Faq();

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
    $linkState        = PMF_Filter::filterInput(INPUT_POST, 'linkstate', FILTER_SANITIZE_STRING);
    $searchcat        = PMF_Filter::filterInput(INPUT_POST, 'searchcat', FILTER_VALIDATE_INT);
    $searchterm       = PMF_Filter::filterInput(INPUT_POST, 'searchterm', FILTER_SANITIZE_STRIPPED);
    
    if (!is_null($linkState)) {
        $cond[SQLPREFIX.'faqdata.links_state'] = 'linkbad';
        $linkState                             = ' checked="checked" ';
        $internalSearch                       .= '&amp;linkstate=linkbad';
    }
    if (!is_null($searchcat)) {
        $internalSearch .= "&amp;searchcat=" . $searchcat;
        $cond[SQLPREFIX.'faqcategoryrelations.category_id'] = array_merge(array($searchcat), $category->getChildNodes($searchcat));
    }

    if ($action == 'accept') {
        $active = 'no';
    }

    $currentcategory = PMF_Filter::filterInput(INPUT_GET, 'category', FILTER_VALIDATE_INT);
    $orderby         = PMF_Filter::filterInput(INPUT_GET, 'orderby', FILTER_SANITIZE_STRING, 1);
    $sortby          = PMF_Filter::filterInput(INPUT_GET, 'sortby', FILTER_SANITIZE_STRING);
    if ($orderby != 1) {
        switch ($orderby) {
            case 'id':
                $orderby = 1;
                break;
            case 'title':
                $orderby = 2;
                break;
            case 'date':
                $orderby = 3;
                break;
        }
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
    </form>

    <form id="recordSelection" name="recordSelection" method="post">
    <fieldset>
    <legend><?php print ($action == 'accept' ? $PMF_LANG['ad_menu_entry_aprove'] : $PMF_LANG['ad_menu_entry_edit']); ?></legend>
<?php
    $numCommentsByFaq = $comment->getNumberOfComments();

    // FIXME: Count "comments"/"entries" for each category also within a search context. Now the count is broken.
    // FIXME: we are not considering 'faqdata.links_state' for filtering the faqs.
    if (!is_null($searchterm)) {

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

    if ($action == 'view' && is_null($searchterm)) {

        $faq->getAllRecords($orderby, null, $sortby);
        $laction        = 'view';
        $internalSearch = '';

    } elseif ($action == "view" && !is_null($searchterm)) {
        // Search for:
        // a. solution id
        // b. full text search
        // TODO: Decide if the search will be performed upon all entries or upon the active ones.
        if (is_numeric($searchterm)) {
            // a. solution id
            $result = $db->search(SQLPREFIX.'faqdata',
                                  array(SQLPREFIX.'faqdata.id AS id',
                                        SQLPREFIX.'faqdata.lang AS lang',
                                        SQLPREFIX.'faqcategoryrelations.category_id AS category_id',
                                        SQLPREFIX.'faqdata.sticky AS sticky',
                                        SQLPREFIX.'faqdata.thema AS thema',
                                        SQLPREFIX.'faqdata.content AS content',
                                        SQLPREFIX.'faqdata.datum AS date'),
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
                                        SQLPREFIX.'faqdata.sticky AS sticky',
                                        SQLPREFIX.'faqdata.thema AS thema',
                                        SQLPREFIX.'faqdata.content AS content',
                                        SQLPREFIX.'faqdata.datum AS date'),
                                  SQLPREFIX.'faqcategoryrelations',
                                  array(SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id',
                                        SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang'),
                                  array(SQLPREFIX.'faqdata.thema',
                                        SQLPREFIX.'faqdata.content',
                                        SQLPREFIX.'faqdata.keywords'),
                                  $searchterm,
                                  array(),
                                  array(SQLPREFIX.'faqcategoryrelations.category_id',  SQLPREFIX.'faqdata.id'));
        }
        
        $laction        = 'view';
        $internalSearch = '&amp;search='.$searchterm;
        $wasSearch      = true;

        while ($row = $db->fetch_object($result)) {
            $faq->faqRecords[] = array(
                'id'          => $row->id,
                'category_id' => $row->category_id,
                'lang'        => $row->lang,
                'sticky'      => $row->sticky,
                'title'       => $row->thema,
                'content'     => $row->content,
                'date'        => makeDate($row->date));
        }

    } elseif ($action == 'accept') {

        $cond['fd.active'] = 'no';
        $faq->getAllRecords($orderby, $cond, $sortby);
        $laction        = 'accept';
        $internalSearch = '';

    }

    $num = count($faq->faqRecords);

    if ($num > 0) {
        $old = 0;
        $all_ids = array();
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
    <div class="categorylisting"><a href="javascript:void(0);" onclick="showhideCategory('category_<?php print $cid; ?>');"><img src="../images/more.gif" width="11" height="11" alt="" /> <?php print $category->getPath($cid); ?></a><?php print $catInfo;?></div>
    <div id="category_<?php print $cid; ?>" class="categorybox" style="display: <?php print ($currentcategory == $cid) ? 'block' : 'none'; ?>;">
    <table class="listrecords">
    <thead>
    <tr>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=asc">&darr;</a></th>
        <th class="listhead">&nbsp;</th>
        <th class="listhead" style="text-align: left"><input type="checkbox" id="category_block_<?php print $cid; ?>" onclick="saveStickyStatusForCategory(<?php print $cid; ?>)" />&nbsp;<?php print $PMF_LANG['ad_record_sticky'] ?></th>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=asc">&darr;</a></th>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=asc">&darr;</a></th>
        <th class="listhead" colspan="2">&nbsp;</th>
    </tr>
    </thead>
<?php
                } else {
?>
    </tbody>
    </table>
    </div>
    <div class="categorylisting"><a href="javascript:void(0);" onclick="showhideCategory('category_<?php print $cid; ?>');"><img src="../images/more.gif" width="11" height="11" alt="" /> <?php print $category->getPath($cid); ?></a><?php print $catInfo;?></div>
    <div id="category_<?php print $cid; ?>" class="categorybox" style="display: <?php print ($currentcategory == $cid) ? 'block' : 'none'; ?>;">
    <table class="listrecords">
    <thead>
    <tr>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=asc">&darr;</a></th>
        <th class="listhead">&nbsp;</th>
        <th class="listhead" style="text-align: left"><input type="checkbox" id="category_block_<?php print $cid; ?>" onclick="saveStickyStatusForCategory(<?php print $cid; ?>)" />&nbsp;<?php print $PMF_LANG['ad_record_sticky'] ?></th>
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
    <tr class="record_<?php print $record['id']; ?>_<?php print $record['lang']; ?>">
        <td class="list" style="width: 24px; text-align: right;"><?php print $record['id']; ?></td>
        <td class="list" style="width: 16px;"><?php print $record['lang']; ?></td>
        <td class="list"><input type="checkbox" lang="<?php print $record['lang'] ?>" onclick="saveStickyStatus(<?php print $cid . ', [' . $record['id'] . ']' ?>);" id="record_<?php print $cid . '_' . $record['id'] ?>" <?php $record['sticky'] ? print 'checked="checked"' : print '    ' ?> /></td>
        <td class="list"><a href="?action=editentry&amp;id=<?php print $record['id']; ?>&amp;lang=<?php print $record['lang']; ?>" title="<?php print $PMF_LANG["ad_user_edit"]; ?> '<?php print str_replace("\"", "´", $record['title']); ?>'"><?php print PMF_htmlentities($record['title'], ENT_QUOTES, $PMF_LANG['metaCharset']); ?></a>
<?php
        if (isset($numCommentsByFaq[$record['id']])) {
            print " (".$numCommentsByFaq[$record['id']]." ".$PMF_LANG["ad_start_comments"].")";
        }
?></td>
        <td class="list" width="50"><?php print substr($record['date'], 0, 10); ?></td>
        <td class="list" width="100"><?php print $linkverifier->getEntryStateHTML($record['id'], $record['lang']); ?></td>
        <td class="list" width="17"><a href="#" onclick="javascript:deleteRecord(<?php print $record['id']; ?>, '<?php print $record['lang']; ?>');" title="<?php print $PMF_LANG["ad_user_delete"]; ?> '<?php print str_replace("\"", "´", $record['title']); ?>'"><img src="images/delete.gif" width="17" height="18" alt="<?php print $PMF_LANG["ad_entry_delete"]; ?>" /></a></td>
        <td class="list" width="17"><a href="?action=copyentry&amp;id=<?php print $record['id']; ?>&amp;lang=<?php print $record['lang']; ?>">copy</a></td>
    </tr>
<?php
            $old = $cid;
            
            $all_ids[$cid][] = $record['id'];
        }
?>
    </tbody>
    </table>
    </div>
    </fieldset>
    </form>
    
    <script type="text/javascript">
    /* <![CDATA[ */

    /**
     * Saves the sticky record status for the whole category
     *
     * @param  integer id id
     * @return void
     */
    function saveStickyStatusForCategory(id)
    {
    	var id_map = [];
<?php 
foreach($all_ids as $cat_id => $record_ids) {
    echo "        id_map[$cat_id] = [" . implode(',', $record_ids) . "];\n";
}
?>
        for(var i = 0; i < id_map[id].length; i++) {
        	$('#record_' + id + '_' + id_map[id][i]).attr('checked', $('#category_block_' + id).attr('checked'));
        }

        saveStickyStatus(id, id_map[id]);
    }

    /**
     * Ajax call for saving the sticky record status
     *
     * @param  integer cid category id
     * @param  integer ids ids
     * @return void
     */
    function saveStickyStatus(cid, ids)
    {
        var data = {action: "ajax", ajax: 'records', ajaxaction: "save_sticky_records"};
        
        for(var i = 0; i < ids.length; i++) {
            data['items[' + i + '][]'] = [ids[i], $('#record_' + cid + '_' + ids[i]).attr('lang'), $('#record_' + cid + '_' + ids[i]).attr('checked')*1];

            // Updating the current record if it's also contained in another category
            var same_records = $('input').filter(function(){return this.id.match(new RegExp('record_(\\d+)_' + ids[i]));});
            for (var j = 0; j<same_records.length; j++) {
                $('#' + same_records[j].id).attr('checked', $('#record_' + cid + '_' + ids[i]).attr('checked'));
            }
        }
    
        $.get("index.php", data, null);
    }

    /**
     * Ajax call for deleting records
     *
     * @param  integer record_id   Record id
     * @param  string  record_lang Record language
     * @return void
     */
    function deleteRecord(record_id, record_lang)
    {
        if (confirm('<?php print $PMF_LANG["ad_entry_del_1"] . " " . $PMF_LANG["ad_entry_del_3"]; ?>')) {
            $.ajax({
                type:    "POST",
                url:     "index.php?action=ajax&ajax=records&ajaxaction=delete_record",
                data:    "record_id=" + record_id + "&record_lang=" + record_lang,
                success: function(msg) {
                    $('.record_' + record_id + '_' + record_lang).fadeOut('slow');
                    $('.record_' + record_id + '_' + record_lang).after('<tr><td colspan="8">' + msg + '</td></tr>');
                }
            });
        }
    }
    
    /* ]]> */
    </script>
<?php
    } else {
        print $PMF_LANG['err_nothingFound'];
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
