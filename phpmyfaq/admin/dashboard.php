<?php
/**
 * The start page with some information about the FAQ.
 *
 * 
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 *
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Alexander M. Turek <me@derrabus.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link https://www.phpmyfaq.de
 * @since 2013-02-05
 */

use phpMyFAQ\Api;
use phpMyFAQ\Db;
use phpMyFAQ\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Session;
use phpMyFAQ\System;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqTableInfo = $faqConfig->getDb()->getTableStatus(Db::getTablePrefix());
$faqSystem = new System();
$faqSession = new Session($faqConfig);
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
      <h1 class="h2">
        <i aria-hidden="true" class="fas fa-tachometer-alt"></i>
          <?= $PMF_LANG['admin_mainmenu_home'] ?>
      </h1>
      <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
          <a href="?action=config">
              <?php if ($faqConfig->get('main.maintenanceMode')): ?>
                <button class="btn btn-sm btn-outline-danger"><?= $PMF_LANG['msgMaintenanceMode'] ?></button>
              <?php else {
    : ?>
                <button class="btn btn-sm btn-outline-success"><?= $PMF_LANG['msgOnlineMode'] ?></button>
              <?php endif;
}
?>
          </a>
        </div>
      </div>
    </div>

<?php if (version_compare($faqConfig->getCurrentVersion(), System::getVersion(), '<')): ?>
  <div class="alert alert-danger" role="alert">
    <h4 class="alert-heading">Attention!</h4>
    <p>
      The version number stored in your database is lower than your installed version, please update your installation
      <a href="../setup/update.php" class="alert-link">here</a> to avoid an unintended behaviour.
    </p>
  </div>
