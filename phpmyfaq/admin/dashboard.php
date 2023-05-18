<?php

/**
 * The start page with some information about the FAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-02-05
 */

use phpMyFAQ\Api;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\Session;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$faqTableInfo = $faqConfig->getDb()->getTableStatus(Database::getTablePrefix());
$faqSystem = new System();
$faqSession = new Session($faqConfig);
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-tachometer"></i>
      <?= Translation::get('admin_mainmenu_home') ?>
  </h1>
  <div class="btn-toolbar mb-2 mb-md-0">
    <div class="btn-group mr-2">
      <a href="?action=config">
          <?php if ($faqConfig->get('main.maintenanceMode')) : ?>
            <button class="btn btn-sm btn-danger"><?= Translation::get('msgMaintenanceMode') ?></button>
          <?php else : ?>
            <button class="btn btn-sm btn-success"><?= Translation::get('msgOnlineMode') ?></button>
          <?php endif; ?>
      </a>
    </div>
  </div>
</div>

<?php if (version_compare($faqConfig->getVersion(), System::getVersion(), '<')) : ?>
  <section class="row mb-3">
    <div class="col-12">
      <div class="card bg-danger text-white shadow h-100 py-2">
        <div class="card-header text-uppercase">Attention!</div>
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="mb-0 font-weight-bold text-gray-800">
                The phpMyFAQ version number stored in your database (<?= $faqConfig->getVersion() ?>) is lower
                than the version number of the installed application (<?= System::getVersion() ?>), please update
                <a href="../setup/update.php" class="text-white-50">your installation here</a> to avoid an unintended
                behaviour.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if (System::isDevelopmentVersion()) : ?>
