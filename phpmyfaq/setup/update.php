<?php

/**
 * Main update script.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Thomas Melchinger <t.melchinger@uni.de>
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2002-01-10
 */

use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\Installer;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\System;

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('IS_VALID_PHPMYFAQ', null);

if (version_compare(PHP_VERSION, '7.3.0') < 0) {
    die('Sorry, but you need PHP 7.3.0 or later!');
}

set_time_limit(0);

require PMF_ROOT_DIR . '/src/Bootstrap.php';

$step = Filter::filterInput(INPUT_GET, 'step', FILTER_VALIDATE_INT, 1);
$version = Filter::filterInput(INPUT_POST, 'version', FILTER_SANITIZE_STRING);
$query = [];

if (!file_exists(PMF_ROOT_DIR . '/config/database.php')) {
    header('Location: index.php');
    exit();
}

require PMF_ROOT_DIR . '/config/database.php';

?>
  <!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <title>phpMyFAQ <?= System::getVersion(); ?> Update</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="phpMyFAQ <?= System::getVersion() ?>">
    <meta name="copyright" content="(c) 2001-<?= date('Y') ?> phpMyFAQ Team">
    <link rel="stylesheet" href="../assets/dist/styles.css">
    <script src="../assets/dist/vendors.js"></script>
    <script src="../assets/dist/phpmyfaq.js"></script>
    <link rel="shortcut icon" href="../assets/themes/default/img/favicon.ico">
  </head>
<body>

  <header>
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark justify-content-between">
      <div class="container">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSetup"
                aria-controls="navbarSetup" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSetup">
          <ul class="navbar-nav mr-auto">
            <li class="nav-item">
              <a class="nav-link" target="_blank" href="https://www.phpmyfaq.de/documentation">Documentation</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" target="_blank" href="https://www.phpmyfaq.de/support">Support</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" target="_blank" href="https://forum.phpmyfaq.de/">Forums</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

<main role="main">
  <section id="content">
    <div class="jumbotron">
      <div class="container">
        <h1 class="display-4 text-center mt-5">
          phpMyFAQ <?= System::getVersion() ?> Update
        </h1>
        <p class="text-center">
          Did you already read the <a target="_blank" href="https://www.phpmyfaq.de/docs/3.0">documentation</a>
          carefully before updating your phpMyFAQ installation?
        </p>
      </div>
    </div>

    <div class="container mb-3">
<?php

$version = $faqConfig->getVersion();
$installer = new Installer();
$installer->checkPreUpgrade($DB['type']);
$installer->checkAvailableDatabaseTables($db);