<?php endif; ?>

    <section class="row">
      <div class="col-lg-12">
        <div class="card-columns">

          <?php if ($faqConfig->get('main.enableUserTracking')): ?>
          <div class="card border-dark">
            <div class="card-header">
              <i aria-hidden="true" class="fas fa-chart-line"></i> <?= $PMF_LANG['ad_stat_report_visits'] ?>
            </div>
            <div class="card-body">
                <?php
                $session = new Session($faqConfig);
                $visits = $session->getLast30DaysVisits();
                ?>
              <script src="assets/js/plugins/jquery.sparkline.min.js"></script>
              <script>
                $(function () {
                  const visits = [<?= implode(',', $visits) ?>];
                  $('.visits').sparkline(
                    visits, {
                      type: 'bar',
                      barColor: '#7797b2',
                      barWidth: 12,
                      height: 268,
                      tooltipSuffix: ' <?= $PMF_LANG['ad_visits_per_day'] ?>'
                    });
                });
              </script>
              <span class="visits">Loading...</span>
            </div>
          </div>
          <?php endif; ?>

          <div class="card border-dark">
            <div class="card-header">
              <i aria-hidden="true" class="fas fa-info-circle"></i> <?= $PMF_LANG['ad_pmf_info'] ?>
            </div>
            <div class="card-body">
              <div class="list-group-flush">
                <a href="?action=viewsessions" class="list-group-item">
                  <i aria-hidden="true" class="fas fa-chart-bar"></i> <?= $PMF_LANG['ad_start_visits'] ?>
                  <span class="float-right text-muted small">
                    <em><?= $faqSession->getNumberOfSessions() ?></em>
                  </span>
                </a>
                <a href="?action=view" class="list-group-item">
                  <i aria-hidden="true" class="fas fa-list-alt"></i> <?= $PMF_LANG['ad_start_articles']; ?>
                  <span class="float-right text-muted small">
                    <em><?= $faqTableInfo[Db::getTablePrefix().'faqdata']; ?></em>
                  </span>
                </a>
                <a href="?action=comments" class="list-group-item">
                  <i aria-hidden="true" class="fas fa-comments"></i> <?= $PMF_LANG['ad_start_comments']; ?>
                  <span class="float-right text-muted small">
                    <em><?= $faqTableInfo[Db::getTablePrefix().'faqcomments']; ?></em>
                  </span>
                </a>
                <a href="?action=question" class="list-group-item">
                  <i aria-hidden="true" class="fas fa-question-circle"></i> <?= $PMF_LANG['msgOpenQuestions']; ?>
                  <span class="float-right text-muted small">
                    <em><?= $faqTableInfo[Db::getTablePrefix().'faqquestions']; ?></em>
                  </span>
                </a>
                <a href="?action=news" class="list-group-item">
                  <i aria-hidden="true" class="fas fa-list-alt"></i> <?= $PMF_LANG['msgNews']; ?>
                  <span class="float-right text-muted small">
                    <em><?= $faqTableInfo[Db::getTablePrefix().'faqnews']; ?></em>
                  </span>
                </a>
                <a href="?action=user&user_action=listallusers" class="list-group-item">
                  <i aria-hidden="true" class="fas fa-users"></i> <?= $PMF_LANG['admin_mainmenu_users']; ?>
                  <span class="float-right text-muted small">
                    <em><?= $faqTableInfo[Db::getTablePrefix().'faquser'] - 1; ?></em>
                  </span>
                </a>
                <a target="_blank" href="https://itunes.apple.com/app/phpmyfaq/id977896957" class="list-group-item">
                   Available on the App Store
                  <span class="float-right text-muted small"><i aria-hidden="true" class="fas fa-heart"></i></span>
                </a>
              </div>
            </div>
          </div>

          <div class="card border-dark">
            <div class="card-header">
              <i aria-hidden="true" class="fas fa-ban"></i> <?= $PMF_LANG['ad_record_inactive']; ?>
            </div>
            <div class="card-body">
              <ul class="list-unstyled">
              <?php
                $inactiveFaqs = $faq->getInactiveFaqsData();
                foreach ($inactiveFaqs as $inactiveFaq) {
                printf(
                    '<li><a href="%s">%s</a></li>',
                    $inactiveFaq['url'],
                    $inactiveFaq['question']
                );
                }
                ?>
              </ul>
            </div>
          </div>

          <?php if ($user->perm->checkRight($user->getUserId(), 'editconfig')): ?>
            <div class="card border-dark">
            <div class="card-header">
              <i aria-hidden="true" class="fas fa-check-double"></i> <?= $PMF_LANG['ad_online_info']; ?>
            </div>
            <div class="card-body">
                <?php
                $version = Filter::filterInput(INPUT_POST, 'param', FILTER_SANITIZE_STRING);
                if ($faqConfig->get('main.enableAutoUpdateHint') || (!is_null($version) && $version == 'version')) {
                    $api = new Api($faqConfig, new System());
                    try {
                        $versions = $api->getVersions();
                        printf(
                            '<p class="alert alert-%s">%s <a href="https://www.phpmyfaq.de" target="_blank">phpmyfaq.de</a>: <strong>phpMyFAQ %s</strong>',
                            (-1 == version_compare($versions['installed'], $versions['current'])) ? 'danger' : 'dark',
                            $PMF_LANG['ad_xmlrpc_latest'],
                            $versions['current']
                        );
                        // Installed phpMyFAQ version is outdated
                        if (-1 == version_compare($versions['installed'], $versions['current'])) {
                            echo '<br />'.$PMF_LANG['ad_you_should_update'];
                        }
                    } catch (Exception $e) {
                        printf('<p class="alert alert-danger">%s</p>', $e->getMessage());
                    }
                } else {
                    ?>
                  <form action="<?= $faqSystem->getSystemUri($faqConfig) ?>admin/index.php" method="post"
                        accept-charset="utf-8">
                    <input type="hidden" name="param" value="version"/>
                    <button class="btn btn-primary" type="submit">
                      <i aria-hidden="true" class="fas fa-check fa fa-white"></i> <?= $PMF_LANG['ad_xmlrpc_button'];
                        ?>
                    </button>
                  </form>
                    <?php
                }
                ?>
            </div>
          </div>

           <div class="card border-dark">
            <div class="card-header">
              <i aria-hidden="true" class="fas fa-certificate fa-fw"></i> <?= $PMF_LANG['ad_online_verification'] ?>
            </div>
            <div class="card-body">
                <?php
                $getJson = Filter::filterInput(INPUT_POST, 'getJson', FILTER_SANITIZE_STRING);
                if (!is_null($getJson) && 'verify' === $getJson) {
                    $api = new Api($faqConfig, new System());
                    try {
                        if (!$api->isVerified()) {
                            echo '<p class="alert alert-danger">phpMyFAQ version mismatch - no verification possible.</p>';
                        } else {
                            $issues = $api->getVerificationIssues();
                            if (1 < count($issues)) {
                                printf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_verification_notokay']);
                                echo '<ul>';
                                foreach ($issues as $file => $hash) {
                                    if ('created' === $file) {
                                        continue;
                                    }
                                    printf(
                                        '<li><span class="pmf-popover" data-original-title="SHA-1" data-content="%s">%s</span></li>',
                                        $hash,
                                        $file
                                    );
                                }
                                echo '</ul>';
                            } else {
                                printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_verification_okay']);
                            }
                        }
                    } catch (Exception $e) {
                        printf('<p class="alert alert-danger">%s</p>', $e->getMessage());
                    }
                } else {
                    ?>
                  <form action="<?= $faqSystem->getSystemUri($faqConfig) ?>admin/index.php" method="post"
                        accept-charset="utf-8">
                    <input type="hidden" name="getJson" value="verify"/>
                    <button class="btn btn-primary" type="submit">
                      <i aria-hidden="true"
                         class="fas fa-certificate fa fa-white"></i> <?= $PMF_LANG['ad_verification_button'] ?>
                    </button>
                  </form>
                    <?php
                }
                ?>
              <script>$(function () {
                  $('span[class="pmf-popover"]').popover();
                });</script>
            </div>
          </div>
          <?php endif; ?>

        </div>
    </section>

