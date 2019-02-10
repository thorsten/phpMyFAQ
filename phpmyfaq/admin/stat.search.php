<?php
/**
 * Frontend for search log statistics.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2003-03-30
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-tasks"></i> <?php echo $PMF_LANG['ad_menu_searchstats'] ?>
                    <div class="pull-right">
                        <a class="btn btn-danger" href="?action=truncatesearchterms">
                            <i aria-hidden="true" class="fa fa-trash-o fa-fw"></i> <?php echo $PMF_LANG['ad_searchterm_del'] ?>
                        </a>
                    </div>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->checkRight($user->getUserId(), 'viewlog')) {
    $perpage = 15;
    $pages = PMF_Filter::filterInput(INPUT_GET, 'pages', FILTER_VALIDATE_INT);
    $page = PMF_Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 1);

    $search = new PMF_Search($faqConfig);

    if ('truncatesearchterms' === $action) {
        if ($search->deleteAllSearchTerms()) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_searchterm_del_suc']);
        } else {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_searchterm_del_err']);
        }
    }

    $searchesCount = $search->getSearchesCount();
    $searchesList = $search->getMostPopularSearches($searchesCount + 1, true);

    if (is_null($pages)) {
        $pages = round((count($searchesList) + ($perpage / 3)) / $perpage, 0);
    }

    $start = ($page - 1) * $perpage;
    $ende = $start + $perpage;

    $baseUrl = sprintf(
        '%s?action=searchstats&amp;page=%d',
        PMF_Link::getSystemRelativeUri(),
        $page
    );

    // Pagination options
    $options = array(
        'baseUrl' => $baseUrl,
        'total' => count($searchesList),
        'perPage' => $perpage,
        'pageParamName' => 'page',
    );
    $pagination = new PMF_Pagination($faqConfig, $options);
    ?>
                <div id="ajaxresponse"></div>
                <table class="table table-striped">
                <thead>
                <tr>
                    <th><?php echo $PMF_LANG['ad_searchstats_search_term'] ?></th>
                    <th><?php echo $PMF_LANG['ad_searchstats_search_term_count'] ?></th>
                    <th><?php echo $PMF_LANG['ad_searchstats_search_term_lang'] ?></th>
                    <th colspan="2"><?php echo $PMF_LANG['ad_searchstats_search_term_percentage'] ?></th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colspan="6"><?php echo $pagination->render();
    ?></td>
                    </tr>
                </tfoot>
                <tbody>
<?php 

    $counter = $displayedCounter = 0;
    $self = substr(__FILE__, strlen($_SERVER['DOCUMENT_ROOT']));

    foreach ($searchesList as $searchItem) {
        if ($displayedCounter >= $perpage) {
            ++$displayedCounter;
            continue;
        }

        ++$counter;
        if ($counter <= $start) {
            continue;
        }
        ++$displayedCounter;

        $num = round(($searchItem['number'] * 100 / $searchesCount), 2);
        ?>
                    <tr class="row_search_id_<?php echo $searchItem['id'] ?>">
                        <td><?php echo PMF_String::htmlspecialchars($searchItem['searchterm']);
        ?></td>
                        <td><?php echo $searchItem['number'] ?></td>
                        <td><?php echo $languageCodes[PMF_String::strtoupper($searchItem['lang'])] ?></td>
                        <td><meter max="100" value="<?php echo $num;
        ?>"></td>
                        <td><?php echo $num;
        ?>%</td>
                        <td>
                            <a class="btn btn-danger" href="javascript:;" title="<?php echo $PMF_LANG['ad_news_delete'];
        ?>"
                               onclick="deleteSearchTerm('<?php echo $searchItem['searchterm'] ?>', <?php echo $searchItem['id'] ?>); return false;">
                                <i aria-hidden="true" class="fa fa-trash-o"></i>
                            </a>
                        </td>
                    </tr>
<?php

    }
    ?>
                </tbody>
                </table>
                <script>
                /**
                 * Ajax call to delete search term
                 *
                 * @param searchterm
                 * @param searchId
                 */
                function deleteSearchTerm(searchterm, searchId)
                {
                    if (confirm('<?php echo $PMF_LANG['ad_user_del_3'] ?>')) {
                        $.getJSON("index.php?action=ajax&ajax=search&ajaxaction=delete_searchterm&searchterm=" + searchterm,
                        function(response) {
                            if (response == 1) {
                                $('#ajaxresponse').
                                    html('<?php printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_search_delsuc']) ?>');
                                $('.row_search_id_' + searchId).fadeOut('slow');
                            } else {
                                $('#ajaxresponse').
                                    html('<?php printf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_search_delfail']) ?>');
                            }
                        });
                    }
                }
                </script>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
?>
            </div>
        </div>