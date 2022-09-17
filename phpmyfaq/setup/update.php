<?php

/**
 * Main update script.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Thomas Melchinger <t.melchinger@uni.de>
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2022 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2002-01-10
 */

use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\Installer;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\System;

const COPYRIGHT = '&copy; 2001-2022 <a target="_blank" href="//www.phpmyfaq.de/">phpMyFAQ Team</a>';
const IS_VALID_PHPMYFAQ = null;

define('PMF_ROOT_DIR', dirname(__FILE__, 2));

if (version_compare(PHP_VERSION, '8.0.0') < 0) {
    die('Sorry, but you need PHP 8.0.0 or later!');
}

set_time_limit(0);

require PMF_ROOT_DIR . '/src/Bootstrap.php';

$step = Filter::filterInput(INPUT_GET, 'step', FILTER_VALIDATE_INT, 1);
$version = Filter::filterInput(INPUT_POST, 'version', FILTER_UNSAFE_RAW);
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
    <link rel="shortcut icon" href="../assets/themes/default/img/favicon.ico">
  </head>
<body>

    <header>
        <div class="px-3 py-2 bg-light">
            <div class="container">
                <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
                    <ul class="nav col-12 col-lg-auto my-2 justify-content-center my-md-0 text-small">
                        <li class="nav-item">
                            <a href="https://www.phpmyfaq.de/documentation" class="nav-link" target="_blank">
                                Documentation
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="https://www.phpmyfaq.de/support" class="nav-link" target="_blank">Support</a>
                        </li>
                        <li class="nav-item">
                            <a href="https://forum.phpmyfaq.de/" class="nav-link" target="_blank">Forums</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
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
              <li>phpMyFAQ 3.0.x</li>
              <li>phpMyFAQ 3.1.x</li>
              <li>phpMyFAQ 3.2.x</li>
            </ul>
          </div>
          <div class="col">
            <p>This update script <strong>will not</strong> work for the following versions:</p>
            <ul>
              <li>phpMyFAQ 0.x</li>
              <li>phpMyFAQ 1.x</li>
              <li>phpMyFAQ 2.x</li>
            </ul>
          </div>
        </div>

        <div class="row">
          <div class="col">
              <?php

                //
                // We only support updates from 3.0+
                //
                if (version_compare($version, '3.0.0', '>')) {
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
                    echo '<p>Please update to the latest phpMyFAQ 3.0 version first.</p>';
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
        $query[] = match ($DB['type']) {
            'mysqli' => 'CREATE TABLE ' . $prefix . 'faqcategory_order (
                    category_id int(11) NOT NULL,
                    position int(11) NOT NULL,
                    PRIMARY KEY (category_id))',
            'pgsql', 'sqlite3', 'sqlsrv' => 'CREATE TABLE ' . $prefix . 'faqcategory_order (
                    category_id INTEGER NOT NULL,
                    position INTEGER NOT NULL,
                    PRIMARY KEY (category_id))',
        };
    }

    //
    // UPDATES FROM 3.1.0-RC
    //
    if (version_compare($version, '3.1.0-RC', '<=')) {
        $faqConfig->delete('records.autosaveActive');
        $faqConfig->delete('records.autosaveSecs');
    }

    //
    // UPDATES FROM 3.2.0-alpha
    //
    if (version_compare($version, '3.2.0-alpha', '<=')) {
        // Azure AD support
        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquser 
                ADD COLUMN refresh_token TEXT NULL DEFAULT NULL,
                ADD COLUMN access_token TEXT NULL DEFAULT NULL,
                ADD COLUMN code_verifier VARCHAR(255) NULL DEFAULT NULL,
                ADD COLUMN jwt TEXT NULL DEFAULT NULL';
        } else {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquser 
                ADD refresh_token TEXT NULL DEFAULT NULL,
                ADD access_token TEXT NULL DEFAULT NULL,
                ADD code_verifier VARCHAR(255) NULL DEFAULT NULL,
                ADD jwt TEXT NULL DEFAULT NULL';
        }
        $faqConfig->add('security.enableSignInWithMicrosoft', false);

        if ('sqlserv' === $DB['type']) {
            // queries to update VARCHAR -> NVARCHAR on MS SQL Server
            // @todo ALTER TABLE [TableName] ALTER COLUMN [ColumnName] nvarchar(N) null
            $query[] = 'DBCC CLEANTABLE';
        }
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