/**************************** STEP 1 OF 3 ***************************/
if ($step === 1) { ?>
      <form action="update.php?step=2" method="post">
        <input name="version" type="hidden" value="<?= $version ?>">

        <div class="pmf-setup-stepwizard">
          <div class="pmf-setup-stepwizard-row setup-panel">
            <div class="pmf-setup-stepwizard-step">
              <a href="#step-1" type="button" class="btn btn-primary pmf-setup-stepwizard-btn-circle">1</a>
              <p>Update information</p>
            </div>
            <div class="pmf-setup-stepwizard-step">
              <a href="#step-2" type="button" class="btn btn-secondary pmf-setup-stepwizard-btn-circle"
                 disabled="disabled">
                2
              </a>
              <p>File backups</p>
            </div>
            <div class="pmf-setup-stepwizard-step">
              <a href="#step-3" type="button" class="btn btn-secondary pmf-setup-stepwizard-btn-circle"
                 disabled="disabled">
                3
              </a>
              <p>Database updates</p>
            </div>
          </div>
        </div>

        <div class="row" id="step1">
          <div class="col">
            <div class="alert alert-info text-center mt-2" role="alert">
              <strong>
                <i aria-hidden="true" class="fa fa-info-circle"></i>
                Please create a full backup of your database, your templates,
                attachments and uploaded images before running this update.
              </strong>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col">
            <p>This update script will work <strong>only</strong> for the following versions:</p>
            <ul>
              <li>phpMyFAQ 2.9.x</li>
              <li>phpMyFAQ 3.0.x</li>
            </ul>
          </div>
          <div class="col">
            <p>This update script <strong>will not</strong> work for the following versions:</p>
            <ul>
              <li>phpMyFAQ 0.x</li>
              <li>phpMyFAQ 1.x</li>
              <li>phpMyFAQ 2.0.x</li>
              <li>phpMyFAQ 2.5.x</li>
              <li>phpMyFAQ 2.6.x</li>
              <li>phpMyFAQ 2.7.x</li>
              <li>phpMyFAQ 2.8.x</li>
            </ul>
          </div>
        </div>

        <div class="row">
          <div class="col">
              <?php

                //
                // We only support updates from 2.9+
                //
                if (version_compare($version, '2.9.0', '>')) {
                    printf(
                        '<div class="alert alert-success text-center" role="alert">Your current version: %s %s</div>',
                        $version,
                        '<i aria-hidden="true" class="fa fa-check"></i>'
                    );
                } else {
                    printf(
                        '<div class="alert alert-danger text-center" role="alert">Your current version: %s</div>',
                        $version
                    );
                    echo '<p>Please update to the latest phpMyFAQ 2.9 version first.</p>';
                }

                //
                // Updates only possible if maintenance mode is enabled
                //
                if (!$faqConfig->get('main.maintenanceMode')) {
                    echo '<div class="alert alert-danger text-center" role="alert">Please enable the maintenance mode ' .
                      'in the <a href="../admin">admin section</a> before running the update script.</div>';
                    $updateDisabled = 'disabled';
                } else {
                    $updateDisabled = '';
                }
                ?>
            <p>
                <button class="btn btn-primary btn-next btn-lg pull-right <?= $updateDisabled ?>" type="submit"
                    <?= $updateDisabled ?>>Go to step 2 of 3</button>
            </p>
          </div>
        </div>
      </form>
    <?php
    System::renderFooter();
}

/**************************** STEP 2 OF 3 ***************************/
if ($step == 2) {
    $checkDatabaseSetupFile = $checkLdapSetupFile = $checkElasticsearchSetupFile = false;
    $updateMessages = [];

    // Backup of config/database.php
    if (file_exists(PMF_ROOT_DIR . '/config/database.php')) {
        if (!copy(PMF_ROOT_DIR . '/config/database.php', PMF_ROOT_DIR . '/config/database.bak.php')) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> The backup file ../config/database.bak.php ' .
                'could not be written. Please correct this!</p>';
        } else {
            $checkDatabaseSetupFile = true;
            $updateMessages[] = 'A backup of your database configuration file has been made.';
        }
    }

    // Backup of config/ldap.php if exists
    if (file_exists(PMF_ROOT_DIR . '/config/ldap.php')) {
        if (!copy(PMF_ROOT_DIR . '/config/ldap.php', PMF_ROOT_DIR . '/config/ldap.bak.php')) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> The backup file ../config/ldap.bak.php ' .
                'could not be written. Please correct this!</p>';
        } else {
            $checkLdapSetupFile = true;
            $updateMessages[] = 'A backup of your LDAP configuration file has been made.';
        }
    } else {
        $checkLdapSetupFile = true;
    }

    // Backup of config/elasticsearch.php if exists
    if (file_exists(PMF_ROOT_DIR . '/config/elasticsearch.php')) {
        if (!copy(PMF_ROOT_DIR . '/config/elasticsearch.php', PMF_ROOT_DIR . '/config/elasticsearch.bak.php')) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> The backup file ' .
                '../config/elasticsearch.bak.php could not be written. Please correct this!</p>';
        } else {
            $checkElasticsearchSetupFile = true;
            $updateMessages[] = 'A backup of your Elasticsearch configuration file has been made.';
        }
    } else {
        $checkElasticsearchSetupFile = true;
    }

    // is everything is okay?
    if ($checkDatabaseSetupFile && $checkLdapSetupFile && $checkElasticsearchSetupFile) {
        ?>
      <form action="update.php?step=3" method="post">
        <input type="hidden" name="version" value="<?= $version ?>">

        <div class="pmf-setup-stepwizard">
          <div class="pmf-setup-stepwizard-row setup-panel">
            <div class="pmf-setup-stepwizard-step">
              <a href="#step-1" type="button" class="btn btn-secondary pmf-setup-stepwizard-btn-circle"
                 disabled="disabled">1</a>
              <p>Update information</p>
            </div>
            <div class="pmf-setup-stepwizard-step">
              <a href="#step-2" type="button" class="btn btn-primary pmf-setup-stepwizard-btn-circle">
                2
              </a>
              <p>File backups</p>
            </div>
            <div class="pmf-setup-stepwizard-step">
              <a href="#step-3" type="button" class="btn btn-secondary pmf-setup-stepwizard-btn-circle"
                 disabled="disabled">
                3
              </a>
              <p>Database updates</p>
            </div>
          </div>
        </div>

        <div class="row" id="step2">
          <div class="col text-center mt-5">
              <?php
                foreach ($updateMessages as $updateMessage) {
                    printf('<p><i aria-hidden="true" class="fa fa-check-circle"></i> %s</p>', $updateMessage);
                } ?>
            <p class="mb-5">Your phpMyFAQ configuration will be updated after the next step.</p>
            <p>
              <button class="btn btn-primary btn-next btn-lg pull-right" type="submit">
                Go to step 3 of 3
              </button>
            </p>
          </div>
        </div>
      </form>
        <?php
        System::renderFooter();
    } else {
        echo '<p class="alert alert-danger"><strong>Error:</strong> Your version of phpMyFAQ could not updated.</p>';
        System::renderFooter();
    }
}

