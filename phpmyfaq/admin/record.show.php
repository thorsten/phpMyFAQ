<?php
/**
 * Shows the list of records ordered by categories.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Minoru TODA <todam@netjapan.co.jp>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-23
 */

use phpMyFAQ\Category;
use phpMyFAQ\Comment;
use phpMyFAQ\Date;
use phpMyFAQ\Db;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Linkverifier;
use phpMyFAQ\Search\SearchFactory;
use phpMyFAQ\Visits;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-list-alt"></i>
              <?= $PMF_LANG['ad_entry_aor'] ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->checkRight($user->getUserId(), 'edit_faq') || $user->perm->checkRight($user->getUserId(), 'delete_faq')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->transform(0);

    // Set the CategoryHelper for the helper class
    $categoryHelper = new CategoryHelper();
    $categoryHelper->setCategory($category);

    $faqHelper = new FaqHelper($faqConfig);

    $category->buildTree();

    $linkVerifier = new Linkverifier($faqConfig, $user->getLogin());
    if ($linkVerifier->isReady()) {
?>
    <script>
        function getImageElement(id, lang) {
            return $('#imgurl_' + lang + '_' + id);
        }

        function getSpanElement(id, lang) {
            return $('#spanurl_' + lang + '_' + id);
        }

        function getDivElement(id, lang) {
            return $('#divurl_' + lang + '_' + id);
        }

        function onDemandVerifyURL(id, lang, target) {
            var target = getSpanElement(id, lang),
                widthPx  = 780,
                heigthPx = 450,
                leftPx   = (screen.width  - widthPx) / 2,
                topPx    = (screen.height - heigthPx) / 2,
                pmfWindow = window.open('index.php?action=ajax&ajax=onDemandURL&id=' + id + '&artlang=' + lang, 'onDemandURLVerification', 'toolbar=no, location=no, status=no, menubar=no, width=' + widthPx + ', height=' + heigthPx + ', left=' + leftPx + ', top=' + topPx + ', resizable=yes, scrollbars=yes');
                pmfWindow.focus();

            verifyEntryURL(id, lang);
        }

        function verifyEntryURL(id, lang) {
            const target = getSpanElement(id, lang);

            // !!IMPORTANT!! DISABLE ONLOAD. If you do not do this, you will get infinite loop!
            getImageElement(id, lang).onload = '';

            //target.src = "images/url-checking.png";
            getDivElement(id, lang).className = "url-checking";
            target.innerHTML = "<?= $PMF_LANG['ad_linkcheck_feedback_url-checking'] ?>";

            const url = 'index.php';
            const pars = 'action=ajax&ajax=verifyURL&id=' + id + '&artlang=' + lang;
            const myAjax = new jQuery.ajax({url: url,
                type: 'get',
                data: pars,
                complete: verifyEntryURL_success,
                error: verifyEntryURL_failure});

            function verifyEntryURL_success(XmlRequest)
            {
                let allResponses = new [];
                allResponses['batch1'] = "<?= $PMF_LANG['ad_linkcheck_feedback_url-batch1'] ?>";
                allResponses['batch2'] = "<?= $PMF_LANG['ad_linkcheck_feedback_url-batch2'] ?>";
                allResponses['batch3'] = "<?= $PMF_LANG['ad_linkcheck_feedback_url-batch3'] ?>";
                allResponses['checking'] = "<?= $PMF_LANG['ad_linkcheck_feedback_url-checking'] ?>";
                allResponses['disabled'] = "<?= $PMF_LANG['ad_linkcheck_feedback_url-disabled'] ?>";
                allResponses['linkbad'] = "<?= $PMF_LANG['ad_linkcheck_feedback_url-linkbad'] ?>";
                allResponses['linkok'] = "<?= $PMF_LANG['ad_linkcheck_feedback_url-linkok'] ?>";
                allResponses['noaccess'] = "<?= $PMF_LANG['ad_linkcheck_feedback_url-noaccess'] ?>";
                allResponses['noajax'] = "<?= $PMF_LANG['ad_linkcheck_feedback_url-noajax'] ?>";
                allResponses['nolinks'] = "<?= $PMF_LANG['ad_linkcheck_feedback_url-nolinks'] ?>";
                allResponses['noscript'] = "<?= $PMF_LANG['ad_linkcheck_feedback_url-noscript'] ?>";
                getDivElement(id, lang).className = "url-" + XmlRequest.responseText;
                if (typeof(allResponses[XmlRequest.responseText]) === "undefined") {
                    getDivElement(id, lang).className = "url-noajax ";
                    target.html(allResponses['noajax']);
                } else {
                    target.html(allResponses[XmlRequest.responseText]);
                }
            }

            function verifyEntryURL_failure(XmlRequest)
            {
                getDivElement(id, lang).className = "url-noaccess";
                target.html("<?= $PMF_LANG['ad_linkcheck_feedback_url-noaccess'] ?>");
            }

        }
    </script>
<?php

    }

    $comment = new Comment($faqConfig);
    $faq = new Faq($faqConfig);
    $faq->setUser($currentAdminUser);
    $faq->setGroups($currentAdminGroups);
    $date = new Date($faqConfig);

    $internalSearch = '';
    $linkState = Filter::filterInput(INPUT_POST, 'linkstate', FILTER_SANITIZE_STRING);
    $searchCat = Filter::filterInput(INPUT_POST, 'searchcat', FILTER_VALIDATE_INT);
    $searchTerm = Filter::filterInput(INPUT_POST, 'searchterm', FILTER_SANITIZE_STRIPPED);

    if (!is_null($linkState)) {
        $cond[Db::getTablePrefix().'faqdata.links_state'] = 'linkbad';
        $linkState = ' checked ';
        $internalSearch .= '&linkstate=linkbad';
    }
    if (!is_null($searchCat)) {
        $internalSearch .= '&searchcat='.$searchCat;
        $cond[Db::getTablePrefix().'faqcategoryrelations.category_id'] = array_merge(
            [$searchCat],
            $category->getChildNodes($searchCat)
        );
    }

    $selectedCategory = Filter::filterInput(INPUT_GET, 'category', FILTER_VALIDATE_INT, 0);
    $orderBy = Filter::filterInput(INPUT_GET, 'orderby', FILTER_SANITIZE_STRING, 1);
    $sortBy = Filter::filterInput(INPUT_GET, 'sortby', FILTER_SANITIZE_STRING);
    if (1 !== $orderBy) {
        switch ($orderBy) {
            case 'id':
                $orderBy = 1;
                break;
            case 'title':
                $orderBy = 2;
                break;
            case 'date':
                $orderBy = 3;
                break;
        }
    }
    ?>
    <form id="recordSelection" name="recordSelection" method="post" accept-charset="utf-8">
        <div class="accordion" id="accordion" role="tablist" aria-multiselectable="true">
