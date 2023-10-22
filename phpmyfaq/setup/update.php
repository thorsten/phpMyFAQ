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

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
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

$step = Filter::filterInput(INPUT_GET, 'next-step', FILTER_VALIDATE_INT, 1);
$version = Filter::filterInput(INPUT_POST, 'installed-version', FILTER_SANITIZE_SPECIAL_CHARS);

$system = new System();
$faqConfig = Configuration::getConfigurationInstance();

$update = new Update($system, $faqConfig);
$update->setVersion(System::getVersion());

$installedVersion = $faqConfig->getVersion();

if (!$update->checkDatabaseFile()) {
    $redirect = new RedirectResponse('./index.php');
    $redirect->send();
}

try {
    $dbConfig = new DatabaseConfiguration(PMF_ROOT_DIR . '/config/database.php');
} catch (ErrorException $e) {
    $dbConfig = new DatabaseConfiguration(PMF_ROOT_DIR . '/content/core/config/database.php');
}

$twig = new TwigWrapper('../assets/templates');
$template = $twig->loadTemplate('./setup/update.twig');

$templateVars = [
    'newVersion' => System::getVersion(),
    'installedVersion' => $installedVersion,
    'currentYear' => date('Y'),
    'documentationUrl' => System::getDocumentationUrl(),
    'configTableNotAvailable' => $update->isConfigTableAvailable($faqConfig->getDb()),
    'nextStepButtonEnabled' => $update->checkMaintenanceMode() ? '' : 'disabled',
];

// Check hard requirements
try {
    $update->checkPreUpgrade($dbConfig->getType());
} catch (Exception $e) {
    $templateVars = [
        ...$templateVars,
        'errorCheckPreUpgrade' => true,
        'errorMessagePreUpgrade' => $e->getMessage(),
    ];
}


// We only support updates from 3.0+
if (!$update->checkMinimumUpdateVersion($installedVersion)) {
    $templateVars = [
        ...$templateVars,
        'installedVersionTooOld' => true,
    ];
}

// Updates only possible if maintenance mode is enabled
if (!$update->checkMaintenanceMode()) {
    $templateVars = [
        ...$templateVars,
        'isMaintenanceModeEnabled' => true,
    ];
}

echo $template->render($templateVars);

/**************************** STEP 2 OF 3 ***************************/
if ($step == 2) {
    $checkDatabaseSetupFile = $checkLdapSetupFile = $checkElasticsearchSetupFile = true;
    $updateMessages = [];

    /*
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
    */

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

    // Perform the queries for optimizing the database
    echo '<div class="mt-5 mb-5">';
    echo '<h6>Update Progress:</h6>';

    try {
        $progressCallback = function ($query) {
            echo "Executing query: $query" . PHP_EOL;
        };
        $update->applyUpdates($progressCallback);
    } catch (ErrorException | Exception $exception) {
        echo '<p class="alert alert-danger"><strong>Error:</strong> ' . $exception->getMessage() . '</p>';
    }

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
