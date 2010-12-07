<?php
/**
 * Shows the list of records ordered by categories
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
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @copyright 2003-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);

if ($permission['editbt'] || $permission['delbt']) {
	
    // (re)evaluate the Category object w/o passing the user language
    $category = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $category->transform(0);
    
    // Set the Category for the helper class
    $helper = PMF_Helper_Category::getInstance();
    $helper->setCategory($category);

    $category->buildTree();
    
    $linkverifier = new PMF_Linkverifier($user->getLogin());
    if ($linkverifier->isReady()) {
        link_verifier_javascript();
    }

    $comment = new PMF_Comment();
    $faq     = new PMF_Faq();

    $cond           = $numCommentsByFaq = $numActiveByCat = array();
    $internalSearch = $linkState = $searchterm = '';
    $searchcat      = $currentcategory = 0;
    $orderby        = 1;
    $sortby         = null;
    $linkState      = PMF_Filter::filterInput(INPUT_POST, 'linkstate', FILTER_SANITIZE_STRING);
    $searchcat      = PMF_Filter::filterInput(INPUT_POST, 'searchcat', FILTER_VALIDATE_INT);
    $searchterm     = PMF_Filter::filterInput(INPUT_POST, 'searchterm', FILTER_SANITIZE_STRIPPED);
    
    if (!is_null($linkState)) {
        $cond[SQLPREFIX.'faqdata.links_state'] = 'linkbad';
        $linkState                             = ' checked="checked" ';
        $internalSearch                       .= '&amp;linkstate=linkbad';
    }
    if (!is_null($searchcat)) {
        $internalSearch .= "&amp;searchcat=" . $searchcat;
        $cond[SQLPREFIX.'faqcategoryrelations.category_id'] = array_merge(array($searchcat), $category->getChildNodes($searchcat));
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
            <?php print $helper->renderCategoryOptions($searchcat); ?>
            </select></td>
            <td><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["msgSearch"]; ?>" /></td>
        </tr>
        </table>
    </fieldset>
    </form>

    <form id="recordSelection" name="recordSelection" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG['ad_menu_entry_edit']; ?></legend>
<?php
    $numCommentsByFaq = $comment->getNumberOfComments();
    $numRecordsByCat  = $category->getNumberOfRecordsOfCategory();

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
    }
    
    if ($action == 'view' && is_null($searchterm)) {

        $faq->getAllRecords($orderby, null, $sortby);
        $laction        = 'view';
        $internalSearch = '';
        
        foreach ($faq->faqRecords as $record) {
            if (!isset($numActiveByCat[$record['category_id']])) {
                $numActiveByCat[$record['category_id']] = 0;
            }
            $numActiveByCat[$record['category_id']] += $record['active'] == 'yes' ? 1 : 0;
        }

    } elseif ($action == "view" && !is_null($searchterm)) {
        
        $fdTable  = SQLPREFIX . 'faqdata';
        $fcrTable = SQLPREFIX . 'faqcategoryrelations';
        $search   = PMF_Search_Factory::create($Language, array('database' => PMF_Db::getType()));

        $search->setDatabaseHandle($db)
               ->setTable($fdTable)
               ->setResultColumns(array(
                    $fdTable . '.id AS id',
                    $fdTable . '.lang AS lang',
                    $fdTable . '.solution_id AS solution_id',
                    $fcrTable . '.category_id AS category_id',
                    $fdTable . '.sticky AS sticky',
                    $fdTable . '.active AS active',
                    $fdTable . '.thema AS thema',
                    $fdTable . '.content AS content',
                    $fdTable . '.datum AS date'))
               ->setJoinedTable($fcrTable)
               ->setJoinedColumns(array(
                    $fdTable . '.id = ' . $fcrTable . '.record_id',
                    $fdTable . '.lang = ' . $fcrTable . '.record_lang'));
        
        if (is_numeric($searchterm)) {
            $search->setMatchingColumns(array($fdTable . '.solution_id'));
        } else {
            $search->setMatchingColumns(array($fdTable . '.thema', $fdTable . '.content', $fdTable . '.keywords'));
        }
        
        $result         = $search->search($searchterm);; // @todo add missing ordering!
        $laction        = 'view';
        $internalSearch = '&amp;search='.$searchterm;
        $wasSearch      = true;

        while ($row = $db->fetch_object($result)) {
            
            if ($searchcat != 0 && $searchcat != (int)$row->category_id) {
                continue;
            }
            
            $faq->faqRecords[] = array(
                'id'          => $row->id,
                'category_id' => $row->category_id,
                'solution_id' => $row->solution_id,
                'lang'        => $row->lang,
                'active'      => $row->active,
                'sticky'      => $row->sticky,
                'title'       => $row->thema,
                'content'     => $row->content,
                'date'        => PMF_Date::createIsoDate($row->date));
            
            if (!isset($numActiveByCat[$row->category_id])) {
                $numActiveByCat[$row->category_id] = 0;
            }
            $numActiveByCat[$row->category_id] += $row->active ? 1 : 0;
        }

    }

    $num = count($faq->faqRecords);

    if ($num > 0) {
        $old     = 0;
        $all_ids = $visits = array();
        
        foreach (PMF_Visits::getInstance()->getAllData() as $visit) {
            $visits[$visit['id']] = $visit['lang'];
        }
        
        foreach ($faq->faqRecords as $record) {
            $catInfo         =  '';
            $isBracketOpened = false;
            $needComma       = false;
            $cid             = $record['category_id'];
            
            if (isset($numRecordsByCat[$cid]) && ($numRecordsByCat[$cid] > 0)) {
                if (!$isBracketOpened) {
                    $catInfo        .= ' (';
                    $isBracketOpened = true;
                }
                $catInfo .= sprintf('<span id="category_%d_item_count">%d</span> %s', 
                    $cid, 
                    $numRecordsByCat[$cid], 
                    $PMF_LANG['msgEntries']);
            }
            
            if (isset($numRecordsByCat[$cid]) && $numRecordsByCat[$cid] > $numActiveByCat[$cid]) {
                $catInfo .= sprintf(', <span style="color: red;">%d %s</span>', 
                    $numActiveByCat[$cid], 
                    $PMF_LANG['ad_record_active']);
                $needComma = true;
            }
            
            if (isset($numCommentsByCat[$cid]) && ($numCommentsByCat[$cid] > 0)) {
                if (!$isBracketOpened) {
                    $catInfo        .= ' (';
                    $isBracketOpened = true;
                }
                $catInfo .= sprintf('%s%d %s', ($needComma ? ', ' : ''), $numCommentsByCat[$cid], $PMF_LANG['ad_start_comments']);
            }
            $catInfo .= $isBracketOpened ? ')' : '';
            
            if ($cid != $old) {
                if ($old == 0) {
                    printf('<a name="cat_%d"></a>', $cid);
                } else {
                    print "    </tbody>\n    </table>\n    </div>";
                }
?>
    <div class="categorylisting"><a href="javascript:void(0);" onclick="showhideCategory('category_<?php print $cid; ?>');"><img src="../images/more.gif" width="11" height="11" alt="" /> <?php print $category->getPath($cid); ?></a><?php print $catInfo;?></div>
    <div id="category_<?php print $cid; ?>" class="categorybox" style="display: <?php print ($currentcategory == $cid) ? 'block' : 'none'; ?>;">
    <table class="listrecords">
    <thead>
    <tr>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=id&amp;sortby=asc">&darr;</a></th>
        <th class="listhead">&nbsp;</th>
        <th class="listhead">#</th>
        <th class="listhead" style="text-align: left"><input type="checkbox" id="sticky_category_block_<?php print $cid; ?>" onclick="saveStatusForCategory(<?php print $cid; ?>, 'sticky')" />&nbsp;<?php print $PMF_LANG['ad_record_sticky'] ?></th>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=title&amp;sortby=asc">&darr;</a></th>
        <th class="listhead"><a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=desc">&uarr;</a>&nbsp;<a href="?action=view&amp;category=<?php print $cid; ?>&amp;orderby=date&amp;sortby=asc">&darr;</a></th>
        <th class="listhead" colspan="3">&nbsp;</th>
        <th class="listhead" style="text-align: left">
            <?php if ($permission['approverec']) { ?>
            <input type="checkbox" id="active_category_block_<?php print $cid; ?>" onclick="saveStatusForCategory(<?php print $cid; ?>, 'active')" />&nbsp;<?php print $PMF_LANG['ad_record_active'] ?>
            <?php } ?>
        </th>
    </tr>
    </thead>
    <tbody>
<?php
            }
?>
    <tr class="record_<?php print $record['id']; ?>_<?php print $record['lang']; ?>">
        <td class="list" style="width: 24px; text-align: right;"><?php print $record['id']; ?></td>
        <td class="list" style="width: 16px;"><?php print $record['lang']; ?></td>
        <td class="list" style="width: 24px;"><a href="?action=editentry&amp;id=<?php print $record['id']; ?>&amp;artlang=<?php print $record['lang']; ?>" title="<?php print $PMF_LANG["ad_user_edit"]; ?> '<?php print str_replace("\"", "´", $record['title']); ?>'"><?php print $record['solution_id']; ?></a></td>
        <td class="list" style="width: 56px;"><input type="checkbox" lang="<?php print $record['lang'] ?>" onclick="saveStatus(<?php print $cid . ', [' . $record['id'] . ']' ?>, 'sticky');" id="sticky_record_<?php print $cid . '_' . $record['id'] ?>" <?php $record['sticky'] ? print 'checked="checked"' : print '    ' ?> /></td>
        <td class="list"><a href="?action=editentry&amp;id=<?php print $record['id']; ?>&amp;artlang=<?php print $record['lang']; ?>" title="<?php print $PMF_LANG["ad_user_edit"]; ?> '<?php print str_replace("\"", "´", $record['title']); ?>'"><?php print $record['title']; ?></a>
<?php
        if (isset($numCommentsByFaq[$record['id']])) {
            print " (".$numCommentsByFaq[$record['id']]." ".$PMF_LANG["ad_start_comments"].")";
        }
?></td>
        <td class="list" style="width: 48px;"><?php print PMF_String::substr($record['date'], 0, 10); ?></td>
        <td class="list" style="width: 96px;"><?php print $linkverifier->getEntryStateHTML($record['id'], $record['lang']); ?></td>
        <td class="list" style="width: 16px;">
            <a href="#" onclick="javascript:deleteRecord(<?php print $record['id']; ?>, '<?php print $record['lang']; ?>');" title="<?php print $PMF_LANG["ad_user_delete"]; ?>">
                <img src="images/delete.png" alt="<?php print $PMF_LANG["ad_entry_delete"]; ?>" />
            </a>
        </td>
        <td class="list" style="width: 16px;">
            <a href="?action=copyentry&amp;id=<?php print $record['id']; ?>&amp;artlang=<?php print $record['lang']; ?>">
            <img src="images/copy.png" alt="<?php print $PMF_LANG['ad_categ_copy']; ?>" title="<?php print $PMF_LANG['ad_categ_copy']; ?>" />
            </a>
        </td>
        <td class="list">
            <?php if ($permission['approverec'] && isset($visits[$record['id']])) { ?>
            <input type="checkbox" lang="<?php print $record['lang'] ?>" onclick="saveStatus(<?php print $cid . ', [' . $record['id'] . ']' ?>, 'active');" id="active_record_<?php print $cid . '_' . $record['id'] ?>" <?php 'yes' == $record['active'] ? print 'checked="checked"' : print '    ' ?> />
            <?php } ?>
        </td>
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
     * @param integer id   id
     * @param string  type status type
     * 
     * @return void
     */
    function saveStatusForCategory(id, type)
    {
        var id_map = [];
<?php 
foreach ($all_ids as $cat_id => $record_ids) {
    echo "        id_map[" . $cat_id . "] = [" . implode(',', $record_ids) . "];\n";
}
?>
        for (var i = 0; i < id_map[id].length; i++) {
            var status = $('#' + type + '_category_block_' + id).attr('checked'); 
            
            $('#' + type + '_record_' + id + '_' + id_map[id][i]).attr('checked', status);
        }

        saveStatus(id, id_map[id], type);
    }

    /**
     * Ajax call for saving the sticky record status
     *
     * @param integer cid  category id
     * @param integer ids  ids
     * @param string  type status type
     *
     * @return void
     */
    function saveStatus(cid, ids, type)
    {
        $('#saving_data_indicator').html('<img src="images/indicator.gif" /> saving ...');
        var data = {action: "ajax", ajax: 'records', ajaxaction: "save_" + type + "_records"};
        
        for (var i = 0; i < ids.length; i++) {
            var status = $('#' + type + '_record_' + cid + '_' + ids[i]).attr('checked');
            var lang   = $('#' + type + '_record_' + cid + '_' + ids[i]).attr('lang');
            
            data['items[' + i + '][]'] = [ids[i], lang, status*1];

            // Updating the current record if it's also contained in another category
            var same_records = $('input').filter(function() {
                return this.id.match(new RegExp(type + '_record_(\\d+)_' + ids[i]));
            });

            if ('active' == type) {
                for (var j = 0; j < same_records.length; j++) {
                    $('#' + same_records[j].id).attr('checked', status);
                    
                    var catid              = same_records[j].id.match(/active_record_(\d+)_\d+/)[1];
                    var current_item_count = $('#category_' + catid + '_item_count').html();
                    var delta              = status ? 1 : -1;
                    
                    $('#category_' + catid + '_item_count').html(current_item_count * 1 + delta);
                }
            } else {
                for (var j = 0; j < same_records.length; j++) {
                    $('#' + same_records[j].id).attr('checked', status);
                }
            }
        }
    
        $.get("index.php", data, null);
        $('#saving_data_indicator').html('<?php print $PMF_LANG['ad_entry_savedsuc']; ?>');
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
        if (confirm('<?php print addslashes($PMF_LANG["ad_entry_del_1"] . " " . $PMF_LANG["ad_entry_del_3"]); ?>')) {
            $('#saving_data_indicator').html('<img src="images/indicator.gif" /> deleting ...');
            $.ajax({
                type:    "POST",
                url:     "index.php?action=ajax&ajax=records&ajaxaction=delete_record",
                data:    "record_id=" + record_id + "&record_lang=" + record_lang,
                success: function(msg) {
                    $('.record_' + record_id + '_' + record_lang).fadeOut('slow');
                    $('.record_' + record_id + '_' + record_lang).after('<tr><td colspan="8">' + msg + '</td></tr>');
                    $('#saving_data_indicator').html('<?php print $PMF_LANG['ad_entry_delsuc']; ?>');
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