<?php
    $numCommentsByFaq = $comment->getNumberOfComments();
    $numCommentsByCat = [];
    $numRecordsByCat = $category->getNumberOfRecordsOfCategory($faqConfig->get('main.enableCategoryRestrictions'));
    $numActiveByCat = [];

    $matrix = $category->getCategoryRecordsMatrix();
    foreach ($matrix as $categoryKey => $value) {
        $numCommentsByCat[$categoryKey] = 0;
        foreach ($value as $faqKey => $value) {
            if (isset($numCommentsByFaq[$faqKey])) {
                $numCommentsByCat[$categoryKey] += $numCommentsByFaq[$faqKey];
            }
        }
    }

    if (is_null($searchTerm)) {
        if ($faqConfig->get('main.enableCategoryRestrictions')) {
            $Language = new Language($faqConfig);
            $language = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
            $faq->getAllRecords($orderBy, ['lang' => $language], $sortBy);  
        } else {
            $faq->getAllRecords($orderBy, null, $sortBy);
        }

        foreach ($faq->faqRecords as $record) {
            if (!isset($numActiveByCat[$record['category_id']])) {
                $numActiveByCat[$record['category_id']] = 0;
            }
            $numActiveByCat[$record['category_id']] += $record['active'] == 'yes' ? 1 : 0;
        }
    } else {
        $fdTable = Db::getTablePrefix().'faqdata';
        $fcrTable = Db::getTablePrefix().'faqcategoryrelations';
        $search = SearchFactory::create($faqConfig, array('database' => Db::getType()));

        $search->setTable($fdTable)
            ->setResultColumns(array(
                    $fdTable.'.id AS id',
                    $fdTable.'.lang AS lang',
                    $fdTable.'.solution_id AS solution_id',
                    $fcrTable.'.category_id AS category_id',
                    $fdTable.'.sticky AS sticky',
                    $fdTable.'.active AS active',
                    $fdTable.'.thema AS thema',
                    $fdTable.'.content AS content',
                    $fdTable.'.updated AS updated',))
            ->setJoinedTable($fcrTable)
            ->setJoinedColumns(array(
                    $fdTable.'.id = '.$fcrTable.'.record_id',
                    $fdTable.'.lang = '.$fcrTable.'.record_lang',));

        if (is_numeric($searchTerm)) {
            $search->setMatchingColumns(array($fdTable.'.solution_id'));
        } else {
            $search->setMatchingColumns(array($fdTable.'.thema', $fdTable.'.content', $fdTable.'.keywords'));
        }

        $result = $search->search($searchTerm);
        $laction = 'view';
        $internalSearch = '&search='.$searchTerm;
        $wasSearch = true;
        $idsFound = array();
        $faqsFound = array();

        while ($row = $faqConfig->getDb()->fetchObject($result)) {
            if ($searchCat != 0 && $searchCat != (int)$row->category_id) {
                continue;
            }

            if (in_array($row->id, $idsFound)) {
                continue; // only show one entry if FAQ is in multiple categories
            }

            $faqsFound[$row->category_id][$row->id] = [
                'id' => $row->id,
                'category_id' => $row->category_id,
                'solution_id' => $row->solution_id,
                'lang' => $row->lang,
                'active' => $row->active,
                'sticky' => $row->sticky,
                'title' => $row->thema,
                'content' => $row->content,
                'updated' => Date::createIsoDate($row->updated),
            ];

            if (!isset($numActiveByCat[$row->category_id])) {
                $numActiveByCat[$row->category_id] = 0;
            }

            $numActiveByCat[$row->category_id] += $row->active ? 1 : 0;

            $idsFound[] = $row->id;
        }

        // Sort search result ordered by category ID
        ksort($faqsFound);
        foreach ($faqsFound as $categoryId => $faqFound) {
            foreach ($faqFound as $singleFaq) {
                $faq->faqRecords[] = $singleFaq;
            }
        }
    }

    if (count($faq->faqRecords) > 0) {
        $old = 0;
        $faqIds = [];

        $visits = new Visits($faqConfig);
        $numVisits = [];
        foreach ($visits->getAllData() as $visit) {
            $numVisits[$visit['id']] = $visit['lang'];
        }

        foreach ($faq->faqRecords as $record) {

            $catInfo = '';
            $cid = $record['category_id'];

            if (isset($numRecordsByCat[$cid])) {
                $catInfo .= sprintf(
                    '<span class="badge badge-info" id="category_%d_item_count">%d %s</span> ',
                    $cid,
                    $numRecordsByCat[$cid],
                    $PMF_LANG['msgEntries']
                );
            }

            if (isset($numRecordsByCat[$cid]) && $numRecordsByCat[$cid] > $numActiveByCat[$cid]) {
                $catInfo .= sprintf(
                    '<span class="badge badge-danger"><span id="js-active-records-%d">%d</span> %s</span> ',
                    $cid,
                    $numRecordsByCat[$cid] - $numActiveByCat[$cid],
                    $PMF_LANG['ad_record_inactive']
                );
            }

            if (isset($numCommentsByCat[$cid]) && ($numCommentsByCat[$cid] > 0)) {
                $catInfo .= sprintf('<span class="badge badge-info">%d %s</span>',
                    $numCommentsByCat[$cid],
                    $PMF_LANG['ad_start_comments']
                );
            }
            $catInfo .= '';

            if ($cid != $old) {
                if ($old == 0) {
                    printf('<a name="cat_%d"></a>', $cid);
                } else {
                    echo '</tbody></table></div></div></div>';
                }
                ?>
          <div class="card card-default">
            <div class="card-header" role="tab" id="category-heading-<?= $cid ?>">
              <span class="float-right"><?= $catInfo ?></span>
              <h5>
                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#category-<?= $cid ?>"
                   aria-expanded="true" aria-controls="collapseOne">
                  <i class="icon fa fa-chevron-circle-right "></i>
                    <?= $category->getPath($cid) ?>
                </a>
              </h5>
            </div>
            <div id="category-<?= $cid ?>" class="card-collapse collapse" role="tabcard"
                 aria-labelledby="category-heading-<?= $cid ?>">
              <div class="card-body">
                <table class="table table-hover table-sm">
                  <thead class="thead-light">
                  <tr>
                    <th colspan="2" style="width: 24px;">
                      <a href="?action=view&category=<?= $cid ?>&orderby=id&sortby=desc">
                        &uarr;
                      </a>
                      <a href="?action=view&category=<?= $cid ?>&orderby=id&sortby=asc">
                        &darr;
                      </a>
                    </th>
                    <th>
                      #
                    </th>
                    <th>
                      <a href="?action=view&category=<?= $cid ?>&orderby=title&sortby=desc">
                        &uarr;
                      </a>
                      <a href="?action=view&category=<?= $cid ?>&orderby=title&sortby=asc">
                        &darr;
                      </a>
                    </th>
                    <th style="width: 100px;">
                      <a href="?action=view&category=<?= $cid ?>&orderby=date&sortby=desc">
                        &uarr;
                      </a>
                      <a href="?action=view&category=<?= $cid ?>&orderby=date&sortby=asc">
                        &darr;
                      </a>
                    </th>
                    <th colspan="2">
                      &nbsp;
                    </th>

                    <th style="width: 120px;">
                      <label>
                        <input type="checkbox" id="sticky_category_block_<?= $cid ?>"
                               onclick="saveStatusForCategory(<?= $cid ?>, 'sticky', '<?= $user->getCsrfTokenFromSession() ?>')"/>
                          <?= $PMF_LANG['ad_record_sticky'] ?>
                      </label>
                    </th>
                    <th style="width: 120px;">
                        <?php if ($user->perm->checkRight($user->getUserId(), 'approverec')) { ?>
                          <label>
                            <input type="checkbox" id="active_category_block_<?= $cid ?>"
                                   onclick="saveStatusForCategory(<?= $cid ?>, 'active', '<?= $user->getCsrfTokenFromSession() ?>')"
                                <?= $numRecordsByCat[$cid] == $numActiveByCat[$cid] ? 'checked' : '' ?>>
                              <?= $PMF_LANG['ad_record_active'] ?>
                          </label>
                        <?php } ?>
                    </th>
                    <th colspan="2">
                      &nbsp;
                    </th>
                  </tr>
                  </thead>
                  <tbody>
<?php
            }
            ?>
                    <tr id="record_<?= $record['id'].'_'.$record['lang'] ?>">
                      <td style="width: 24px; text-align: right;">
                        <a href="?action=editentry&id=<?= $record['id'] ?>&lang=<?= $record['lang'] ?>">
                            <?= $record['id'] ?>
                        </a>
                      </td>
                      <td style="width: 16px;">
                          <?= $record['lang'] ?>
                      </td>
                      <td style="width: 24px;">
                        <a href="?action=editentry&id=<?= $record['id'] ?>&lang=<?= $record['lang'] ?>"
                           title="<?= $PMF_LANG['ad_user_edit'] ?> '<?= str_replace('"', '´', $record['title']) ?>'">
                            <?= $record['solution_id'] ?>
                        </a>
                      </td>
                      <td>
                        <a href="?action=editentry&id=<?= $record['id'] ?>&lang=<?= $record['lang'] ?>"
                           title="<?= $PMF_LANG['ad_user_edit'] ?> '<?= str_replace('"', '´', $record['title']) ?>'">
                            <?= $record['title'] ?>
                        </a>
<?php
        if (isset($numCommentsByFaq[$record['id']])) {
            printf(
                '<br><a class="badge badge-primary" href="?action=comments#record_id_%d">%d %s</a>',
                $record['id'],
                $numCommentsByFaq[$record['id']],
                $PMF_LANG['ad_start_comments']
                );
        }
            ?></td>
                      <td>
                          <?= $date->format($record['updated']) ?>
                      </td>
                      <td style="width: 96px;">
                          <?= $linkVerifier->getEntryStateHTML($record['id'], $record['lang']) ?>
                      </td>
                      <td>
                        <div class="dropdown">
                          <a class="btn btn-primary dropdown-toggle" href="#" role="button"
                             id="dropdownAddNewTranslation" data-toggle="dropdown" aria-haspopup="true"
                             aria-expanded="false">
                            <i aria-hidden="true" class="fas fa-globe"></i>
                          </a>
                          <div class="dropdown-menu" aria-labelledby="dropdownAddNewTranslation">
                              <?= $faqHelper->createFaqTranslationLinkList($record['id'], $record['lang']) ?>
                          </div>
                        </div>
                      </td>
                      <td style="width: 56px;">
                        <label>
                          <input type="checkbox" lang="<?= $record['lang'] ?>"
                                 onclick="saveStatus(<?= $cid.', ['.$record['id'].']' ?>, 'sticky', '<?= $user->getCsrfTokenFromSession() ?>');"
                                 id="sticky_record_<?= $cid.'_'.$record['id'] ?>"
                              <?= $record['sticky'] ? 'checked' : '    ' ?>>
                        </label>
                      </td>
                      <td>
                          <?php if ($user->perm->checkRight($user->getUserId(),
                                    'approverec') && isset($numVisits[$record['id']])) { ?>
                            <label>
                              <input type="checkbox" lang="<?= $record['lang'] ?>"
                                     onclick="saveStatus(<?= $cid.', ['.$record['id'].']' ?>, 'active', '<?= $user->getCsrfTokenFromSession() ?>');"
                                     id="active_record_<?= $cid.'_'.$record['id'] ?>"
                                  <?= 'yes' == $record['active'] ? 'checked' : '    ' ?>>
                            </label>
                          <?php } else { ?>
                            <span class="badge badge-important">
                              <i aria-hidden="true" class="fas fa-white fa fa-ban-circle"></i>
                            </span>
                          <?php } ?>
                      </td>
                      <td style="width: 16px;">
                        <a class="btn btn-info"
                           href="?action=copyentry&id=<?= $record['id'] ?>&lang=<?= $record['lang']; ?>"
                           title="<?= $PMF_LANG['ad_categ_copy'] ?>">
                          <i aria-hidden="true" class="fas fa-copy"></i>
                        </a>
                      </td>
                      <td style="width: 16px;">
                        <a class="btn btn-danger" href="javascript:void(0);"
                           onclick="deleteRecord(<?= $record['id'] ?>, '<?= $record['lang'] ?>', '<?= $user->getCsrfTokenFromSession() ?>');"
                           title="<?= $PMF_LANG['ad_user_delete'] ?>">
                          <i aria-hidden="true" class="fas fa-trash"></i>
                        </a>
                      </td>
                    </tr>
<?php
            $old = $cid;

            $faqIds[$cid][] = $record['id'];
            ?>
<?php
        }
        ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
    </form>

    <script src="assets/js/record.js"></script>
    <script>
    /**
     * Saves the sticky record status for the whole category
     *
     * @param id
     * @param type
     * @param csrf
     *
     * @return void
     */
    function saveStatusForCategory(id, type, csrf) {
      let id_map = [];
        <?php
        foreach ($faqIds as $categoryId => $recordIds) {
            if ('' === $categoryId) {
                $categoryId = 0;
            }
            echo '                id_map['.$categoryId.'] = ['.implode(',', $recordIds)."];\n";
        }
        ?>
      for (let i = 0; i < id_map[id].length; i++) {
        const status = $('#' + type + '_category_block_' + id).prop('checked');
        $('#' + type + '_record_' + id + '_' + id_map[id][i]).prop('checked', status);
      }

      saveStatus(id, id_map[id], type, csrf);
    }

    /**
     * Ajax call for saving the sticky record status
     *
     * @param cid  category id
     * @param ids  ids
     * @param type status type
     * @param csrf CSRF Token
     *
     * @return void
     */
    function saveStatus(cid, ids, type, csrf) {
      const indicator = $('#saving_data_indicator'),
        data = {
          action: 'ajax',
          ajax: 'records',
          ajaxaction: 'save_' + type + '_records',
          csrf: csrf
        };

      indicator.html('<i class="fas fa-cog fa-spin"></i> Saving ...');

      for (let i = 0; i < ids.length; i++) {
        const statusId = '#' + type + '_record_' + cid + '_' + ids[i];
        const status = $(statusId).attr('checked') ? '' : 'checked';
        const langId = '#' + type + '_record_' + cid + '_' + ids[i];
        const lang = $(langId).attr('lang');

        data['items[' + i + '][]'] = [ids[i], lang, status];

        // Updating the current record if it's also contained in another category
        const sameRecords = $('input').filter(function () {
          return this.id.match(new RegExp(type + '_record_(\\d+)_' + ids[i]));
        });

        if ('active' === type) {
          for (let j = 0; j < sameRecords.length; j++) {
            $('#' + sameRecords[j].id).attr('checked', status);

            const catid = sameRecords[j].id.match(/active_record_(\d+)_\d+/)[1];
            const current_item_count = $('#js-active-records-' + catid).html();
            const delta = 'checked' === status ? -1 : 1;

            $('#js-active-records-' + catid).html(current_item_count * 1 + delta);
          }
        } else {
          for (let j = 0; j < sameRecords.length; j++) {
            $('#' + sameRecords[j].id).attr('checked', status);

            const catid = sameRecords[j].id.match(/active_record_(\d+)_\d+/)[1];
            const current_item_count = $('#js-active-records-' + catid).html();
            const delta = 'checked' === status ? -1 : 1;

            $('#js-active-records-' + catid).html(current_item_count * 1 + delta);
          }
        }
      }

      $.get("index.php", data, null);
      indicator.html('<?= $PMF_LANG['ad_entry_savedsuc'] ?>');
    }

    /**
     * Ajax call for deleting records
     *
     * @param record_id   Record id
     * @param record_lang Record language
     * @param csrf_token  CSRF Token
     *
     * @return void
     */
    function deleteRecord(record_id, record_lang, csrf_token)
    {
        if (confirm('<?= addslashes($PMF_LANG['ad_entry_del_1'].' '.$PMF_LANG['ad_entry_del_3']);
        ?>')) {
            $('#saving_data_indicator').html('<img src="../assets/svg/spinning-circles.svg"> Deleting ...');
            $.ajax({
                type:    "POST",
                url:     "index.php?action=ajax&ajax=records&ajaxaction=delete_record",
                data:    "record_id=" + record_id + "&record_lang=" + record_lang + "&csrf=" + csrf_token,
                success: function() {
                    $("#record_" + record_id + "_" + record_lang).fadeOut("slow");
                    $('#saving_data_indicator').html('<?= $PMF_LANG['ad_entry_delsuc'];
        ?>');
                }
            });
        }
    }
    </script>
<?php

    } else {
        echo $PMF_LANG['err_nothingFound'];
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
?>
    </div>
</div>
