<?php
/**
 * Main update script
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Setup
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Melchinger <t.melchinger@uni.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-01-10
 */

define('COPYRIGHT', '&copy; 2001-2014 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('IS_VALID_PHPMYFAQ', null);

if ((@ini_get('safe_mode') != 'On' || @ini_get('safe_mode') !== 1)) {
    set_time_limit(0);
}

if (version_compare(PHP_VERSION, '5.4.4') < 0) {
    die("Sorry, but you need PHP 5.4.4 or later!"); // Die hard because of "use"
}

require PMF_ROOT_DIR . '/inc/Bootstrap.php';

$step    = PMF_Filter::filterInput(INPUT_GET, 'step', FILTER_VALIDATE_INT, 1);
$version = PMF_Filter::filterInput(INPUT_POST, 'version', FILTER_SANITIZE_STRING);
$query   = [];

if (file_exists(PMF_ROOT_DIR.'/inc/data.php')) {
    require PMF_ROOT_DIR . '/inc/data.php'; // before 2.6.0-alpha
} else {
    if (!file_exists(PMF_ROOT_DIR . '/config/database.php')) {
        header("Location: setup.php");
        exit();
    }
    require PMF_ROOT_DIR . '/config/database.php'; // after 2.6.0-alpha
}
?>
<!doctype html>
<!--[if lt IE 7 ]> <html lang="en" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]> <html lang="en" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]> <html lang="en" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]> <html lang="en" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="en" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>phpMyFAQ <?php echo PMF_System::getVersion(); ?> Update</title>

    <meta name="viewport" content="width=device-width;">
    <meta name="application-name" content="phpMyFAQ <?php echo PMF_System::getVersion(); ?>">
    <meta name="copyright" content="(c) 2001-<?php echo date('Y'); ?> phpMyFAQ Team">

    <link rel="stylesheet" href="../admin/assets/css/style.min.css?v=1">

    <script src="../assets/js/libs/modernizr.min.js"></script>
    <script src="../assets/js/libs/jquery.min.js"></script>

    <link rel="shortcut icon" href="../assets/template/default/favicon.ico">
    <link rel="apple-touch-icon" href="../assets/template/default/apple-touch-icon.png">

</head>
<body>

<!--[if lt IE 8 ]>
<div class="internet-explorer-error">
    Do you know that your Internet Explorer is out of date?<br/>
    Please use Internet Explorer 8+, Mozilla Firefox 4+, Google Chrome, Apple Safari 5+ or Opera 11+
</div>
<![endif]-->

<div class="navbar navbar-default navbar-static-top">
    <nav class="container">
        <ul class="nav navbar-nav">
            <li><a target="_blank" href="http://www.phpmyfaq.de/documentation.php">Documentation</a></li>
            <li><a target="_blank" href="http://www.phpmyfaq.de/support.php">Support</a></li>
            <li><a target="_blank" href="http://forum.phpmyfaq.de/">Forums</a></li>
            <li><a target="_blank" href="http://faq.phpmyfaq.de/">FAQ</a></li>
            <li class="divider-vertical"></li>
            <li><a href="../">Back to your FAQ</a></li>
        </ul>
    </nav>
</div>

<section id="content">
    <div class="container">
        <div class="row">
            <div class="jumbotron text-center">
                <h1>phpMyFAQ <?php echo PMF_System::getVersion(); ?> Update</h1>
                <?php
                $version = $faqConfig->get('main.currentVersion');
                if (version_compare($version, '2.8.0-alpha2', '>=')) {
                    if (! $faqConfig->get('main.maintenanceMode')) {
                        echo '<p class="alert alert-warning"><strong>Warning!</strong> Your phpMyFAQ installation is ' .
                             'not in maintenance mode, you should enable the maintenance mode in your administration ' .
                             'backend before running the update!</p>';
                    }
                }
                ?>
            </div>
        </div>
<?php

$installer = new PMF_Installer();
$installer->checkPreUpgrade();

