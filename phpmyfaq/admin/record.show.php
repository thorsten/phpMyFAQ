<?php

/**
 * Shows the list of records ordered by categories.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Comments;
use phpMyFAQ\Date;
use phpMyFAQ\Database;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Search\SearchFactory;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Visits;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fa fa-list-alt"></i>
              <?= Translation::get('ad_entry_aor') ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->hasPermission($user->getUserId(), 'edit_faq') || $user->perm->hasPermission($user->getUserId(), 'delete_faq')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->transform(0);
    $category->buildCategoryTree();

    $categoryHelper = new CategoryHelper();
    $categoryHelper->setCategory($category);

    $categoryRelation = new CategoryRelation($faqConfig, $category);
    $categoryRelation->setGroups($currentAdminGroups);

    $faqHelper = new FaqHelper($faqConfig);

    $faq = new Faq($faqConfig);
    $faq->setUser($currentAdminUser);
    $faq->setGroups($currentAdminGroups);
    $date = new Date($faqConfig);

    $internalSearch = '';
    $searchCat = Filter::filterInput(INPUT_POST, 'searchcat', FILTER_VALIDATE_INT);
    $searchTerm = Filter::filterInput(INPUT_POST, 'searchterm', FILTER_SANITIZE_SPECIAL_CHARS);

    if (!is_null($searchCat)) {
        $internalSearch .= '&searchcat=' . $searchCat;
        $cond[Database::getTablePrefix() . 'faqcategoryrelations.category_id'] = array_merge(
            [$searchCat],
            $category->getChildNodes((int) $searchCat)
        );
    }

    $selectedCategory = Filter::filterInput(INPUT_GET, 'category', FILTER_VALIDATE_INT, 0);
    $orderBy = Filter::filterInput(INPUT_GET, 'orderby', FILTER_SANITIZE_SPECIAL_CHARS, 1);
    $sortBy = Filter::filterInput(INPUT_GET, 'sortby', FILTER_SANITIZE_SPECIAL_CHARS);
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
    $comment = new Comments($faqConfig);
    $numCommentsByFaq = $comment->getNumberOfComments();
    $numCommentsByCat = [];
    $numRecordsByCat = $categoryRelation->getNumberOfFaqsPerCategory();

    $numActiveByCat = [];

    $csrfToken = Token::getInstance()->getTokenString('faq-overview');

    $matrix = $categoryRelation->getCategoryFaqsMatrix();
    foreach ($matrix as $categoryKey => $value) {
        $numCommentsByCat[$categoryKey] = 0;
        foreach ($value as $faqKey => $innerValue) {
            if (isset($numCommentsByFaq[$faqKey])) {
                $numCommentsByCat[$categoryKey] += $numCommentsByFaq[$faqKey];
            }
        }
    }

    if (is_null($searchTerm)) {
        if ($faqConfig->get('main.enableCategoryRestrictions')) {
            $Language = new Language($faqConfig);
            $language = $Language->setLanguage(
                $faqConfig->get('main.languageDetection'),
                $faqConfig->get('main.language')
            );
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
        $fdTable = Database::getTablePrefix() . 'faqdata';
        $fcrTable = Database::getTablePrefix() . 'faqcategoryrelations';
        $search = SearchFactory::create($faqConfig, ['database' => Database::getType()]);

        $search
            ->setTable($fdTable)
            ->setResultColumns(
                [
                    $fdTable . '.id AS id',
                    $fdTable . '.lang AS lang',
                    $fdTable . '.solution_id AS solution_id',
                    $fcrTable . '.category_id AS category_id',
                    $fdTable . '.sticky AS sticky',
                    $fdTable . '.active AS active',
                    $fdTable . '.thema AS thema',
                    $fdTable . '.content AS content',
                    $fdTable . '.updated AS updated',
                ]
            )
            ->setJoinedTable($fcrTable)
            ->setJoinedColumns(
                [
                    $fdTable . '.id = ' . $fcrTable . '.record_id',
                    $fdTable . '.lang = ' . $fcrTable . '.record_lang',
                ]
            );

        if (is_numeric($searchTerm) && $faqConfig->get('search.searchForSolutionId')) {
            $search->setMatchingColumns([$fdTable . '.solution_id']);
        } else {
            $search->setMatchingColumns([$fdTable . '.thema', $fdTable . '.content', $fdTable . '.keywords']);
        }

        $result = $search->search($searchTerm);
        $laction = 'view';
        $internalSearch = '&search=' . $searchTerm;
        $wasSearch = true;
        $idsFound = [];
        $faqsFound = [];

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
        foreach ($faqsFound as $faqFound) {
            foreach ($faqFound as $singleFaq) {
                $faq->faqRecords[] = $singleFaq;
            }
        }
    }

    if (count($faq->faqRecords) > 0) {
        $old = -1;
        $faqIds = [];

        $visits = new Visits($faqConfig);
        $numVisits = [];
        foreach ($visits->getAllData() as $visit) {
            $numVisits[$visit['id']] = $visit['lang'];
        }

        foreach ($faq->faqRecords as $record) {
            $catInfo = '';
            $cid = $record['category_id'];

            if (is_null($cid)) {
                $cid = 0;
            }

            if (isset($numRecordsByCat[$cid])) {
                $catInfo .= sprintf(
                    '<span class="badge bg-primary" id="category_%d_item_count">%d %s</span> ',
                    $cid,
                    $numRecordsByCat[$cid],
                    Translation::get('msgEntries')
                );
            }

            if (isset($numRecordsByCat[$cid]) && $numRecordsByCat[$cid] > $numActiveByCat[$cid]) {
                $catInfo .= sprintf(
                    '<span class="badge bg-danger"><span id="js-active-records-%d">%d</span> %s</span> ',
                    $cid,
                    $numRecordsByCat[$cid] - $numActiveByCat[$cid],
                    Translation::get('ad_record_inactive')
                );
            }

            if (isset($numCommentsByCat[$cid]) && ($numCommentsByCat[$cid] > 0)) {
                $catInfo .= sprintf(
                    '<span class="badge bg-secondary">%d %s</span>',
                    $numCommentsByCat[$cid],
                    Translation::get('ad_start_comments')
                );
            }
            $catInfo .= '';

            if ($cid !== $old) {
                if ($old === -1) {
                    printf('<a id="cat_%d"></a>', $cid);
                } else {
                    echo '</tbody></table></div></div></div>';
                }
                ?>
          <div class="card card-default mb-1">
            <div class="card-header bg-light" role="tab" id="category-heading-<?= $cid ?>">
              <span class="float-right"><?= $catInfo ?></span>
              <h5>
                <a role="button" data-bs-toggle="collapse" data-parent="#accordion" href="#category-<?= $cid ?>"
                   aria-expanded="true" aria-controls="collapseOne" class="text-decoration-none">
                  <i class="icon fa fa-chevron-circle-right "></i>
                <?= $cid > 0 ? Strings::htmlentities($category->getPath($cid)) : Translation::get('err_noHeaders') ?>
                </a>
              </h5>
            </div>
            <div id="category-<?= $cid ?>" class="card-collapse collapse" role="tabcard"
                 aria-labelledby="category-heading-<?= $cid ?>">
              <div class="card-body">
                <table class="table table-hover table-sm align-middle">
                  <thead class="thead-light">
                  <tr>
                    <th colspan="3" style="width: 24px; vertical-align: middle;">
                      <div style="display: inline-flex; flex-direction: column;">
                        <a href="?action=view&category=<?= $cid ?>&orderby=id&sortby=asc">
                          <i class="fa fa-sort-asc" aria-hidden="true"></i>
                        </a>
                        ID
                        <a href="?action=view&category=<?= $cid ?>&orderby=id&sortby=desc">
                          <i class="fa fa-sort-desc" aria-hidden="true"></i>
                        </a>
                      </div>
                    </th>
                    <th style="vertical-align: middle;">
                      <div style="display: inline-flex; flex-direction: column;">
                        <a href="?action=view&category=<?= $cid ?>&orderby=title&sortby=asc">
                          <i class="fa fa-sort-asc" aria-hidden="true"></i>
                        </a>
                        <?= Translation::get('ad_entry_theme') ?>
                        <a href="?action=view&category=<?= $cid ?>&orderby=title&sortby=desc">
                          <i class="fa fa-sort-desc" aria-hidden="true"></i>
                        </a>
                      </div>
                    </th>
                    <th style="width: 100px; vertical-align: middle;">
                      <div style="display: inline-flex; flex-direction: column;">
                        <a href="?action=view&category=<?= $cid ?>&orderby=date&sortby=asc">
                          <i class="fa fa-sort-asc" aria-hidden="true"></i>
                        </a>
                        <?= Translation::get('ad_entry_date') ?>
                        <a href="?action=view&category=<?= $cid ?>&orderby=date&sortby=desc">
                          <i class="fa fa-sort-desc" aria-hidden="true"></i>
                        </a>
                      </div>
                    </th>
                    <th>
                    </th>
                    <th class="align-middle">
                      <div class="form-check">
                        <input class="form-check-input pmf-admin-faqs-all-sticky" type="checkbox" value=""
                               data-pmf-category-id="<?= $cid ?>" data-pmf-csrf="<?= $csrfToken ?>"
                               id="sticky_category_block_<?= $cid ?>">
                        <label class="form-check-label" for="sticky_category_block_<?= $cid ?>">
                          <?= Translation::get('ad_record_sticky') ?>
                        </label>
                      </div>
                    </th>
                    <th class="align-middle" style="width: 120px;">
                    <?php if ($user->perm->hasPermission($user->getUserId(), 'approverec')) { ?>
                        <div class="form-check">
                            <input class="form-check-input pmf-admin-faqs-all-active" type="checkbox" value=""
                                   data-pmf-category-id="<?= $cid ?>" data-pmf-csrf="<?= $csrfToken ?>"
                                   id="active_category_block_<?= $cid ?>"
                                <?php
                                if (
                                    isset($numRecordsByCat[$cid]) && isset($numActiveByCat[$cid]) &&
                                    $numRecordsByCat[$cid] === $numActiveByCat[$cid]
                                ) {
                                    echo 'checked';
                                }
                                ?>>
                            <label class="form-check-label" for="sticky_category_block_<?= $cid ?>">
                                <?= Translation::get('ad_record_active') ?>
                            </label>
                        </div>
                    <?php } else { ?>
                          <span class="fa-stack">
                              <i class="fa fa-check fa-stack-1x"></i>
                              <i class="fa fa-ban fa-stack-2x text-danger"></i>
                            </span>
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
                    <tr id="record_<?= $record['id'] . '_' . $record['lang'] ?>">
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
                           title="<?= Translation::get('ad_user_edit') ?> '<?= str_replace('"', '´', (string) $record['title']) ?>'">
                        <?= $record['solution_id'] ?>
                        </a>
                      </td>
                      <td>
                        <a href="?action=editentry&id=<?= $record['id'] ?>&lang=<?= $record['lang'] ?>"
                           title="<?= Translation::get('ad_user_edit') ?> '
                           <?= str_replace('"', '´', Strings::htmlentities($record['title'])) ?>'">
                        <?= Strings::htmlentities($record['title']) ?>
                        </a>
            <?php
            if (isset($numCommentsByFaq[$record['id']])) {
                printf(
                    '<br><a class="badge bg-primary" href="?action=comments#record_id_%d">%d %s</a>',
                    $record['id'],
                    $numCommentsByFaq[$record['id']],
                    Translation::get('ad_start_comments')
                );
            }
            ?></td>
                      <td>
                        <small><?= $date->format($record['updated']) ?></small>
                      </td>
                      <td>
                        <div class="dropdown">
                          <a class="btn btn-primary dropdown-toggle" href="#" role="button"
                             id="dropdownAddNewTranslation" data-bs-toggle="dropdown" aria-haspopup="true"
                             aria-expanded="false">
                            <i aria-hidden="true" class="fa fa-globe"
                               title="<?= Translation::get('msgTransToolAddNewTranslation') ?>"></i>
                          </a>
                          <div class="dropdown-menu" aria-labelledby="dropdownAddNewTranslation">
                          <?= $faqHelper->createFaqTranslationLinkList($record['id'], $cid, $record['lang']) ?>
                          </div>
                        </div>
                      </td>
                      <td class="align-middle">
                          <div>
                              <input class="form-check-input pmf-admin-sticky-faq" type="checkbox"
                                     data-pmf-category-id-sticky="<?= $cid ?>" data-pmf-faq-id="<?= $record['id'] ?>"
                                     data-pmf-csrf="<?= $csrfToken ?>" lang="<?= $record['lang'] ?>"
                                     id="sticky_record_<?= $cid . '_' . $record['id'] ?>"
                                     <?= $record['sticky'] ? 'checked' : '' ?>>
                          </div>
                      </td>
                      <td>
                      <?php if ($user->perm->hasPermission($user->getUserId(), 'approverec')) { ?>
                          <div>
                              <input class="form-check-input pmf-admin-active-faq" type="checkbox"
                                     data-pmf-category-id-active="<?= $cid ?>" data-pmf-faq-id="<?= $record['id'] ?>"
                                     data-pmf-csrf="<?= $csrfToken ?>" lang="<?= $record['lang'] ?>"
                                     id="active_record_<?= $cid . '_' . $record['id'] ?>"
                                     <?= 'yes' == $record['active'] ? 'checked' : '    ' ?>>
                          </div>
                      <?php } else { ?>
                            <span class="fa-stack">
                              <i class="fa fa-check fa-stack-1x"></i>
                              <i class="fa fa-ban fa-stack-2x text-danger"></i>
                            </span>
                      <?php } ?>
                      </td>
                      <td style="width: 16px;">
                        <a class="btn btn-info"
                           href="?action=copyentry&id=<?= $record['id'] ?>&lang=<?= $record['lang']; ?>"
                           title="<?= Translation::get('ad_categ_copy') ?>">
                          <i aria-hidden="true" class="fa fa-copy"></i>
                        </a>
                      </td>
                      <td style="width: 16px;">
                        <button class="btn btn-danger pmf-button-delete-faq" type="button"
                                data-pmf-id="<?= $record['id'] ?>" data-pmf-language="<?= $record['lang'] ?>"
                                data-pmf-token="<?= $csrfToken ?>">
                            <?= Translation::get('ad_user_delete') ?>
                        </button>
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
<?php
    } else {
        echo Translation::get('err_nothingFound');
    }
} else {
    echo Translation::get('err_NotAuth');
}
?>
    </div>
</div>
