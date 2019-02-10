<?php
/**
 * The start page with some information about the FAQ.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2013-02-05
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqTableInfo = $faqConfig->getDb()->getTableStatus(PMF_Db::getTablePrefix());
$faqSystem = new PMF_System();
$faqSession = new PMF_Session($faqConfig);
?>
    <header class="row">
        <div class="col-lg-12">
            <h2 class="page-header">
                <div class="pull-right">
                    <a href="?action=config">
                        <?php if ($faqConfig->get('main.maintenanceMode')): ?>
                        <span class="label label-important"><?php print $PMF_LANG['msgMaintenanceMode']; ?></span>
                        <?php else: ?>
                        <span class="label label-success"><?php print $PMF_LANG['msgOnlineMode']; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <i aria-hidden="true" class="fa fa-dashboard fa-fw"></i> <?php echo $PMF_LANG['admin_mainmenu_home'] ?>
            </h2>
        </div>
    </header>

    <?php if ($faqConfig->get('main.enableUserTracking')): ?>
    <section class="row">
        <div class="col-lg-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i aria-hidden="true" class="fa fa-bar-chart-o fa-fw"></i> <?php echo $PMF_LANG['ad_stat_report_visits'] ?>
                </div>
                <div class="panel-body">
                <?php
                $session = new PMF_Session($faqConfig);
                $visits = $session->getLast30DaysVisits();
                ?>
                <script type="text/javascript" src="assets/js/plugins/jquery.sparkline.min.js"></script>
                <script type="text/javascript">
                    $(function() {
                        var visits = [<?php echo implode(',', $visits) ?>];
                        $('.visits').sparkline(
                            visits, {
                                type: 'bar',
                                barColor: '#7797b2',
                                barWidth: window.innerWidth / 66,
                                height: 268,
                                tooltipSuffix: ' <?php echo $PMF_LANG['ad_visits_per_day'] ?>'
                            });
                    });
                </script>
                <span class="visits">Loading...</span>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i aria-hidden="true" class="fa fa-bell fa-fw"></i> <?php echo $PMF_LANG['ad_pmf_info'] ?>
                </div>
                <div class="panel-body">
                    <div class="list-group">
                        <a href="?action=viewsessions" class="list-group-item">
                            <i aria-hidden="true" class="fa fa-bar-chart-o fa-fw"></i> <?php echo $PMF_LANG['ad_start_visits'] ?>
                            <span class="pull-right text-muted small">
                                <em><?php echo $faqSession->getNumberOfSessions() ?></em>
                            </span>
                        </a>
                        <a href="?action=view" class="list-group-item">
                            <i aria-hidden="true" class="fa fa-list-alt fa-fw"></i> <?php echo $PMF_LANG['ad_start_articles']; ?>
                            <span class="pull-right text-muted small">
                                <em><?php echo $faqTableInfo[PMF_Db::getTablePrefix().'faqdata']; ?></em>
                            </span>
                        </a>
                        <a href="?action=comments" class="list-group-item">
                            <i aria-hidden="true" class="fa fa-comment fa-fw"></i> <?php echo $PMF_LANG['ad_start_comments']; ?>
                            <span class="pull-right text-muted small">
                                <em><?php echo $faqTableInfo[PMF_Db::getTablePrefix().'faqcomments']; ?></em>
                            </span>
                        </a>
                        <a href="?action=question" class="list-group-item">
                            <i aria-hidden="true" class="fa fa-question fa-fw"></i> <?php echo $PMF_LANG['msgOpenQuestions']; ?>
                            <span class="pull-right text-muted small">
                                <em><?php echo $faqTableInfo[PMF_Db::getTablePrefix().'faqquestions']; ?></em>
                            </span>
                        </a>
                        <a href="?action=news" class="list-group-item">
                            <i aria-hidden="true" class="fa fa-list-alt fa-fw"></i> <?php echo $PMF_LANG['msgNews']; ?>
                            <span class="pull-right text-muted small">
                                <em><?php echo $faqTableInfo[PMF_Db::getTablePrefix().'faqnews']; ?></em>
                            </span>
                        </a>
                        <a href="?action=user&user_action=listallusers" class="list-group-item">
                            <i aria-hidden="true" class="fa fa-users fa-fw"></i> <?php echo $PMF_LANG['admin_mainmenu_users']; ?>
                            <span class="pull-right text-muted small">
                                <em><?php echo $faqTableInfo[PMF_Db::getTablePrefix().'faquser'] - 1; ?></em>
                            </span>
                        </a>
                        <a target="_blank" href="https://itunes.apple.com/app/phpmyfaq/id977896957" class="list-group-item">
                            <i aria-hidden="true" class="fa fa-apple fa-fw"></i> Available on the App Store
                            <span class="pull-right text-muted small">
                                <i aria-hidden="true" class="fa fa-heart"></i>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php endif; ?>
    <?php if ($user->perm->checkRight($user->getUserId(), 'editconfig')): ?>

    <section class="row">
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i aria-hidden="true" class="fa fa-info-circle fa-fw"></i> <?php print $PMF_LANG['ad_online_info']; ?>
                </div>
                <div class="panel-body">
                    <?php
                    $version = PMF_Filter::filterInput(INPUT_POST, 'param', FILTER_SANITIZE_STRING);
                    if (!is_null($version) && $version == 'version') {
                        $json = file_get_contents('https://api.phpmyfaq.de/versions');
                        $result = json_decode($json);
                        if ($result instanceof stdClass) {
                            $installed = $faqConfig->get('main.currentVersion');
                            $available = $result->stable;
                            printf(
                                '<p class="alert alert-%s">%s <a href="https://www.phpmyfaq.de" target="_blank">phpmyfaq.de</a>:<br/><strong>phpMyFAQ %s</strong>',
                                (-1 == version_compare($installed, $available)) ? 'danger' : 'info',
                                $PMF_LANG['ad_xmlrpc_latest'],
                                $available
                            );
                            // Installed phpMyFAQ version is outdated
                            if (-1 == version_compare($installed, $available)) {
                                print '<br />'.$PMF_LANG['ad_you_should_update'];
                            }
                        }
                    } else {
                        ?>
                        <form action="<?php echo $faqSystem->getSystemUri($faqConfig) ?>admin/index.php" method="post" accept-charset="utf-8">
                            <input type="hidden" name="param" value="version" />
                            <button class="btn btn-primary" type="submit">
                                <i aria-hidden="true" class="fa fa-check fa fa-white"></i> <?php print $PMF_LANG['ad_xmlrpc_button'];
                        ?>
                            </button>
                        </form>
                    <?php

                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i aria-hidden="true" class="fa fa-certificate fa-fw"></i> <?php print $PMF_LANG['ad_online_verification'] ?>
                </div>
                <div class="panel-body">
                    <?php
                    $getJson = PMF_Filter::filterInput(INPUT_POST, 'getJson', FILTER_SANITIZE_STRING);
                    if (!is_null($getJson) && 'verify' === $getJson) {
                        set_error_handler(
                            function ($severity, $message, $file, $line) {
                                throw new ErrorException($message, $severity, $severity, $file, $line);
                            }
                        );

                        $faqSystem = new PMF_System();
                        $localHashes = $faqSystem->createHashes();
                        $versionCheckError = true;
                        try {
                            $remoteHashes = file_get_contents(
                                'https://api.phpmyfaq.de/verify/'.$faqConfig->get('main.currentVersion')
                            );
                            if (!is_array(json_decode($remoteHashes, true))) {
                                $versionCheckError = true;
                            } else {
                                $versionCheckError = false;
                            }
                        } catch (ErrorException $e) {
                            echo '<p class="alert alert-danger">phpMyFAQ version could not be checked.</p>';
                        }

                        restore_error_handler();

                        if ($versionCheckError) {
                            echo '<p class="alert alert-danger">phpMyFAQ version mismatch - no verification possible.</p>';
                        } else {
                            $diff = array_diff(
                                json_decode($localHashes, true),
                                json_decode($remoteHashes, true)
                            );

                            if (1 < count($diff)) {
                                printf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_verification_notokay']);
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
                                printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_verification_okay']);
                            }
                        }
                    } else {
                        ?>
                        <form action="<?php echo $faqSystem->getSystemUri($faqConfig) ?>admin/index.php" method="post" accept-charset="utf-8">
                            <input type="hidden" name="getJson" value="verify" />
                            <button class="btn btn-primary" type="submit">
                                <i aria-hidden="true" class="fa fa-certificate fa fa-white"></i> <?php print $PMF_LANG['ad_verification_button'] ?>
                            </button>
                        </form>
                    <?php

                    }
                    ?>
                    <script>$(function(){ $('span[class="pmf-popover"]').popover();});</script>
                </div>
            </div>
        </div>

        <div style="font-size: 5px; text-align: right; color: #f5f5f5">NOTE: Art is resistance.</div>
    </section>

    <?php endif; ?>