/**************************** STEP 1 OF 3 ***************************/
if ($step === 1) {
?>
        <form action="update.php?step=2" method="post">
        <input name="version" type="hidden" value="<?php echo $version; ?>">
        <div class="row form-group">
            <div class="col-lg-12">
                <ul class="nav nav-pills nav-justified thumbnail setup-panel">
                    <li class="active">
                        <a href="#">
                            <h4 class="list-group-item-heading">Step 1 of 3</h4>
                            <p class="list-group-item-text">Update information</p>
                        </a>
                    </li>
                    <li class="disabled"><a href="update.php?step=2">
                            <h4 class="list-group-item-heading">Step 2 of 3</h4>
                            <p class="list-group-item-text">File backups</p>
                        </a>
                    </li>
                    <li class="disabled"><a href="#">
                            <h4 class="list-group-item-heading">Step 3 of 3</h4>
                            <p class="list-group-item-text">Database updates</p>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="row setup-content" id="step1">
            <div class="col-lg-12">
                <p class="alert alert-info text-center">
                    <strong>
                        Please create a full backup of your database, your templates, attachments and uploaded images
                        before running this update.
                    </strong>
                </p>

                <p>This update script will work <strong>only</strong> for the following versions:</p>
                <ul>
                    <li>phpMyFAQ 2.5.x (out of support since mid of 2010)</li>
                    <li>phpMyFAQ 2.6.x (out of support since end of 2011)</li>
                    <li>phpMyFAQ 2.7.x</li>
                    <li>phpMyFAQ 2.8.x</li>
                </ul>

                <p>This update script <strong>will not</strong> work for the following versions:</p>
                <ul>
                    <li>phpMyFAQ 0.x</li>
                    <li>phpMyFAQ 1.x</li>
                    <li>phpMyFAQ 2.0.x</li>
                </ul>
                <?php
                // 2.5 versions only
                if (version_compare($version, '2.6.0-alpha', '<') && !is_writeable('../template')) {
                    echo '<p class="alert alert-danger text-center"><strong>Please change the directory ../template ' .
                         'and its contents writable (777 on Linux/UNIX).</strong></p>';
                }

                // We only support updates from 2.5+
                if (version_compare($version, '2.5.0', '>')) {
                    printf(
                        '<p class="alert alert-success text-center">Your current phpMyFAQ version: %s</p>',
                        $version
                    );
                } else {
                    printf(
                        '<p class="alert alert-danger text-center">Your current phpMyFAQ version: %s</p>',
                        $version
                    );
                    echo '<p>Please update to the latest phpMyFAQ 2.7 version first.</p>';
                }
                if ('hash' !== PMF_ENCRYPTION_TYPE) {
                    printf(
                        '<p class="alert alert-info text-center">Your passwords are currently encoded with a %s() method.</p>',
                        PMF_ENCRYPTION_TYPE
                    );
                }
                ?>

                <p style="text-align: center">
                    <button class="btn btn-primary btn-lg" type="submit">
                        Go to step 2 of 3
                    </button>
                </p>
            </div>
        </div>
        </form>
<?php
    PMF_System::renderFooter();
}

