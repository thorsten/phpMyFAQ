<?php

/**
 * Main update script.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Melchinger <t.melchinger@uni.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-01-10
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Setup\Installer;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use Symfony\Component\HttpFoundation\RedirectResponse;

const COPYRIGHT = '&copy; 2001-2023 <a target="_blank" href="//www.phpmyfaq.de/">phpMyFAQ Team</a>';
const IS_VALID_PHPMYFAQ = null;

define('PMF_ROOT_DIR', dirname(__FILE__, 2));

if (version_compare(PHP_VERSION, '8.1.0') < 0) {
    die('Sorry, but you need PHP 8.1.0 or later!');
}

set_time_limit(0);

require PMF_ROOT_DIR . '/src/Bootstrap.php';

Strings::init();

$step = Filter::filterInput(INPUT_GET, 'step', FILTER_VALIDATE_INT, 1);
$version = Filter::filterInput(INPUT_POST, 'version', FILTER_SANITIZE_SPECIAL_CHARS);
$query = [];

if (!file_exists(PMF_ROOT_DIR . '/config/database.php')) {
    $response = new RedirectResponse('index.php');
    $response->send();
}

$dbConfig = new DatabaseConfiguration(PMF_ROOT_DIR . '/config/database.php');

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
  <script src="../assets/dist/frontend.js"></script>
  <link rel="shortcut icon" href="../assets/themes/default/img/favicon.ico">
</head>
<body>

<nav class="p-3 text-bg-dark border-bottom">
  <div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
      <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
        <li class="nav-link px-2 text-white">
          <a href="<?= System::getDocumentationUrl() ?>" class="nav-link px-2 text-white" target="_blank">
            Documentation
          </a>
        </li>
        <li class="nav-link px-2 text-white {{ activeAddContent }}">
          <a href="https://www.phpmyfaq.de/support" class="nav-link px-2 text-white" target="_blank">
            Support
          </a>
        </li>
        <li class="nav-link px-2 text-white {{ activeAddQuestion }}">
          <a href="https://forum.phpmyfaq.de/" class="nav-link px-2 text-white" target="_blank">
            Forums
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main role="main">
  <section id="phpmyfaq-setup-form">
    <div class="container shadow-lg p-5 mt-5 bg-light-subtle">
      <div class="px-4 pt-2 my-2 text-center border-bottom">
        <h1 class="display-4 fw-bold">phpMyFAQ <?= System::getVersion() ?></h1>
        <div class="col-lg-6 mx-auto">
          <p class="lead mb-4">
            Did you already read our <a target="_blank" href="https://www.phpmyfaq.de/docs/3.2">documentation</a>
            carefully before starting the phpMyFAQ setup?
          </p>
        </div>
      </div>

      <div class="form-header d-flex mb-4">
        <span class="stepIndicator">Update information</span>
        <span class="stepIndicator">File backups</span>
        <span class="stepIndicator">Database updates</span>
    </div>
<?php

$system = new System();
$faqConfig = Configuration::getConfigurationInstance();
$version = $faqConfig->getVersion();

$installer = new Installer($system);
$update = new Update($system);

$installer->checkPreUpgrade($dbConfig->getType());

if ($update->isConfigTableAvailable($faqConfig->getDb())) {
    echo Alert::danger('ad_entryins_fail');
}

/**************************** STEP 1 OF 3 ***************************/
if ($step === 1) { ?>
      <form action="update.php?step=2" method="post">
        <input name="version" type="hidden" value="<?= $version ?>">

        <div class="row">
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
                if (!version_compare($version, '3.0.0', '>')) {
                    echo '<div class="alert alert-danger" role="alert">';
                    echo '<h4 class="alert-heading">Attention!</h4>';
                    printf('Your current version: %s', $version);
                    echo '<hr>Please update to the latest phpMyFAQ 3.0 version first.</div>';
                }

                //
                // Updates only possible if maintenance mode is enabled
                //
                if (!$faqConfig->get('main.maintenanceMode')) {
                    echo '<div class="alert alert-danger" role="alert"><h4 class="alert-heading">Heads up!</h4>' .
                        'Please enable the maintenance mode in the <a href="../admin/?action=config">admin section</a>' .
                        ' before running the update script.</div>';
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
    if (!copy(PMF_ROOT_DIR . '/config/database.php', PMF_ROOT_DIR . '/config/database.bak.php')) {
        echo '<p class="alert alert-danger"><strong>Error:</strong> The backup file ../config/database.bak.php ' .
            'could not be written. Please correct this!</p>';
    } else {
        $checkDatabaseSetupFile = true;
        $updateMessages[] = 'A backup of your database configuration file has been made.';
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

    // is everything being okay?
    if ($checkDatabaseSetupFile && $checkLdapSetupFile && $checkElasticsearchSetupFile) {
        ?>
      <form action="update.php?step=3" method="post">
        <input type="hidden" name="version" value="<?= $version ?>">

          <div class="row">
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

  <div class="row" id="step2">
    <div class="col">
    <?php
    $images = [];
    $prefix = Database::getTablePrefix();
    $faqConfig->getAll();
    $perm = new BasicPermission($faqConfig);

    require 'version_changes.php';

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
    switch ($dbConfig->getType()) {
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