<section class="row">
    <div class="col">
        <div class="alert alert-danger" role="alert">
            <h5 class="alert-heading">
                Attention!
            </h5>
            <p>
                phpMyFAQ is currently in development (<?= System::getVersion() ?>) And therefore not yet ready for
                production.
                Please <a target="_blank" href="https://github.com/thorsten/phpMyFAQ/issues" class="alert-link">
                    report all issues on Github
                </a>. Thank you very much!
            </p>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="row masonry-grid">
    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card mb-4">
        <h5 class="card-header py-3">
          <i aria-hidden="true" class="fa fa-info-circle"></i> <?= Translation::get('ad_pmf_info') ?>
        </h5>
        <div class="card-body">
          <div class="list-group-flush">
            <a href="?action=viewsessions" class="list-group-item d-flex justify-content-between align-items-start">
              <div class="ms-2 me-auto">
                <i aria-hidden="true" class="fa fa-bar-chart"></i> <?= Translation::get('ad_start_visits') ?>
              </div>
              <span class="badge bg-primary rounded-pill">
                <?= $faqSession->getNumberOfSessions() ?>
              </span>
            </a>
            <a href="?action=view" class="list-group-item d-flex justify-content-between align-items-start">
              <div class="ms-2 me-auto">
                <i aria-hidden="true" class="fa fa-list-alt"></i> <?= Translation::get('ad_start_articles') ?>
              </div>
              <span class="badge bg-primary rounded-pill">
                <?= $faqTableInfo[Database::getTablePrefix() . 'faqdata']; ?>
              </span>
            </a>
            <a href="?action=comments" class="list-group-item d-flex justify-content-between align-items-start">
              <div class="ms-2 me-auto">
                <i aria-hidden="true" class="fa fa-comments"></i> <?= Translation::get('ad_start_comments') ?>
              </div>
              <span class="badge bg-primary rounded-pill">
                <?= $faqTableInfo[Database::getTablePrefix() . 'faqcomments']; ?>
              </span>
            </a>
            <a href="?action=question" class="list-group-item d-flex justify-content-between align-items-start">
              <div class="ms-2 me-auto">
                <i aria-hidden="true" class="fa fa-question-circle"></i> <?= Translation::get('msgOpenQuestions') ?>
              </div>
                <span class="badge bg-primary rounded-pill">
                    <?= $faqTableInfo[Database::getTablePrefix() . 'faqquestions']; ?>
              </span>
            </a>
            <a href="?action=news" class="list-group-item d-flex justify-content-between align-items-start">
              <div class="ms-2 me-auto">
                <i aria-hidden="true" class="fa fa-list-alt"></i> <?= Translation::get('msgNews') ?>
              </div>
              <span class="badge bg-primary rounded-pill">
                <?= $faqTableInfo[Database::getTablePrefix() . 'faqnews']; ?>
              </span>
            </a>
            <a href="?action=user&user_action=listallusers"
               class="list-group-item d-flex justify-content-between align-items-start">
              <div class="ms-2 me-auto">
                <i aria-hidden="true" class="fa fa-users"></i> <?= Translation::get('admin_mainmenu_users') ?>
              </div>
              <span class="badge bg-primary rounded-pill">
                <?= $faqTableInfo[Database::getTablePrefix() . 'faquser'] - 1; ?>
              </span>
            </a>
          </div>
        </div>
      </div>
    </div>

      <?php if ($faqConfig->get('main.enableUserTracking')) : ?>
        <div class="col-sm-12 col-lg-6 mb-4">
          <div class="card mb-4">
            <h5 class="card-header py-3">
              <i aria-hidden="true" class="fa fa-bar-chart"></i> <?= Translation::get('ad_stat_report_visits') ?>
            </h5>
            <div class="card-body">
              <canvas id="pmf-chart-visits" width="400" height="300"></canvas>
            </div>
          </div>
        </div>
      <?php endif; ?>

    <div class="col-sm-6 col-lg-3 mb-4">
      <div class="card mb-4">
        <h5 class="card-header py-3">
          <i aria-hidden="true" class="fa fa-ban"></i> <?= Translation::get('ad_record_inactive') ?>
        </h5>
        <div class="card-body">
          <ul class="list-unstyled">
              <?php
                $inactiveFaqs = $faq->getInactiveFaqsData();
                if ((is_countable($inactiveFaqs) ? count($inactiveFaqs) : 0) > 0) {
                    foreach ($inactiveFaqs as $inactiveFaq) {
                        printf(
                            '<li><i class="fa fa-question-circle"></i> <a href="%s">%s</a></li>',
                            Strings::htmlentities($inactiveFaq['url']),
                            Strings::htmlentities($inactiveFaq['question'])
                        );
                    }
                } else {
                    echo '<li>n/a</li>';
                }
                ?>
          </ul>
        </div>
      </div>
    </div>

        <?php if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) : ?>
    <div class="col-sm-6 col-lg-3 mb-4">
          <div class="card mb-4">
            <h5 class="card-header py-3">
              <i aria-hidden="true" class="fa fa-check"></i> <?= Translation::get('ad_online_info'); ?>
            </h5>
            <div class="card-body">
                <?php
                $version = Filter::filterInput(INPUT_POST, 'param', FILTER_SANITIZE_SPECIAL_CHARS);
                if ($faqConfig->get('main.enableAutoUpdateHint') || ($version == 'version')) {
                    $api = new Api($faqConfig);
                    try {
                        $versions = $api->getVersions();
                        printf(
                            '<p class="alert alert-%s">%s <a href="%s" target="_blank">phpmyfaq.de</a>: <strong>phpMyFAQ %s</strong>',
                            (-1 == version_compare($versions['installed'], $versions['current'])) ? 'danger' : 'info',
                            Translation::get('ad_xmlrpc_latest'),
                            System::PHPMYFAQ_URL,
                            $versions['current']
                        );
                        // Installed phpMyFAQ version is outdated
                        if (-1 == version_compare($versions['installed'], $versions['current'])) {
                            echo '<br>' . Translation::get('ad_you_should_update');
                        }
                    } catch (DecodingExceptionInterface | TransportExceptionInterface | Exception $e) {
                        printf('<p class="alert alert-danger">%s</p>', $e->getMessage());
                    }
                } else {
                    ?>
                  <form action="<?= Strings::htmlentities($faqSystem->getSystemUri($faqConfig)) ?>admin/index.php"
                        method="post" accept-charset="utf-8">
                    <input type="hidden" name="param" value="version"/>
                    <button class="btn btn-info" type="submit">
                        <?= Translation::get('ad_xmlrpc_button') ?>
                    </button>
                  </form>
                    <?php
                }
                ?>
            </div>
          </div>
    </div>

    <div class="col-sm-6 col-lg-3 mb-4">
          <div class="card mb-4">
            <h5 class="card-header py-3">
              <i aria-hidden="true" class="fa fa-certificate fa-fw"></i> <?= Translation::get('ad_online_verification') ?>
            </h5>
            <div class="card-body">
                <?php
                $getJson = Filter::filterInput(INPUT_POST, 'getJson', FILTER_SANITIZE_SPECIAL_CHARS);
                if ('verify' === $getJson) {
                    $api = new Api($faqConfig);
                    try {
                        if (!$api->isVerified()) {
                            echo '<p class="alert alert-danger">phpMyFAQ version mismatch - no verification possible.</p>';
                        } else {
                            $issues = $api->getVerificationIssues();
                            if (1 < count($issues)) {
                                echo Alert::danger('ad_verification_notokay');
                                echo '<ul>';
                                foreach ($issues as $file => $hash) {
                                    if ('created' === $file) {
                                        continue;
                                    }
                                    printf(
                                        '<li><span class="pmf-popover" data-bs-toggle="popover" data-bs-container="body" title="SHA-1" data-bs-content="%s">%s</span></li>',
                                        $hash,
                                        $file
                                    );
                                }
                                echo '</ul>';
                            } else {
                                echo Alert::success('ad_verification_okay');
                            }
                        }
                    } catch (Exception $e) {
                        printf('<p class="alert alert-danger">%s</p>', $e->getMessage());
                    }
                } else {
                    ?>
                  <form action="./index.php" method="post" accept-charset="utf-8">
                      <input type="hidden" name="getJson" value="verify">
                      <button class="btn btn-info" type="submit">
                          <?= Translation::get('ad_verification_button') ?>
                      </button>
                  </form>
                    <?php
                }
                ?>
            </div>
          </div>
    </div>
        <?php endif; ?>
</section>