/**************************** STEP 2 OF 3 ***************************/
if ($step == 2) {

    $checkDatabaseSetupFile = $checkLdapSetupFile = false;

    // First backup old inc/data.php, then backup new config/bak.database.php and copy inc/data.php
    // to config/database.php
    // This is needed for 2.5 updates only
    if (file_exists(PMF_ROOT_DIR . '/inc/data.php')) {
        if (!copy(PMF_ROOT_DIR . '/inc/data.php', PMF_ROOT_DIR . '/config/database.bak.php') ||
            !copy(PMF_ROOT_DIR . '/inc/data.php', PMF_ROOT_DIR . '/config/database.php')) {
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> The backup file ../config/database.bak.php " .
                  "could not be written. Please correct this!</p>";
        } else {
            $checkDatabaseSetupFile = true;
        }
    }

    // The backup an existing config/database.php
    // 2.6+ updates
    if (file_exists(PMF_ROOT_DIR . '/config/database.php')) {
        if (!copy(PMF_ROOT_DIR . '/config/database.php', PMF_ROOT_DIR . '/config/database.bak.php')) {
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> The backup file ../config/database.bak.php " .
                  "could not be written. Please correct this!</p>";
        } else {
            $checkDatabaseSetupFile = true;
        }
    }

    // Now backup and move LDAP setup if available
    // This is needed for 2.5+ updates with a LDAP configuration file
    if (file_exists(PMF_ROOT_DIR . '/inc/dataldap.php')) {
        if (!copy(PMF_ROOT_DIR . '/inc/dataldap.php', PMF_ROOT_DIR . '/config/ldap.bak.php') ||
            !copy(PMF_ROOT_DIR . '/inc/dataldap.php', PMF_ROOT_DIR . '/config/ldap.php')) {
            echo "<p class=\"alert alert-danger\"><strong>Error:</strong> The backup file ../config/ldap.bak.php " .
                  "could not be written. Please correct this!</p>";
        } else {
            $checkLdapSetupFile = true;
        }
    }

    // is everything is okay?
    if ($checkDatabaseSetupFile) {
?>
        <form action="update.php?step=3" method="post">
        <input type="hidden" name="version" value="<?php echo $version; ?>">
        <div class="row form-group">
            <div class="col-lg-12">
                <ul class="nav nav-pills nav-justified thumbnail setup-panel">
                    <li class="disabled"><a href="#">
                            <h4 class="list-group-item-heading">Step 1 of 3</h4>
                            <p class="list-group-item-text">Update information</p>
                        </a>
                    </li>
                    <li class="active"><a href="#">
                            <h4 class="list-group-item-heading">Step 2 of 3</h4>
                            <p class="list-group-item-text">File backups</p>
                        </a>
                    </li>
                    <li class="disabled"><a href="update.php?step=3">
                            <h4 class="list-group-item-heading">Step 3 of 3</h4>
                            <p class="list-group-item-text">Database updates</p>
                        </a>
                   </li>
                </ul>
            </div>
        </div>
        <div class="row setup-content" id="step2">
            <div class="col-lg-12">
                <p>A backup of your database configuration file has been made.</p>
                <p>The configuration will be updated after the next step.</p>
                <p style="text-align: center;">
                    <button class="btn btn-primary btn-lg" type="submit">
                        Go to step 3 of 3
                    </button>
                </p>
            </div>
        </div>
        </form>
<?php
        PMF_System::renderFooter();
    } else {
        echo '<p class="alert alert-danger"><strong>Error:</strong> Your version of phpMyFAQ could not updated.</p>';
        PMF_System::renderFooter();
    }
}

