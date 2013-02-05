<?php
/**
 * The start page with some information about the FAQ
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2013-02-05
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

//
$faqTableInfo = $faqConfig->getDb()->getTableStatus();
$faqSystem = new PMF_System();
?>
    <header>
        <h2>
            <div class="pull-right">
                <a href="?action=config">
                    <?php if ($faqConfig->get('main.maintenanceMode')): ?>
                    <span class="label label-important"><?php print $PMF_LANG['msgMaintenanceMode']; ?></span>
                    <?php else: ?>
                    <span class="label label-success"><?php print $PMF_LANG['msgOnlineMode']; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <i class="icon-dashboard"></i> <?php print $PMF_LANG['ad_pmf_info']; ?>
        </h2>
    </header>

    <section class="row-fluid">
        <div class="dashboard-stat span2">
            <span><a href="?action=viewsessions"><?php echo $PMF_LANG['ad_start_visits'] ?></a></span>
            <?php echo $faqTableInfo[PMF_Db::getTablePrefix() . 'faqsessions']; ?>
        </div>
        <div class="dashboard-stat span2">
            <span><a href="?action=view"><?php echo $PMF_LANG["ad_start_articles"]; ?></a></span>
            <?php echo $faqTableInfo[PMF_Db::getTablePrefix() . "faqdata"]; ?>
        </div>
        <div class="dashboard-stat span2">
            <span><a href="?action=comments"><?php echo $PMF_LANG["ad_start_comments"]; ?></a></span>
            <?php echo $faqTableInfo[PMF_Db::getTablePrefix() . "faqcomments"]; ?>
        </div>
        <div class="dashboard-stat span2">
            <span><a href="?action=question"><?php echo $PMF_LANG["msgOpenQuestions"]; ?></a></span>
            <?php echo $faqTableInfo[PMF_Db::getTablePrefix() . "faqquestions"]; ?>
        </div>
        <div class="dashboard-stat span2">
            <span><a href="?action=news"><?php echo $PMF_LANG["msgNews"]; ?></a></span>
            <?php echo $faqTableInfo[PMF_Db::getTablePrefix() . "faqnews"]; ?>
        </div>
        <div class="dashboard-stat span2">
            <span><a href="?action=user&user_action=listallusers"><?php echo $PMF_LANG['admin_mainmenu_users']; ?></a></span>
            <?php echo $faqTableInfo[PMF_Db::getTablePrefix() . 'faquser'] - 1; ?>
        </div>
    </section>

    <section class="row-fluid">
        <div class="span12">
            <header>
                <h3><?php echo $PMF_LANG["ad_stat_report_visits"] ?></h3>
            </header>
            <?php
            $session = new PMF_Session($faqConfig);
            $visits  = $session->getLast30DaysVisits();
            ?>
            <script type="text/javascript" src="../assets/js/plugins/jquery.sparkline.min.js"></script>
            <script type="text/javascript">
                $(function() {
                    var visits = [<?php echo implode(',', $visits) ?>];
                    $('.visits').sparkline(
                        visits, {
                            type: 'bar',
                            barColor: '#fbc372',
                            barWidth: 32,
                            height: 200,
                            tooltipSuffix: ' <?php echo $PMF_LANG["ad_visits_per_day"] ?>'
                        });
                });
            </script>
            <span class="visits">Loading...</span>
        </div>
    </section>

    <section class="row-fluid">
        <div class="span6">
            <header>
                <h3><?php print $PMF_LANG['ad_online_info']; ?></h3>
            </header>
            <?php
            $version = PMF_Filter::filterInput(INPUT_POST, 'param', FILTER_SANITIZE_STRING);
            if (!is_null($version) && $version == 'version') {
                $json   = file_get_contents('http://www.phpmyfaq.de/api/version');
                $result = json_decode($json);
                if ($result instanceof stdClass) {
                    $installed = $faqConfig->get('main.currentVersion');
                    $available = $result->stable;
                    printf(
                        '<p class="alert alert-%s">%s <a href="http://www.phpmyfaq.de" target="_blank">phpmyfaq.de</a>:<br/><strong>phpMyFAQ %s</strong>',
                        (-1 == version_compare($installed, $available)) ? 'danger' : 'info',
                        $PMF_LANG['ad_xmlrpc_latest'],
                        $available
                    );
                    // Installed phpMyFAQ version is outdated
                    if (-1 == version_compare($installed, $available)) {
                        print '<br />' . $PMF_LANG['ad_you_should_update'];
                    }
                }
            } else {
                ?>
                <p>
                <form action="index.php" method="post">
                    <input type="hidden" name="param" value="version" />
                    <button class="btn btn-primary" type="submit">
                        <i class="icon-check icon-white"></i> <?php print $PMF_LANG["ad_xmlrpc_button"]; ?>
                    </button>
                </form>
                </p>
            <?php
            }
            ?>
            </p>
        </div>
        <div class="span6">
            <header>
                <h3><?php print $PMF_LANG['ad_online_verification'] ?></h3>
            </header>
            <?php
            $getJson = PMF_Filter::filterInput(INPUT_POST, 'getJson', FILTER_SANITIZE_STRING);
            if (!is_null($getJson) && 'verify' === $getJson) {

                $faqSystem    = new PMF_System();
                $localHashes  = $faqSystem->createHashes();
                $remoteHashes = file_get_contents(
                    'http://www.phpmyfaq.de/api/verify/' . $faqConfig->get('main.currentVersion')
                );

                if (!is_array(json_decode($remoteHashes, true))) {
                    echo '<p class="alert alert-danger">phpMyFAQ version mismatch - no verification possible.</p>';
                } else {

                    $diff = array_diff(
                        json_decode($localHashes, true),
                        json_decode($remoteHashes, true)
                    );

                    if (1 < count($diff)) {
                        printf('<p class="alert alert-danger">%s</p>', $PMF_LANG["ad_verification_notokay"]);
                        print '<ul>';
                        foreach ($diff as $file => $hash) {
                            if ('created' === $file) {
                                continue;
                            }
                            printf(
                                '<li><span class="pmf-popover" data-original-title="SHA-1" data-content="%s">%s</span></li>',
                                $hash,
                                $file
                            );
                        }
                        print '</ul>';
                    } else {
                        printf('<p class="alert alert-success">%s</p>', $PMF_LANG["ad_verification_okay"]);
                    }
                }

            } else {
                ?>
                <p>
                <form action="index.php" method="post">
                    <input type="hidden" name="getJson" value="verify" />
                    <button class="btn btn-primary" type="submit">
                        <i class="icon-certificate icon-white"></i> <?php print $PMF_LANG["ad_verification_button"] ?>
                    </button>
                </form>
                </p>
            <?php
            }
            ?>
            <script>$(function(){ $('span[class="pmf-popover"]').popover();});</script>
        </div>

        <div style="font-size: 5px; text-align: right; color: #f5f5f5">NOTE: Art is resistance.</div>
    </section>