/**************************** STEP 3 OF 3 ***************************/
if ($step == 3) {
    ?>

  <div class="pmf-setup-stepwizard">
    <div class="pmf-setup-stepwizard-row setup-panel">
      <div class="pmf-setup-stepwizard-step">
        <a href="#step-1" type="button" class="btn btn-secondary pmf-setup-stepwizard-btn-circle"
           disabled="disabled">1</a>
        <p>Update information</p>
      </div>
      <div class="pmf-setup-stepwizard-step">
        <a href="#step-2" type="button" class="btn btn-secondary pmf-setup-stepwizard-btn-circle">
          2
        </a>
        <p>File backups</p>
      </div>
      <div class="pmf-setup-stepwizard-step">
        <a href="#step-3" type="button" class="btn btn-primary pmf-setup-stepwizard-btn-circle"
           disabled="disabled">
          3
        </a>
        <p>Database updates</p>
      </div>
    </div>
  </div>

  <div class="row " id="step2">
    <div class="col">
    <?php
    $images = [];
    $prefix = Database::getTablePrefix();
    $faqConfig->getAll();
    $perm = new BasicPermission($faqConfig);

    //
    // Enable maintenance mode
    //
    if ($faqConfig->set('main.maintenanceMode', 'true')) {
        echo "<p class='alert alert-info'><i class='fa fa-info-circle'></i> Activating maintenance mode ...</p>";
    }

    //
    // UPDATES FROM 2.9.0-alpha
    //
    if (version_compare($version, '2.9.0-alpha', '<')) {
        $faqConfig->delete('cache.varnishEnable');
        $faqConfig->delete('cache.varnishHost');
        $faqConfig->delete('cache.varnishPort');
        $faqConfig->delete('cache.varnishSecret');
        $faqConfig->delete('cache.varnishTimeout');

        $faqConfig->add('search.enableHighlighting', 'true');
        $faqConfig->add('main.enableRssFeeds', 'true');
        $faqConfig->add('records.allowCommentsForGuests', 'true');
        $faqConfig->add('records.allowQuestionsForGuests', 'true');
        $faqConfig->add('records.allowNewFaqsForGuests', 'true');
        $faqConfig->add('records.hideEmptyCategories', 'false');
        $faqConfig->add('search.searchForSolutionId', 'true');
        $faqConfig->add('socialnetworks.disableAll', 'false');
        $faqConfig->add('main.enableGzipCompression', 'true');

        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquser ADD COLUMN success INT(1) NULL DEFAULT 1';
        } elseif ('pgsql' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquser ADD success SMALLINT NULL DEFAULT 1';
        } else {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquser ADD success INTEGER NULL DEFAULT 1';
        }
    }

    //
    // UPDATES FROM 2.9.0-alpha2
    //
    if (version_compare($version, '2.9.0-alpha2', '<')) {
        $faqConfig->add('seo.metaTagsHome', 'index, follow');
        $faqConfig->add('seo.metaTagsFaqs', 'index, follow');
        $faqConfig->add('seo.metaTagsCategories', 'index, follow');
        $faqConfig->add('seo.metaTagsPages', 'index, follow');
        $faqConfig->add('seo.metaTagsAdmin', 'noindex, nofollow');
        $faqConfig->add('main.enableLinkVerification', 'true');
        $faqConfig->add('spam.manualActivation', 'true');
        $faqConfig->add('mail.remoteSMTP', 'false');
        $faqConfig->add('mail.remoteSMTPServer', '');
        $faqConfig->add('mail.remoteSMTPUsername', '');
        $faqConfig->add('mail.remoteSMTPPassword', '');
        $faqConfig->add('security.enableRegistration', 'true');
        $faqConfig->delete('search.useAjaxSearchOnStartpage');

        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faqcategories ADD COLUMN active INT(1) NULL DEFAULT 1';
        } else {
            $query[] = 'ALTER TABLE ' . $prefix . 'faqcategories ADD active INT NULL DEFAULT 1';
        }
    }

    //
    // UPDATES FROM 2.9.0-alpha3
    //
    if (version_compare($version, '2.9.0-alpha3', '<')) {
        $faqConfig->add('main.customPdfHeader', '');
        $faqConfig->add('main.customPdfFooter', '');
        $faqConfig->add('records.allowDownloadsForGuests', 'false');
        $faqConfig->add('main.enableMarkdownEditor', 'false');
        $faqConfig->add('main.enableSmartAnswering', 'true');
        $faqConfig->add('records.numberMaxStoredRevisions', '10');

        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faqquestions ADD COLUMN lang VARCHAR(5) NOT NULL DEFAULT \'\'';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqcategories ADD COLUMN group_id INT NULL DEFAULT -1';
        } else {
            $query[] = 'ALTER TABLE ' . $prefix . 'faqquestions ADD lang VARCHAR(5) NOT NULL DEFAULT \'\'';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqcategories ADD group_id INT NULL DEFAULT -1';
        }
        $query[] = 'UPDATE ' . $prefix . "faqquestions SET lang = '" . $faqConfig->getDefaultLanguage() . "'";
    }

    //
    // UPDATES FROM 2.9.0-alpha4
    //
    if (version_compare($version, '2.9.0-alpha4', '<')) {
        switch ($DB['type']) {
            case 'pgsql':
                $query[] = 'ALTER TABLE ' . $prefix . 'faqdata RENAME COLUMN datum TO updated';
                $query[] = 'ALTER TABLE ' . $prefix . 'faqdata_revisions RENAME COLUMN datum TO updated';
                $query[] = 'ALTER TABLE ' . $prefix . 'faqdata ADD created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
                $query[] = 'ALTER TABLE ' . $prefix . 'faqdata_revisions ADD created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
                break;
            case 'mssql':
            case 'sqlsrv':
                $query[] = "EXEC sp_RENAME '" . $prefix . "faqdata.datum', 'updated', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '" . $prefix . "faqdata_revisions.datum', 'updated', 'COLUMN'";
                $query[] = 'ALTER TABLE ' . $prefix . 'faqdata ADD created DATETIME DEFAULT CURRENT_TIMESTAMP';
                $query[] = 'ALTER TABLE ' . $prefix . 'faqdata_revisions ADD created DATETIME DEFAULT CURRENT_TIMESTAMP';
                break;
            case 'mysqli':
                $query[] = 'ALTER TABLE ' . $prefix . 'faqdata CHANGE datum updated VARCHAR(15) NOT NULL';
                $query[] = 'ALTER TABLE ' . $prefix . 'faqdata_revisions CHANGE datum updated VARCHAR(15) NOT NULL';
                $query[] = 'ALTER TABLE ' . $prefix . 'faqdata ADD created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
                $query[] = 'ALTER TABLE ' . $prefix . 'faqdata_revisions ADD created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
                break;
        }
        if ('sqlite3' === $DB['type']) {
            $query[] = 'CREATE TABLE ' . $prefix . "faqdata_temp (
                id INTEGER NOT NULL,
                lang VARCHAR(5) NOT NULL,
                solution_id INTEGER NOT NULL,
                revision_id INTEGER NOT NULL DEFAULT 0,
                active char(3) NOT NULL,
                sticky INTEGER NOT NULL,
                keywords text DEFAULT NULL,
                thema text NOT NULL,
                content text DEFAULT NULL,
                author VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                comment char(1) default 'y',
                updated VARCHAR(15) NOT NULL,
                links_state VARCHAR(7) DEFAULT NULL,
                links_check_date INTEGER DEFAULT 0 NOT NULL,
                date_start VARCHAR(14) NOT NULL DEFAULT '00000000000000',
                date_end VARCHAR(14) NOT NULL DEFAULT '99991231235959',
                PRIMARY KEY (id, lang))";
            $query[] = 'INSERT INTO ' . $prefix . 'faqdata_temp
                SELECT
                    id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email,
                    comment, datum, links_state, links_check_date, date_start, date_end
                FROM ' . $prefix . 'faqdata';
            $query[] = 'DROP TABLE ' . $prefix . 'faqdata';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqdata_temp RENAME TO ' . $prefix . 'faqdata';

            $query[] = 'CREATE TABLE ' . $prefix . "faqdata_revision_temp (
                id INTEGER NOT NULL,
                lang VARCHAR(5) NOT NULL,
                solution_id INTEGER NOT NULL,
                revision_id INTEGER NOT NULL DEFAULT 0,
                active char(3) NOT NULL,
                sticky INTEGER NOT NULL,
                keywords text DEFAULT NULL,
                thema text NOT NULL,
                content text DEFAULT NULL,
                author VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                comment char(1) default 'y',
                updated VARCHAR(15) NOT NULL,
                links_state VARCHAR(7) DEFAULT NULL,
                links_check_date INTEGER DEFAULT 0 NOT NULL,
                date_start VARCHAR(14) NOT NULL DEFAULT '00000000000000',
                date_end VARCHAR(14) NOT NULL DEFAULT '99991231235959',
                PRIMARY KEY (id, lang, solution_id, revision_id))";
            $query[] = 'INSERT INTO ' . $prefix . 'faqdata_revision_temp
                SELECT
                    id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email,
                    comment, datum, links_state, links_check_date, date_start, date_end
                FROM ' . $prefix . 'faqdata_revisions';
            $query[] = 'DROP TABLE ' . $prefix . 'faqdata_revisions';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqdata_revision_temp RENAME TO ' . $prefix . 'faqdata_revisions';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqdata ADD COLUMN created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqdata_revisions ADD COLUMN created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
        }
    }

    //
    // UPDATES FROM 2.9.0-beta
    //
    if (version_compare($version, '2.9.0-beta2', '<')) {
        $faqConfig->add('search.enableElasticsearch', 'false');
    }

    //
    // UPDATES FROM 2.9.0-RC
    //
    if (version_compare($version, '2.9.0-RC', '<')) {
        if ($DB['type'] === 'mssql' || $DB['type'] === 'sqlsrv') {
            $query[] = 'ALTER TABLE ' . $prefix . 'faqdata ADD notes VARCHAR(MAX) DEFAULT NULL';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqdata_revisions ADD notes VARCHAR(MAX) DEFAULT NULL';
        } else {
            $query[] = 'ALTER TABLE ' . $prefix . 'faqdata ADD notes text DEFAULT NULL';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqdata_revisions ADD notes text DEFAULT NULL';
        }
    }

    //
    // UPDATES FROM 2.9.6
    //
    if (version_compare($version, '2.9.6', '<')) {
        if ($DB['type'] === 'mysqli') {
            $query[] = 'ALTER TABLE ' . $prefix . 'faqdata ADD FULLTEXT(keywords,thema,content);';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqquestions CHANGE COLUMN lang lang VARCHAR(5) AFTER id';
        }
    }

    //
    // UPDATES FROM 2.10.0-alpha
    //
    if (version_compare($version, '2.10.0-alpha', '<')) {
        $faqConfig->add('ldap.ldap_mapping.name', 'cn');
        $faqConfig->add('ldap.ldap_mapping.username', 'samAccountName');
        $faqConfig->add('ldap.ldap_mapping.mail', 'mail');
        $faqConfig->add('ldap.ldap_mapping.memberOf', '');
        $faqConfig->add('ldap.ldap_use_domain_prefix', 'true');
        $faqConfig->add('ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION', '3');
        $faqConfig->add('ldap.ldap_options.LDAP_OPT_REFERRALS', '0');
        $faqConfig->add('ldap.ldap_use_memberOf', 'false');
        $faqConfig->add('ldap.ldap_use_sasl', 'false');
        $faqConfig->add('ldap.ldap_use_multiple_servers', 'false');
        $faqConfig->add('ldap.ldap_use_anonymous_login', 'false');
        $faqConfig->add('ldap.ldap_use_dynamic_login', 'false');
        $faqConfig->add('ldap.ldap_dynamic_login_attribute', 'uid');
        $faqConfig->add('seo.enableXMLSitemap', 'true');
        $faqConfig->add('main.enableCategoryRestrictions', 'true');
        $faqConfig->update(['main.currentApiVersion' => System::getApiVersion()]);

        $query[] = 'UPDATE ' . $prefix . "faqconfig SET config_name = 'ldap.ldapSupport'
            WHERE config_name = 'security.ldapSupport'";

        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faqcategories ADD COLUMN image VARCHAR(255) DEFAULT NULL';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqcategories ADD COLUMN show_home SMALLINT DEFAULT NULL';
        } else {
            $query[] = 'ALTER TABLE ' . $prefix . 'faqcategories ADD image VARCHAR(255) DEFAULT NULL';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqcategories ADD show_home INTEGER DEFAULT NULL';
        }
    }

    //
    // UPDATES FROM 3.0.0-alpha
    //
    if (version_compare($version, '3.0.0-alpha', '<')) {
        $query[] = 'DELETE FROM ' . $prefix . 'faqright WHERE right_id = 18';
        $query[] = 'DELETE FROM ' . $prefix . 'faquser_right WHERE right_id = 18';
        $query[] = 'DELETE FROM ' . $prefix . 'faqgroup_right WHERE right_id = 18';
        $query[] = 'INSERT INTO ' . $prefix . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (18, 'viewadminlink', 'Right to see the link to the admin section', 1, 1)";
        $query[] = 'INSERT INTO ' . $prefix . 'faquser_right (user_id, right_id) VALUES (1, 18)';

        $faqConfig->add('main.enableSendToFriend', 'true');
        $faqConfig->add('main.privacyURL', '');
    }

    //
    // UPDATES FROM 3.0.0-alpha.2
    //
    if (version_compare($version, '3.0.0-alpha.2', '<')) {
        $faqConfig->add('main.enableAutoUpdateHint', 'true');
    }

    //
    // UPDATES FROM 3.0.0-alpha.3
    //
    if (version_compare($version, '3.0.0-alpha.3', '<')) {
        $faqConfig->add('records.enableAutoRevisions', 'false');
        // Add superadmin flag
        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquser ADD COLUMN is_superadmin INT(1) DEFAULT 0';
        } else {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquser ADD is_superadmin INTEGER DEFAULT 0';
        }
        $query[] = 'UPDATE ' . $prefix . 'faquser SET is_superadmin = 1 WHERE user_id = 1';

        // Add domain flag
        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquserlogin ADD COLUMN domain VARCHAR(255) DEFAULT NULL';
        } else {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquserlogin ADD domain VARCHAR(255) DEFAULT NULL';
        }

        // Update section flag for faqright table
        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faqright ADD COLUMN for_sections INT(11) DEFAULT 0';
        } else {
            $query[] = 'ALTER TABLE ' . $prefix . 'faqright ADD for_sections INTEGER DEFAULT 0';
        }

        // Add new tables
        $query[] = 'CREATE TABLE ' . $prefix . 'faqcategory_news (category_id INTEGER NOT NULL, news_id INTEGER NOT NULL, PRIMARY KEY (category_id, news_id))';
        $query[] = 'CREATE TABLE ' . $prefix . 'faqsections (id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id))';
        $query[] = 'CREATE TABLE ' . $prefix . 'faqsection_category (section_id INTEGER NOT NULL, category_id INTEGER NOT NULL DEFAULT -1, PRIMARY KEY (section_id, category_id))';
        $query[] = 'CREATE TABLE ' . $prefix . 'faqsection_group (section_id INTEGER NOT NULL, group_id INTEGER NOT NULL DEFAULT -1, PRIMARY KEY (section_id, group_id))';
        $query[] = 'CREATE TABLE ' . $prefix . 'faqsection_news (section_id INTEGER NOT NULL, news_id INTEGER NOT NULL DEFAULT -1, PRIMARY KEY (section_id, news_id))';
        $query[] = 'CREATE TABLE ' . $prefix . 'faqmeta (id INT NOT NULL, lang VARCHAR(5) DEFAULT NULL, page_id VARCHAR(48) DEFAULT NULL, type VARCHAR(48) DEFAULT NULL, content TEXT NULL, PRIMARY KEY (id))';

        // Add new rights
        $perm->addRight(['name' => 'view_faqs', 'description' => 'Right to view FAQs']);
        $perm->addRight(['name' => 'view_categories', 'description' => 'Right to view categories']);
        $perm->addRight(['name' => 'view_sections', 'description' => 'Right to view sections']);
        $perm->addRight(['name' => 'view_news', 'description' => 'Right to view news']);
        $perm->addRight(['name' => 'add_section', 'description' => 'Right to add sections']);
        $perm->addRight(['name' => 'edit_section', 'description' => 'Right to edit sections']);
        $perm->addRight(['name' => 'delete_section', 'description' => 'Right to delete sections']);
        $perm->addRight(['name' => 'administrate_sections', 'description' => 'Right to administrate sections']);
        $perm->addRight(['name' => 'administrate_groups', 'description' => 'Right to administrate groups']);

        // Rename rights
        $perm->renameRight('adduser', 'add_user');
        $perm->renameRight('edituser', 'edit_user');
        $perm->renameRight('deluser', 'delete_user');
    }

    //
    // UPDATES FROM 3.0.0-alpha.4
    //
    if (version_compare($version, '3.0.0-alpha.4', '<')) {
        $perm->renameRight('addbt', 'add_faq');
        $perm->renameRight('editbt', 'edit_faq');
        $perm->renameRight('delbt', 'delete_faq');

        // Add login attempts flag
        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquser ADD COLUMN login_attempts INT(1) DEFAULT 0';
        } else {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquser ADD login_attempts INTEGER DEFAULT 0';
        }
    }

    //
    // UPDATES FROM 3.0.0-beta.3
    //
    if (version_compare($version, '3.0.0-beta.3', '<=')) {
        // Fix category table
        switch ($DB['type']) {
            case 'mysqli':
                $query[] = 'ALTER TABLE ' . $prefix . 'faqcategories MODIFY parent_id INTEGER';
                break;
            case 'pgsql':
                $query[] = 'ALTER TABLE ' . $prefix . 'faqcategories ALTER COLUMN parent_id TYPE INTEGER;';
                break;
        }

        $faqConfig->add('mail.remoteSMTPPort', '465');
        $faqConfig->add('mail.remoteSMTPEncryption', 'ssl');
        $faqConfig->delete('socialnetworks.enableFacebookSupport');
    }

    //
    // UPDATES FROM 3.0.0-RC
    //
    if (version_compare($version, '3.0.0-RC', '<=')) {
        $query[] = 'UPDATE ' . $prefix . "faqconfig SET config_name = 'main.customPdfFooter'
            WHERE config_name = 'main.customPdfHFooter'";
    }

    //
    // UPDATES FROM 3.1.0-alpha
    //
    if (version_compare($version, '3.1.0-alpha', '<=')) {
        // Add is_visible flag for user data
        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquserdata ADD COLUMN is_visible INT(1) DEFAULT 0';
        } else {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquserdata ADD is_visible INTEGER DEFAULT 0';
        }

        // Remove RSS support
        $faqConfig->delete('main.enableRssFeeds');

        // Add API related configuration
        $faqConfig->add('api.enableAccess', true);
        $faqConfig->add('api.apiClientToken', '');

        // Add whitelist for domains
        $faqConfig->add('security.domainWhiteListForRegistrations', '');
    }

    //
    // UPDATES FROM 3.1.0-alpha.3
    //
    if (version_compare($version, '3.1.0-alpha.3', '<=')) {
        // Add "login with email address" configuration
        $faqConfig->add('main.loginWithEmailAddress', false);
    }

    //
    // UPDATES FROM 3.1.0-beta
    //
    if (version_compare($version, '3.1.0-beta', '<=')) {
        switch ($DB['type']) {
            case 'mysqli':
                $query[] = 'CREATE TABLE ' . $prefix . 'faqcategory_order (
                    category_id int(11) NOT NULL,
                    position int(11) NOT NULL,
                    PRIMARY KEY (category_id))';
                break;
            case 'pgsql':
            case 'sqlite3':
            case 'sqlsrv':
                $query[] = 'CREATE TABLE ' . $prefix . 'faqcategory_order (
                    category_id INTEGER NOT NULL,
                    position INTEGER NOT NULL,
                    PRIMARY KEY (category_id))';
                break;
        }
    }
    
    //
    // UPDATES FROM 3.1.0-RC
    //
    if (version_compare($version, '3.1.0-RC', '<=')) {
        $faqConfig->delete('records.autosaveActive');
        $faqConfig->delete('records.autosaveSecs');
    }

    //
    // Always the last step: Update version number
    //
    if (version_compare($version, System::getVersion(), '<')) {
        $faqConfig->update(['main.currentApiVersion' => System::getApiVersion()]);
        $faqConfig->update(['main.currentVersion' => System::getVersion()]);
    }

    //
    // Optimize tables if possible
    //
    switch ($DB['type']) {
        case 'mysqli':
            // Get all table names
            $faqConfig->getDb()->getTableNames($prefix);
            foreach ($faqConfig->getDb()->tableNames as $tableName) {
                $query[] = 'OPTIMIZE TABLE ' . $tableName;
            }
            break;
        case 'pgsql':
            $query[] = 'VACUUM ANALYZE;';
            break;
    }

    // Perform the queries for optimizing the database
    if (isset($query)) {
        echo '<div class="mt-5 mb-5">';
        echo '<h6>Update Progress:</h6>';
        echo '<div class="text-center">';
        foreach ($query as $executeQuery) {
            $result = $faqConfig->getDb()->query($executeQuery);
            printf('<span title="%s">█</span>', $executeQuery);
            if (!$result) {
                echo '<p class="alert alert-danger"><strong>Error:</strong> Please update your version of phpMyFAQ ' .
                    'once again or send us a <a href="https://github.com/thorsten/phpMyFAQ/issues" target="_blank">' .
                    'bug report</a></p>';
                printf('<p class="error"><strong>DB error:</strong> %s</p>', $faqConfig->getDb()->error());
                printf('<code>%s</code>', htmlentities($executeQuery));
                System::renderFooter();
            }
            usleep(10000);
        }
        echo '</div>';
        echo '</div>';
    }

    //
    // Disable maintenance mode
    //
    if ($faqConfig->set('main.maintenanceMode', 'false')) {
        echo "<p class='alert alert-info'><i class='fa fa-info-circle'></i> Deactivating maintenance mode ...</p>";
    }
    ?>
  <p class="alert alert-success">The database was updated successfully. Thank you very much for updating.</p>
    <?php
    //
    // Remove backup files
    //
    foreach (glob(PMF_ROOT_DIR . '/config/*.bak.php') as $filename) {
        if (!unlink($filename)) {
            printf("<p class=\"alert alert-info\">Please remove the backup file %s manually.</p>\n", $filename);
        }
    }
    ?>
  <p>
    <a href="../index.php" class="btn btn-primary btn-next btn-lg pull-right" type="button">
      Go to your updated phpMyFAQ installation
    </a>
  </p>
    <?php
    System::renderFooter();
}