/**************************** STEP 3 OF 3 ***************************/
if ($step == 3) {
?>

        <div class="row form-group">
            <div class="col-lg-12">
                <ul class="nav nav-pills nav-justified thumbnail setup-panel">
                    <li class="disabled"><a href="#">
                            <h4 class="list-group-item-heading">Step 1 of 3</h4>
                            <p class="list-group-item-text">Update information</p>
                        </a>
                    </li>
                    <li class="disabled"><a href="#">
                            <h4 class="list-group-item-heading">Step 2 of 3</h4>
                            <p class="list-group-item-text">File backups</p>
                        </a>
                    </li>
                    <li class="active"><a href="#">
                            <h4 class="list-group-item-heading">Step 3 of 3</h4>
                            <p class="list-group-item-text">Database updates</p>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="row setup-content" id="step2">
            <div class="col-lg-12">
<?php
    $images = [];

    //
    // UPDATES FROM 2.5.1
    //
    if (version_compare($version, '2.5.1', '<')) {
        // Truncate table and re-import all stopwords with the new Lithuanian ones
        $query[] = "DELETE FROM ". PMF_Db::getTablePrefix() . "faqstopwords";
        require 'stopwords.sql.php';
    }

    //
    // UPDATES FROM 2.5.2
    if (version_compare($version, '2.5.3', '<')) {
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'spam.enableCaptchaCode'
            WHERE config_name = 'spam.enableCatpchaCode'";
    }

    //
    // UPDATES FROM 2.6.0-alpha
    //
    if (version_compare($version, '2.6.0-alpha', '<')) {

        require '../lang/' . $faqConfig->get('main.language');

        if (isset($PMF_LANG['metaCharset']) && strtolower($PMF_LANG['metaCharset']) != 'utf-8') {
            // UTF-8 Migration
            switch($DB['type']) {
            case 'mysqli':
                include 'mysqli.utf8migration.php';
                break;

            default:
                echo '<p class="hint">Please read <a target="_blank" href="../docs/documentation.en.html">' .
                      'documenation</a> about migration to UTF-8.</p>';
                break;
            }
        }

        $faqConfig->add('main.enableUpdate', 'false');
        $faqConfig->add('security.useSslForLogins', 'false');
        $faqConfig->add('main.currentApiVersion', PMF_System::getApiVersion());
        $faqConfig->add('main.templateSet', 'default');
        $faqConfig->add('main.numberSearchTerms', '10');
        $faqConfig->add('records.orderingPopularFaqs', 'visits');

        // Attachments stuff
        $faqConfig->add('records.attachmentsStorageType', '0');
        $faqConfig->add('records.enableAttachmentEncryption', 'false');
        $faqConfig->add('records.defaultAttachmentEncKey', '');
        switch($DB['type']) {
            case 'pgsql':
                $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqattachment (
                    id SERIAL NOT NULL,
                    record_id int4 NOT NULL,
                    record_lang varchar(5) NOT NULL,
                    real_hash char(32) NOT NULL,
                    virtual_hash char(32) NOT NULL,
                    password_hash char(40) NULL,
                    filename varchar(255) NOT NULL,
                    filesize int NOT NULL,
                    encrypted int NOT NULL DEFAULT 0,
                    mime_type varchar(255) NULL,
                    PRIMARY KEY (id))";

                $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqattachment_file (
                    virtual_hash char(32) NOT NULL,
                    contents bytea,
                    PRIMARY KEY (virtual_hash))";
                break;
            case 'mysqli':
                $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqattachment (
                    id int(11) NOT NULL,
                    record_id int(11) NOT NULL,
                    record_lang varchar(5) NOT NULL,
                    real_hash char(32) NOT NULL,
                    virtual_hash char(32) NOT NULL,
                    password_hash char(40) NULL,
                    filename varchar(255) NOT NULL,
                    filesize int NOT NULL,
                    encrypted tinyint NOT NULL DEFAULT 0,
                    mime_type varchar(255) NULL,
                    PRIMARY KEY (id))";

                $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqattachment_file (
                    virtual_hash char(32) NOT NULL,
                    contents blob NOT NULL,
                    PRIMARY KEY (virtual_hash))";
                break;
            default:
                /**
                 * Just try standard SQL and hope for the best
                 */
                $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqattachment (
                    id int NOT NULL,
                    record_id int NOT NULL,
                    record_lang varchar(5) NOT NULL,
                    hash char(33) NOT NULL,
                    filename varchar(255) NOT NULL,
                    file_contents blob,
                    encrypted int,
                    PRIMARY KEY (id))";
                break;
        }

    }

    //
    // UPDATES FROM 2.6.0-RC
    //
    if (version_compare($version, '2.6.0-RC', '<')) {
        $faqConfig->add('main.optionalMailAddress', 'false');
        $faqConfig->add('main.useAjaxSearchOnStartpage', 'false');
    }

    //
    // UPDATES FROM 2.6.99
    //
    if (version_compare($version, '2.6.99', '<')) {
        $faqConfig->add('search.relevance', 'thema,content,keywords');
        $faqConfig->add('search.enableRelevance', 'false');
        $faqConfig->add('main.enableGoogleTranslation', 'false');
        $faqConfig->add('main.googleTranslationKey', 'INSERT-YOUR-KEY');
    }

    //
    // UPDATES FROM 2.7.0-alpha
    //
    if (version_compare($version, '2.7.0-alpha', '<')) {
        // Add new config values
        $faqConfig->add('socialnetworks.enableTwitterSupport', 'false');
        $faqConfig->add('socialnetworks.twitterConsumerKey', '');
        $faqConfig->add('socialnetworks.twitterConsumerSecret', '');
        $faqConfig->add('socialnetworks.twitterAccessTokenKey', '');
        $faqConfig->add('socialnetworks.twitterAccessTokenSecret', '');
        $faqConfig->add('socialnetworks.enableFacebookSupport', 'false');

        // Migrate faqquestion table to new structure
        switch($DB['type']) {
            case 'pgsql':
                $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions RENAME COLUMN ask_username TO username";
                $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions RENAME COLUMN ask_usermail TO email";
                $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions RENAME COLUMN ask_rubrik TO category_id";
                $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions RENAME COLUMN ask_content TO question";
                $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions RENAME COLUMN ask_date TO created";
                break;
            case 'mssql':
            case 'sqlsrv':
                $query[] = "EXEC sp_RENAME '" . PMF_Db::getTablePrefix() . "faqquestions.ask_username', 'username', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '" . PMF_Db::getTablePrefix() . "faqquestions.ask_usermail', 'email', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '" . PMF_Db::getTablePrefix() . "faqquestions.ask_rubrik', 'category_id', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '" . PMF_Db::getTablePrefix() . "faqquestions.ask_content', 'question', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '" . PMF_Db::getTablePrefix() . "faqquestions.ask_date', 'created', 'COLUMN'";
                break;
            case 'mysqli':
                $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions CHANGE ask_username username VARCHAR(100) NOT NULL";
                $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions CHANGE ask_usermail email VARCHAR(100) NOT NULL";
                $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions CHANGE ask_rubrik category_id INT(11) NOT NULL";
                $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions CHANGE ask_content question TEXT NOT NULL";
                $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions CHANGE ask_date created VARCHAR(20) NOT NULL";
                break;
        }

        $query[] = "INSERT INTO ". PMF_Db::getTablePrefix() . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (34, 'addattachment', 'Right to add attachments', 1, 1)";
        $query[] = "INSERT INTO ". PMF_Db::getTablePrefix() . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (35, 'editattachment', 'Right to edit attachments', 1, 1)";
        $query[] = "INSERT INTO ". PMF_Db::getTablePrefix() . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (36, 'delattachment', 'Right to delete attachments', 1, 1)";
        $query[] = "INSERT INTO ". PMF_Db::getTablePrefix() . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (37, 'dlattachment', 'Right to download attachments', 1, 1)";
        $query[] = "INSERT INTO ". PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 34)";
        $query[] = "INSERT INTO ". PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 35)";
        $query[] = "INSERT INTO ". PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 36)";
        $query[] = "INSERT INTO ". PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 37)";
    }

    //
    // UPDATES FROM 2.7.0-alpha2
    //
    if (version_compare($version, '2.7.0-alpha2', '<')) {
        $query[] = "INSERT INTO ". PMF_Db::getTablePrefix() . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (38, 'reports', 'Right to generate reports', 1, 1)";
        $query[] = "INSERT INTO ". PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 38)";
    }

    //
    // UPDATES FROM 2.7.0-beta
    //
    if (version_compare($version, '2.7.0-beta', '<')) {
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'search.numberSearchTerms'
            WHERE config_name = 'main.numberSearchTerms'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'search.useAjaxSearchOnStartpage'
            WHERE config_name = 'main.useAjaxSearchOnStartpage'";
    }

    //
    // UPDATES FROM 2.7.0-beta2
    //
    if (version_compare($version, '2.7.0-beta2', '<')) {
        $faqConfig->add('security.ssoSupport', 'false');
        $faqConfig->add('security.ssoLogoutRedirect', '');
        $faqConfig->add('main.dateFormat', 'Y-m-d H:i');
        $faqConfig->add('security.enableLoginOnly', 'false');
    }

    //
    // UPDATES FROM 2.7.0-RC
    //
    if (version_compare($version, '2.7.0-RC', '<')) {
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'records.numberOfRecordsPerPage'
            WHERE config_name = 'main.numberOfRecordsPerPage'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'records.numberOfShownNewsEntries'
            WHERE config_name = 'main.numberOfShownNewsEntries'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'records.orderingPopularFaqs'
            WHERE config_name = 'main.orderingPopularFaqs'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'records.disableAttachments'
            WHERE config_name = 'main.disableAttachments'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'records.maxAttachmentSize'
            WHERE config_name = 'main.maxAttachmentSize'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'records.attachmentsPath'
            WHERE config_name = 'main.attachmentsPath'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'records.attachmentsStorageType'
            WHERE config_name = 'main.attachmentsStorageType'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'records.enableAttachmentEncryption'
            WHERE config_name = 'main.enableAttachmentEncryption'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'records.defaultAttachmentEncKey'
            WHERE config_name = 'main.defaultAttachmentEncKey'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'security.permLevel'
            WHERE config_name = 'main.permLevel'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'security.ipCheck'
            WHERE config_name = 'main.ipCheck'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'security.enableLoginOnly'
            WHERE config_name = 'main.enableLoginOnly'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'security.ldapSupport'
            WHERE config_name = 'main.ldapSupport'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'security.bannedIPs'
            WHERE config_name = 'main.bannedIPs'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'security.ssoSupport'
            WHERE config_name = 'main.ssoSupport'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'security.ssoLogoutRedirect'
            WHERE config_name = 'main.ssoLogoutRedirect'";
        $query[] = "UPDATE ". PMF_Db::getTablePrefix() . "faqconfig SET config_name = 'security.useSslForLogins'
            WHERE config_name = 'main.useSslForLogins'";
    }

    //
    // UPDATES FROM 2.7.1
    //
    if (version_compare($version, '2.7.1', '<')) {
        $faqConfig->add('security.useSslOnly', 'false');
    }

    //
    // UPDATES FROM 2.7.3
    //
    if (version_compare($version, '2.7.3', '<')) {
        $query[] = "DELETE FROM ".PMF_Db::getTablePrefix()."faqright WHERE right_id = 18 AND right_id = 19";
        $query[] = "DELETE FROM ".PMF_Db::getTablePrefix()."faquser_right WHERE right_id = 18 AND right_id = 19";
        $query[] = "DELETE FROM ".PMF_Db::getTablePrefix()."faqgroup_right WHERE right_id = 18 AND right_id = 19";
    }

    //
    // UPDATES FROM 2.8.0-alpha
    //
    if (version_compare($version, '2.8.0-alpha', '<')) {

        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (39, 'addfaq', 'Right to add FAQs in frontend', 1, 1)";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 39)";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (40, 'addquestion', 'Right to add questions in frontend', 1, 1)";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 40)";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (41, 'addcomment', 'Right to add comments in frontend', 1, 1)";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 41)";

        $faqConfig->add('cache.varnishEnable', 'false');
        $faqConfig->add('cache.varnishHost', '127.0.0.1');
        $faqConfig->add('cache.varnishPort', '2000');
        $faqConfig->add('cache.varnishSecret', '');
        $faqConfig->add('cache.varnishTimeout', '500');

        $faqConfig->add('security.forcePasswordUpdate', 'true');

        if ('sqlite3' === $DB['type']) {
            $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions ADD COLUMN answer_id INT NOT NULL DEFAULT 0";
        } else {
            $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqquestions ADD answer_id INT NOT NULL DEFAULT 0";
        }

        $faqConfig->add('records.enableCloseQuestion', 'false');
        $faqConfig->add('records.enableDeleteQuestion', 'false');

        $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faquserlogin_temp (
            login varchar(128) NOT NULL,
            pass varchar(80) NOT NULL,
            PRIMARY KEY (login))";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faquserlogin_temp SELECT * FROM " . PMF_Db::getTablePrefix() . "faquserlogin";
        $query[] = "DROP TABLE " . PMF_Db::getTablePrefix() . "faquserlogin";
        $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faquserlogin (
            login varchar(128) NOT NULL,
            pass varchar(80) NOT NULL,
            PRIMARY KEY (login))";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faquserlogin SELECT * FROM " . PMF_Db::getTablePrefix() . "faquserlogin_temp";
        $query[] = "DROP TABLE " . PMF_Db::getTablePrefix() . "faquserlogin_temp";
    }

    //
    // UPDATES FROM 2.8.0-alpha2
    //
    if (version_compare($version, '2.8.0-alpha2', '<')) {
        switch($DB['type']) {
            case 'pgsql':
                $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqinstances (
                    id int4 NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    instance VARCHAR(255) NOT NULL,
                    comment TEXT NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    modified TIMESTAMP NOT NULL,
                    PRIMARY KEY (id))";
                $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqinstances_config (
                    instance_id int4 NOT NULL,
                    config_name VARCHAR(255) NOT NULL default '',
                    config_value VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (instance_id, config_name))";
                break;
            case 'mssql':
            case 'sqlsrv':
            case 'sqlite3':
                $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqinstances (
                    id INT NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    instance VARCHAR(255) NOT NULL,
                    comment TEXT NULL,
                    created DATETIME NOT NULL,
                    modified DATETIME NOT NULL,
                    PRIMARY KEY (id))";
                $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqinstances_config (
                    instance_id INT NOT NULL,
                    config_name VARCHAR(255) NOT NULL default '',
                    config_value VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (instance_id, config_name))";
                break;
            case 'mysqli':
                $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqinstances (
                    id INT(11) NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    instance VARCHAR(255) NOT NULL,
                    comment TEXT NULL,
                    created TIMESTAMP DEFAULT 0,
                    modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
                $query[] = "CREATE TABLE " . PMF_Db::getTablePrefix() . "faqinstances_config (
                    instance_id INT(11) NOT NULL,
                    config_name VARCHAR(255) NOT NULL default '',
                    config_value VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (instance_id, config_name)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
                break;
        }

        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faqright (right_id, name, description) VALUES
            (42, 'editinstances', 'Right to edit multi-site instances')";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 42)";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faqright (right_id, name, description) VALUES
            (43, 'addinstances', 'Right to add multi-site instances')";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 43)";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faqright (right_id, name, description) VALUES
            (44, 'delinstances', 'Right to delete multi-site instances')";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 44)";

        if ('sqlite3' === $DB['type']) {
            $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faquser ADD COLUMN remember_me VARCHAR(150) NULL";
        } else {
            $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faquser ADD remember_me VARCHAR(150) NULL";
        }
    }

    // Perform the queries for updating/migrating the database
    if (isset($query)) {
        echo '<div class="text-center">';
        $count = 0;
        foreach ($query as $key => $executeQuery) {
            $result = $faqConfig->getDb()->query($executeQuery);
            echo '.';
            if (!($key % 100)) {
                echo '<br />';
            }
            if (!$result) {
                echo "</div>";
                echo '<p class="alert alert-danger"><strong>Error:</strong> Please update your version of phpMyFAQ ' .
                      'once again or send us a <a href="http://bugs.phpmyfaq.de" target="_blank">bug report</a>.' .
                      '</p>';
                printf(
                    '<p class="alert alert-danger"><strong>DB error:</strong> %s</p>',
                    $faqConfig->getDb()->error()
                );
                printf(
                    '<code>%s</code>',
                    htmlentities($executeQuery)
                );
                PMF_System::renderFooter();
            }
            usleep(10000);
            $count++;
            if (!($count % 10)) {
                ob_flush();
            }
        }
        echo "</div>";
    }

    // Clear the array with the queries
    unset($query);
    $query = [];

    //
    // 2nd UPDATES FROM 2.8.0-alpha2
    //
    if (version_compare($version, '2.8.0-alpha2', '<')) {

        $link = new PMF_Link(null, $faqConfig);

        $instanceData = array(
            'url'      => $link->getSystemUri($_SERVER['SCRIPT_NAME']),
            'instance' => $link->getSystemRelativeUri('setup/update.php'),
            'comment'  => $faqConfig->get('main.titleFAQ')
        );
        $faqInstance = new PMF_Instance($faqConfig);
        $faqInstance->addInstance($instanceData);

        $faqInstanceMaster = new PMF_Instance_Master($faqConfig);
        $faqInstanceMaster->createMaster($faqInstance);

        $faqConfig->add('records.autosaveActive', 'false');
        $faqConfig->add('records.autosaveSecs', '180');
        $faqConfig->add('main.maintenanceMode', 'false');
        $faqConfig->add('security.salt', md5($faqConfig->get('main.referenceURL')));
    }

    //
    // UPDATES FROM 2.8.0-alpha3
    //
    if (version_compare($version, '2.8.0-alpha3', '<')) {

        $query[] = "DROP TABLE " . PMF_Db::getTablePrefix() . "faqlinkverifyrules";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faqright (right_id, name, description) VALUES
            (45, 'export', 'Right to export the complete FAQ')";
        $query[] = "INSERT INTO " . PMF_Db::getTablePrefix() . "faquser_right (user_id, right_id) VALUES (1, 45)";

    }

    //
    // UPDATES FROM 2.8.0-beta
    //
    if (version_compare($version, '2.8.0-beta', '<')) {
        $faqConfig->add('records.randomSort', 'false');
        $faqConfig->add('main.enableWysiwygEditorFrontend', 'false');
    }

    //
    // UPDATED FROM 2.8.0-beta2
    //
    if (version_compare($version, '2.8.0-beta2', '<')) {
        $faqConfig->delete('main.enableGoogleTranslation');
        $faqConfig->delete('main.googleTranslationKey');
    }

    //
    // UPDATED FROM 2.8.0-beta3
    //
    if (version_compare($version, '2.8.0-beta3', '<')) {
        $faqConfig->add('main.enableGravatarSupport', 'false');
    }

    //
    // UPDATED FROM 2.8.15
    //
    if (version_compare($version, '2.8.16', '<')) {
        $query[] = "CREATE INDEX index_time ON " . PMF_Db::getTablePrefix() . "faqsessions (time)";
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
            $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faquser ADD COLUMN success INT(1) NULL DEFAULT 1";
        } else {
            $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faquser ADD success INT(1) NULL DEFAULT 1";
        }
    }


    //
    // UPDATES FROM 2.9.0-alpha
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

        if ('sqlite3' === $DB['type']) {
            $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqcategories ADD COLUMN active INT(1) NULL DEFAULT 1";
        } else {
            $query[] = "ALTER TABLE " . PMF_Db::getTablePrefix() . "faqcategories ADD active INT(1) NULL DEFAULT 1";
        }
    }

    // Always the last step: Update version number
    if (version_compare($version, PMF_System::getVersion(), '<')) {
        $faqConfig->update(array('main.currentVersion' => PMF_System::getVersion()));
    }

    // optimize tables if possible
    switch ($DB['type']) {
        case 'mysqli':
            // Get all table names
            $faqConfig->getDb()->getTableNames(PMF_Db::getTablePrefix());
            foreach ($faqConfig->getDb()->tableNames as $tableName) {
                $query[] = 'OPTIMIZE TABLE '.$tableName;
            }
            break;
        case 'pgsql':
            $query[] = "VACUUM ANALYZE;";
            break;
    }

    // Perform the queries for optimizing the database
    if (isset($query)) {
        echo '<div class="center">';
        foreach ($query as $executeQuery) {
            $result = $faqConfig->getDb()->query($executeQuery);
            printf('<span title="%s">.</span>', $executeQuery);
            if (!$result) {
                echo '<p class="alert alert-danger"><strong>Error:</strong> Please install your version of phpMyFAQ once again ' .
                      'or send us a <a href="http://bugs.phpmyfaq.de" target="_blank">bug report</a>.</p>';
                printf('<p class="error"><strong>DB error:</strong> %s</p>', $faqConfig->getDb()->error());
                printf('<code>%s</code>', htmlentities($executeQuery));
                PMF_System::renderFooter();
            }
            usleep(10000);
        }
        echo "</div>";
    }

    echo "</p>\n";
    echo '<p class="alert alert-success">The database was updated successfully. Thank you very much for updating.</p>';
    echo '<h3>Back to your updated <a href="../index.php">phpMyFAQ installation</a> and have fun! :-)</h3>';

    // Remove backup files
    foreach (glob(PMF_ROOT_DIR.'/config/*.bak.php') as $filename) {
        if (!@unlink($filename)) {
            printf("<p class=\"alert alert-info\">Please remove the backup file %s manually.</p>\n", $filename);
        }
    }
    // Remove 'setup.php' file
    if (is_writeable(__DIR__ . '/setup.php') && @unlink(__DIR__ . '/setup.php')) {
        echo "<p class=\"alert alert-success\">The file <em>./setup/index.php</em> was deleted automatically.</p>\n";
    } else {
        echo "<p class=\"alert alert-danger\">Please delete the file <em>./setup/index.php</em> manually.</p>\n";
    }
    // Remove 'update.php' file
    if (is_writeable(__DIR__ . '/update.php') && @unlink(__DIR__ . '/update.php')) {
        echo "<p class=\"alert alert-success\">The file <em>./setup/update.php</em> was deleted automatically.</p>\n";
    } else {
        echo "<p class=\"alert alert-danger\">Please delete the file <em>./setup/update.php</em> manually.</p>\n";
    }

    PMF_System::renderFooter();
}
